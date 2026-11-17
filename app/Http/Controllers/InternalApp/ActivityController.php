<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use App\Services\InternalAppService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActivityController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        [$query, $filters] = $this->buildQuery($request);

        $items = $query->paginate(30)->withQueryString();

        $users = DB::table('users as u')
            ->leftJoin('personal_informations as p', 'p.user_id', '=', 'u.id')
            ->whereNull('u.deleted_at')
            ->select('u.id', DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), u.user_name) as nama"))
            ->orderBy('nama')->get();
        $projects = DB::table('internal_projects')->orderBy('name')->get(['id', 'name']);

        $summary = DB::table('internal_activity_logs')
            ->select('user_id', DB::raw('COUNT(*) as total'))
            ->whereNotNull('user_id')->groupBy('user_id')->pluck('total', 'user_id');

        return view('internal-app.activity.index', compact('items', 'users', 'projects', 'filters', 'summary'));
    }

    public function export(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        [$query] = $this->buildQuery($request);
        $rows = $query->limit(20000)->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aktivitas Karyawan');
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'LAPORAN AKTIVITAS KARYAWAN — DIVISI PROJECT RST 2026');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->fromArray(['Tanggal', 'Karyawan', 'Project', 'Tipe', 'Aksi', 'Deskripsi'], null, 'A3');
        $sheet->getStyle('A3:F3')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $r = 4;
        foreach ($rows as $row) {
            $sheet->fromArray([[
                $row->activity_date, $row->nama, $row->project_name, $row->type, $row->action, $row->description,
            ]], null, 'A'.$r);
            $r++;
        }
        $last = max(4, $r - 1);
        $sheet->getStyle("A3:F{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (['A' => 14, 'B' => 24, 'C' => 26, 'D' => 12, 'E' => 14, 'F' => 60] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
        $sheet->freezePane('A4');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Laporan-Aktivitas-Karyawan.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function memberIndex(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $query = DB::table('internal_activity_logs as a')
            ->leftJoin('internal_projects as p', 'p.id', '=', 'a.project_id')
            ->where('a.user_id', InternalAppService::userId())
            ->select('a.*', 'p.name as project_name')
            ->orderByDesc('a.activity_date')->orderByDesc('a.id');
        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(function ($w) use ($like) {
                $w->whereRaw("LOWER(COALESCE(a.description,'')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(p.name,'')) LIKE ?", [$like]);
            });
        }
        $items = $query->paginate(30)->withQueryString();
        $projects = DB::table('internal_projects')->orderBy('name')->get(['id', 'name']);

        return view('internalapp::member.activity.index', compact('items', 'q', 'projects'));
    }

    public function store(Request $request)
    {
        $isAdmin = $this->isAdmin();
        $currentUserId = InternalAppService::userId();
        $targetUserId = $isAdmin ? ((int) $request->input('user_id') ?: $currentUserId) : $currentUserId;
        if ($targetUserId <= 0) {
            return response()->json(['status' => false, 'message' => 'User tidak valid.'], 422);
        }
        $request->merge(['user_id' => $targetUserId]);

        $v = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'project_id' => 'required|integer|exists:internal_projects,id',
            'title' => 'required|string|max:200',
            'phase' => 'nullable|string|max:50',
            'priority' => 'required|in:low,medium,high,urgent',
            'due_date' => 'nullable|date',
            'status' => 'required|in:backlog,todo,in_progress,in_review,done',
            'activity_date' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
        ]);

        $progress = match ($v['status']) {
            'done' => 100,
            'in_review' => 80,
            'in_progress' => 40,
            default => 0,
        };

        $taskId = DB::table('internal_tasks')->insertGetId([
            'project_id' => $v['project_id'],
            'title' => $v['title'],
            'description' => $v['description'] ?? 'Input aktivitas manual',
            'status' => $v['status'],
            'phase' => $v['phase'] ?? null,
            'priority' => $v['priority'],
            'assignee_id' => $targetUserId,
            'reporter_id' => $currentUserId ?: null,
            'due_date' => $v['due_date'] ?? null,
            'progress' => $progress,
            'position' => 0,
            'labels' => 'manual-input',
            'is_milestone' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('internal_project_members')->insertOrIgnore([
            'project_id' => $v['project_id'], 'user_id' => $targetUserId, 'role' => 'member',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('internal_task_activity_logs')->insert([
            'task_id' => $taskId,
            'user_id' => $targetUserId,
            'action' => 'created',
            'description' => 'Input aktivitas: '.$v['title'],
            'meta' => json_encode(['source' => 'manual']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (Schema::hasTable('internal_activity_logs')) {
            DB::table('internal_activity_logs')->insert([
                'user_id' => $targetUserId,
                'project_id' => $v['project_id'],
                'task_id' => $taskId,
                'type' => 'task',
                'action' => 'created',
                'description' => $v['title'],
                'meta' => json_encode(['phase' => $v['phase'] ?? null, 'source' => 'manual']),
                'activity_date' => $v['activity_date'] ?? $v['due_date'] ?? date('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        InternalAppService::refreshProjectProgress((int) $v['project_id']);

        return response()->json(['status' => true, 'message' => 'Aktivitas berhasil disimpan.']);
    }

    protected function buildQuery(Request $request): array
    {
        $q = trim((string) $request->input('q', ''));
        $userId = (int) $request->input('user_id', 0);
        $projectId = (int) $request->input('project_id', 0);
        $from = $request->input('from');
        $to = $request->input('to');
        $type = trim((string) $request->input('type', ''));

        $query = DB::table('internal_activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('personal_informations as pi', 'pi.user_id', '=', 'a.user_id')
            ->leftJoin('internal_projects as p', 'p.id', '=', 'a.project_id')
            ->select(
                'a.*',
                'p.name as project_name',
                DB::raw("COALESCE(NULLIF(pi.nick_name, ''), TRIM(COALESCE(pi.first_name, '') || ' ' || COALESCE(pi.last_name, '')), u.user_name, 'System') as nama")
            )
            ->orderByDesc('a.activity_date')->orderByDesc('a.id');

        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(function ($w) use ($like) {
                $w->whereRaw("LOWER(COALESCE(a.description,'')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(p.name,'')) LIKE ?", [$like])
                    ->orWhereRaw("LOWER(COALESCE(u.user_name,'')) LIKE ?", [$like]);
            });
        }
        if ($userId > 0) {
            $query->where('a.user_id', $userId);
        }
        if ($projectId > 0) {
            $query->where('a.project_id', $projectId);
        }
        if ($from) {
            $query->where('a.activity_date', '>=', $from);
        }
        if ($to) {
            $query->where('a.activity_date', '<=', $to);
        }
        if ($type !== '') {
            $query->where('a.type', $type);
        }

        return [$query, compact('q', 'userId', 'projectId', 'from', 'to', 'type')];
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
