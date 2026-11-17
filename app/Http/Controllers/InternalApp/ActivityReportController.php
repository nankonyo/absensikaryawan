<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActivityReportController extends Controller
{
    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
    }

    public function index(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        [$rows, $meta] = $this->build($request);

        $users = $this->userOptions();
        $divisions = DB::table('internal_employee_settings')->select('divisi')->whereNotNull('divisi')->distinct()->orderBy('divisi')->pluck('divisi');
        if ($divisions->isEmpty()) {
            $divisions = collect(['Divisi Project RST']);
        }

        return view('internal-app.activity-report.index', compact('rows', 'meta', 'users', 'divisions'));
    }

    public function export(Request $request)
    {
        abort_unless($this->isAdmin(), 403);
        [$rows, $meta] = $this->build($request);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($meta['sheet_title']);

        $sheet->setCellValue('B2', 'Name   :');
        $sheet->setCellValue('C2', $meta['name']);
        $sheet->setCellValue('B3', 'Team   :');
        $sheet->setCellValue('C3', $meta['team']);
        $sheet->setCellValue('B4', 'Period :');
        $sheet->setCellValue('C4', $meta['month_label']);
        $sheet->setCellValue('B5', 'Date     :');
        $sheet->setCellValue('C5', date('d/m/Y'));
        $sheet->setCellValue('K5', $meta['done']);
        $sheet->setCellValue('E2', 'Laporan Aktivitas Project — Divisi Project RST');

        $headers = ['No.', 'Task Activity', 'Activity', 'Phase', 'Assignee', 'Priority', 'Due Date', 'Days Left', 'Status', 'Done'];
        $col = 2; // mulai kolom B (A dikosongkan seperti file asli)
        foreach ($headers as $h) {
            $sheet->setCellValueByColumnAndRow($col, 7, $h);
            $col++;
        }
        $sheet->getStyle('B7:K7')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDEBF7']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);

        $r = 8;
        foreach ($rows as $i => $row) {
            $values = [
                $i + 1,
                $row['task_activity'],
                $row['activity'],
                $row['phase'],
                $row['assignee'],
                $row['priority'],
                $row['due_date'],
                $row['days_left'],
                $row['status'],
                $row['done'],
            ];
            $c = 2;
            foreach ($values as $v) {
                $sheet->setCellValueByColumnAndRow($c, $r, $v);
                $c++;
            }
            $r++;
        }
        $last = max(7, $r - 1);
        $sheet->getStyle("B7:K{$last}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("B8:K{$last}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (['A' => 2, 'B' => 6, 'C' => 50, 'D' => 30, 'E' => 16, 'F' => 14, 'G' => 10, 'H' => 16, 'I' => 14, 'J' => 14, 'K' => 8] as $colLetter => $w) {
            $sheet->getColumnDimension($colLetter)->setWidth($w);
        }
        $sheet->freezePane('B8');

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'Laporan-Activity-'.$meta['file_suffix'].'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    protected function build(Request $request): array
    {
        $mode = $request->input('mode', 'employee');
        $userId = (int) $request->input('user_id', 0);
        $divisi = trim((string) $request->input('divisi', 'Divisi Project RST'));
        $period = $request->input('period', 'monthly');
        $month = $request->input('month');
        $year = (int) $request->input('year', date('Y'));
        $week = $request->input('week');

        if ($period === 'yearly') {
            if ($year < 2000 || $year > 2100) {
                $year = (int) date('Y');
            }
            $from = $year.'-01-01';
            $to = $year.'-12-31';
            $periodLabel = 'Tahun '.$year;
            $periodSuffix = 'yearly-'.$year;
            $periodSheet = 'Activity Yearly '.$year;
            $month = substr($from, 0, 7);
        } elseif ($period === 'weekly') {
            $anchor = $week ?: date('Y-m-d');
            $ts = strtotime($anchor) ?: time();
            $from = date('Y-m-d', strtotime('monday this week', $ts));
            $to = date('Y-m-d', strtotime('sunday this week', $ts));
            $weekNo = date('W', strtotime($from));
            $periodLabel = 'Minggu ke-'.$weekNo.' ('.date('d M Y', strtotime($from)).' s.d. '.date('d M Y', strtotime($to)).')';
            $periodSuffix = 'weekly-'.$from;
            $periodSheet = 'Activity Week '.$weekNo;
            $month = substr($from, 0, 7);
        } else {
            $period = 'monthly';
            if (! $month) {
                $latest = DB::table('internal_activity_logs')->max('activity_date');
                $month = $latest ? substr($latest, 0, 7) : date('Y-m');
            }
            if (! preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
                $month = date('Y-m');
            }
            $from = $month.'-01';
            $to = date('Y-m-t', strtotime($from));
            $periodLabel = date('F Y', strtotime($from));
            $periodSuffix = 'monthly-'.$month;
            $periodSheet = 'Activity '.date('M Y', strtotime($from));
            $year = (int) substr($from, 0, 4);
        }

        $query = DB::table('internal_activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('personal_informations as pi', 'pi.user_id', '=', 'a.user_id')
            ->leftJoin('internal_employee_settings as s', 's.user_id', '=', 'a.user_id')
            ->leftJoin('internal_tasks as t', 't.id', '=', 'a.task_id')
            ->leftJoin('internal_projects as p', 'p.id', '=', 'a.project_id')
            ->select(
                'a.*',
                't.title as task_title', 't.phase', 't.priority', 't.due_date', 't.status as task_status',
                'p.name as project_name',
                's.divisi',
                DB::raw("COALESCE(NULLIF(pi.nick_name, ''), TRIM(COALESCE(pi.first_name, '') || ' ' || COALESCE(pi.last_name, '')), u.user_name, 'System') as nama")
            )
            ->whereBetween('a.activity_date', [$from, $to]);

        $name = '-';
        $team = 'Divisi Project RST';
        if ($mode === 'employee') {
            if ($userId <= 0) {
                $userId = (int) (DB::table('internal_activity_logs')
                    ->whereNotNull('user_id')
                    ->select('user_id', DB::raw('COUNT(*) as total'))
                    ->groupBy('user_id')->orderByDesc('total')->value('user_id')
                    ?: DB::table('users')->whereNull('deleted_at')->orderBy('id')->value('id'));
            }
            $query->where('a.user_id', $userId);
            $name = $this->userName($userId);
            $team = (string) (DB::table('internal_employee_settings')->where('user_id', $userId)->value('divisi') ?: 'Divisi Project RST');
            $suffix = 'employee-'.$userId;
            $sheetTitle = 'Activity '.mb_substr($name, 0, 20);
        } else {
            $query->where('s.divisi', $divisi);
            $name = $divisi;
            $team = $divisi;
            $suffix = 'divisi-'.preg_replace('/[^A-Za-z0-9]+/', '-', $divisi);
            $sheetTitle = 'Activity '.mb_substr($divisi, 0, 20);
        }

        $logs = $query->orderBy('a.activity_date')->orderBy('a.id')->get();

        $seen = [];
        $rows = [];
        $done = 0;
        foreach ($logs as $l) {
            $key = ($l->user_id ?? 0).'-'.($l->task_id ?? ('log'.$l->id));
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $isDone = ($l->task_status ?? '') === 'done';
            if ($isDone) {
                $done++;
            }
            $due = $l->due_date ?: $l->activity_date;
            $rows[] = [
                'task_activity' => $l->task_title ?: $l->description,
                'activity' => $l->project_name ?: '-',
                'phase' => $l->phase ?: ucfirst($l->type ?? '-'),
                'assignee' => $l->nama,
                'priority' => ucfirst($l->priority ?: '-'),
                'due_date' => $due,
                'days_left' => $this->daysLeft($due, $isDone),
                'status' => $isDone ? 'Done' : $this->statusLabel($l->task_status ?? $l->action),
                'done' => $isDone ? 1 : 0,
            ];
        }

        $meta = [
            'mode' => $mode,
            'user_id' => $mode === 'employee' ? $userId : null,
            'name' => $name,
            'team' => $team,
            'period' => $period,
            'period_label' => $periodLabel,
            'from' => $from,
            'to' => $to,
            'year' => $year,
            'month' => $month,
            'week' => $from,
            'month_label' => $periodLabel,
            'done' => $done,
            'file_suffix' => $suffix.'-'.$periodSuffix,
            'sheet_title' => mb_substr($periodSheet, 0, 31),
        ];

        return [$rows, $meta];
    }

    protected function daysLeft(?string $due, bool $done): string
    {
        if (! $due) {
            return '-';
        }
        if ($done) {
            return 'Completed';
        }
        $diff = (int) floor((strtotime($due) - strtotime(date('Y-m-d'))) / 86400);
        if ($diff < 0) {
            return 'Expired '.abs($diff).' hari';
        }
        if ($diff === 0) {
            return 'Last day!';
        }

        return $diff.' hari';
    }

    protected function statusLabel(?string $status): string
    {
        return match ($status) {
            'done' => 'Done',
            'in_progress' => 'Ongoing',
            'in_review' => 'Review',
            'todo' => 'To Do',
            'backlog' => 'Not Started',
            default => 'Ongoing',
        };
    }

    protected function userName(int $userId): string
    {
        $row = DB::table('users as u')
            ->leftJoin('personal_informations as p', 'p.user_id', '=', 'u.id')
            ->where('u.id', $userId)
            ->select(DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), u.user_name) as nama"))
            ->first();

        return $row->nama ?? ('User #'.$userId);
    }

    protected function userOptions()
    {
        return DB::table('users as u')
            ->leftJoin('personal_informations as p', 'p.user_id', '=', 'u.id')
            ->whereNull('u.deleted_at')
            ->select('u.id', DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), u.user_name) as nama"))
            ->orderBy('nama')->get();
    }

    protected function isAdmin(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }
}
