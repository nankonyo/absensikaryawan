<?php

namespace App\Http\Controllers\InternalApp;

use App\Models\System\InternalAppInstallation;
use App\Services\InternalAppService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    protected array $statuses = ['backlog', 'todo', 'in_progress', 'in_review', 'done'];

    protected array $priorities = ['low', 'medium', 'high', 'urgent'];

    public function __construct()
    {
        InternalAppInstallation::ensureForRst();
        InternalAppService::touchPresence();
    }

    public function index(Request $request)
    {
        $projects = $this->projectsQuery()->get();
        $metrics = [
            'total' => $projects->count(),
            'in_progress' => $projects->where('status', 'in_progress')->count(),
            'completed' => $projects->where('status', 'completed')->count(),
            'overdue' => $projects->filter(fn ($p) => $p->end_date && $p->end_date < date('Y-m-d') && $p->status !== 'completed')->count(),
        ];

        $activeTasks = DB::table('internal_tasks as t')
            ->join('internal_projects as p', 'p.id', '=', 't.project_id')
            ->leftJoin('users', 'users.id', '=', 't.assignee_id')
            ->leftJoin('personal_informations as pi', 'pi.user_id', '=', 'users.id')
            ->leftJoin('internal_user_presence as up', 'up.user_id', '=', 't.assignee_id')
            ->whereIn('t.status', ['in_progress', 'in_review'])
            ->select(
                't.id', 't.title', 't.status', 't.priority', 't.progress', 't.due_date',
                'p.name as project_name', 'p.id as project_id', 't.assignee_id',
                DB::raw("COALESCE(NULLIF(pi.nick_name, ''), TRIM(COALESCE(pi.first_name, '') || ' ' || COALESCE(pi.last_name, '')), users.user_name, 'Unassigned') as assignee_name"),
                'up.status as presence_status', 'up.last_seen_at'
            )
            ->orderByDesc('t.updated_at')->limit(20)->get();

        $recentTasks = DB::table('internal_tasks as t')
            ->join('internal_projects as p', 'p.id', '=', 't.project_id')
            ->select('t.*', 'p.name as project_name')
            ->orderByDesc('t.updated_at')->limit(8)->get();

        $users = InternalAppService::userOptions();
        $members = DB::table('internal_project_members as m')
            ->join('users', 'users.id', '=', 'm.user_id')
            ->leftJoin('personal_informations as p', 'p.user_id', '=', 'users.id')
            ->select('m.project_id', 'm.user_id', DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), users.user_name) as nama"))
            ->get()->groupBy('project_id');

        return view('internal-app.project.index', compact('projects', 'metrics', 'activeTasks', 'recentTasks', 'users', 'members'));
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'name' => 'required|string|max:200',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:planning,in_progress,completed,overdue,on_hold',
            'color' => 'nullable|string|max:20',
            'daerah' => 'nullable|string|max:150',
            'instansi' => 'nullable|string|max:200',
            'production_url' => 'nullable|string|max:500',
            'development_url' => 'nullable|string|max:500',
            'hosting_provider' => 'nullable|string|max:150',
            'account_type' => 'nullable|in:gmail,hosting,other',
            'account_email' => 'nullable|email|max:255',
            'account_username' => 'nullable|string|max:255',
            'account_password' => 'nullable|string|max:1000',
            'notes' => 'nullable|string',
            'members' => 'nullable|array', 'members.*' => 'integer|exists:users,id',
        ]);
        $data = [
            'code' => $v['code'] ?? null, 'name' => $v['name'], 'description' => $v['description'] ?? null,
            'owner_id' => InternalAppService::userId(), 'start_date' => $v['start_date'] ?? null,
            'end_date' => $v['end_date'] ?? null, 'status' => $v['status'] ?? 'planning',
            'progress' => 0, 'color' => $v['color'] ?? '#D34B52',
        ];
        $data = array_merge($data, $this->projectMeta($v));
        $id = DB::table('internal_projects')->insertGetId($data + ['created_at' => now(), 'updated_at' => now()]);
        foreach (array_unique(array_merge([InternalAppService::userId()], $request->input('members', []))) as $uid) {
            if ($uid) {
                DB::table('internal_project_members')->insertOrIgnore([
                    'project_id' => $id, 'user_id' => $uid, 'role' => $uid == InternalAppService::userId() ? 'owner' : 'member',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
        InternalAppService::projectActivity((int) $id, 'created', 'Project dibuat: '.$v['name']);

        return response()->json(['status' => true, 'message' => 'Proyek berhasil dibuat.', 'id' => $id]);
    }

    public function update(Request $request, $id)
    {
        $v = $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date', 'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:planning,in_progress,completed,overdue,on_hold',
            'color' => 'nullable|string|max:20',
            'daerah' => 'nullable|string|max:150',
            'instansi' => 'nullable|string|max:200',
            'production_url' => 'nullable|string|max:500',
            'development_url' => 'nullable|string|max:500',
            'hosting_provider' => 'nullable|string|max:150',
            'account_type' => 'nullable|in:gmail,hosting,other',
            'account_email' => 'nullable|email|max:255',
            'account_username' => 'nullable|string|max:255',
            'account_password' => 'nullable|string|max:1000',
            'notes' => 'nullable|string',
        ]);
        $data = [
            'name' => $v['name'], 'description' => $v['description'] ?? null,
            'start_date' => $v['start_date'] ?? null, 'end_date' => $v['end_date'] ?? null,
            'status' => $v['status'], 'color' => $v['color'] ?? '#D34B52',
        ];
        $data = array_merge($data, $this->projectMeta($v));
        $data['updated_at'] = now();
        DB::table('internal_projects')->where('id', $id)->update($data);
        InternalAppService::projectActivity((int) $id, 'updated', 'Project diperbarui: '.$v['name']);

        return response()->json(['status' => true, 'message' => 'Proyek diperbarui.']);
    }

    public function show($id)
    {
        $project = $this->projectsQuery()->where('p.id', $id)->first();
        abort_unless($project, 404);

        $members = DB::table('internal_project_members as m')
            ->join('users', 'users.id', '=', 'm.user_id')
            ->leftJoin('personal_informations as p', 'p.user_id', '=', 'users.id')
            ->where('m.project_id', $id)
            ->select('m.*', 'users.user_name', DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), users.user_name) as nama"))
            ->orderBy('nama')->get();

        $tasks = DB::table('internal_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.assignee_id')
            ->leftJoin('personal_informations as pi', 'pi.user_id', '=', 't.assignee_id')
            ->where('t.project_id', $id)
            ->select('t.*', DB::raw("COALESCE(NULLIF(pi.nick_name, ''), TRIM(COALESCE(pi.first_name, '') || ' ' || COALESCE(pi.last_name, '')), u.user_name, 'Unassigned') as assignee_name"))
            ->orderByDesc('t.updated_at')->get();

        $logs = DB::table('internal_task_activity_logs as l')
            ->join('internal_tasks as t', 't.id', '=', 'l.task_id')
            ->leftJoin('users', 'users.id', '=', 'l.user_id')
            ->where('t.project_id', $id)
            ->select('l.*', 't.title as task_title', 'users.user_name')
            ->orderByDesc('l.id')->limit(30)->get();

        $notes = DB::table('internal_activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('personal_informations as pi', 'pi.user_id', '=', 'a.user_id')
            ->where('a.project_id', $id)
            ->select('a.*', DB::raw("COALESCE(NULLIF(pi.nick_name, ''), TRIM(COALESCE(pi.first_name, '') || ' ' || COALESCE(pi.last_name, '')), u.user_name, 'System') as nama"))
            ->orderByDesc('a.id')->limit(25)->get();

        $lastStatusBy = $project->last_status_by
            ? (DB::table('users as u')->leftJoin('personal_informations as p', 'p.user_id', '=', 'u.id')
                ->where('u.id', $project->last_status_by)
                ->select(DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), u.user_name) as nama"))
                ->value('nama') ?: ('User #'.$project->last_status_by))
            : null;

        $hasCredential = ! empty($project->account_password);

        return view('internal-app.project.show', compact('project', 'members', 'tasks', 'logs', 'notes', 'lastStatusBy', 'hasCredential'));
    }

    public function note(Request $request, $id)
    {
        $project = DB::table('internal_projects')->find($id);
        abort_unless($project, 404);
        $v = $request->validate(['note' => 'required|string|max:2000']);

        DB::table('internal_activity_logs')->insert([
            'user_id' => InternalAppService::userId() ?: null,
            'project_id' => $id,
            'task_id' => null,
            'type' => 'note',
            'action' => 'note',
            'description' => $v['note'],
            'meta' => json_encode(['source' => 'project_note']),
            'activity_date' => date('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('internal_projects')->where('id', $id)->update([
            'last_status' => $v['note'],
            'last_status_at' => now(),
            'last_status_by' => InternalAppService::userId() ?: null,
            'updated_at' => now(),
        ]);

        return response()->json(['status' => true, 'message' => 'Catatan / status project disimpan.']);
    }

    public function credential($id)
    {
        $project = DB::table('internal_projects')->find($id);
        abort_unless($project, 404);
        $password = null;
        if (! empty($project->account_password)) {
            try {
                $password = Crypt::decryptString($project->account_password);
            } catch (\Throwable $e) {
                $password = null;
            }
        }

        return response()->json(['status' => true, 'password' => $password]);
    }

    protected function projectMeta(array $v): array
    {
        $meta = [
            'daerah' => $v['daerah'] ?? null,
            'instansi' => $v['instansi'] ?? null,
            'production_url' => $v['production_url'] ?? null,
            'development_url' => $v['development_url'] ?? null,
            'hosting_provider' => $v['hosting_provider'] ?? null,
            'account_type' => $v['account_type'] ?? null,
            'account_email' => $v['account_email'] ?? null,
            'account_username' => $v['account_username'] ?? null,
            'notes' => $v['notes'] ?? null,
        ];
        if (! empty($v['account_password'])) {
            $meta['account_password'] = Crypt::encryptString($v['account_password']);
        }

        return $meta;
    }

    public function destroy($id)
    {
        $projectName = DB::table('internal_projects')->where('id', $id)->value('name');
        DB::table('internal_projects')->where('id', $id)->delete();
        InternalAppService::logActivity(null, null, 'project', 'deleted', 'Project dihapus: '.($projectName ?: ('#'.$id)));

        return response()->json(['status' => true, 'message' => 'Proyek dihapus.']);
    }

    public function board(Request $request)
    {
        $projects = $this->projectsQuery()->get();
        $projectId = (int) $request->input('project_id', $projects->first()->id ?? 0);
        $project = $projectId ? DB::table('internal_projects')->find($projectId) : null;
        $tasks = $projectId ? $this->tasksQuery($projectId)->get() : collect();
        $grouped = collect($this->statuses)->mapWithKeys(fn ($s) => [$s => $tasks->where('status', $s)->values()]);
        $users = InternalAppService::userOptions();

        return view('internal-app.project.board', [
            'projects' => $projects, 'project' => $project, 'tasks' => $tasks, 'grouped' => $grouped,
            'users' => $users, 'statuses' => $this->statuses,
        ]);
    }

    public function gantt(Request $request)
    {
        $projects = $this->projectsQuery()->get();
        $projectId = (int) $request->input('project_id', $projects->first()->id ?? 0);
        $project = $projectId ? DB::table('internal_projects')->find($projectId) : null;
        $tasks = $projectId ? $this->tasksQuery($projectId)->whereNotNull('t.start_date')->whereNotNull('t.due_date')->get() : collect();

        return view('internal-app.project.gantt', compact('projects', 'project', 'tasks'));
    }

    public function task($id)
    {
        $task = $this->tasksQuery(null)->where('t.id', $id)->first();
        abort_unless($task, 404);
        $project = DB::table('internal_projects')->find($task->project_id);
        $subtasks = DB::table('internal_subtasks')->where('task_id', $id)->orderBy('position')->get();
        $comments = DB::table('internal_task_comments as c')
            ->leftJoin('users', 'users.id', '=', 'c.user_id')
            ->leftJoin('personal_informations as p', 'p.user_id', '=', 'users.id')
            ->where('c.task_id', $id)
            ->select('c.*', DB::raw("COALESCE(NULLIF(p.nick_name, ''), TRIM(COALESCE(p.first_name, '') || ' ' || COALESCE(p.last_name, '')), users.user_name, 'System') as nama"))
            ->orderBy('c.id')->get();
        $worklogs = DB::table('internal_task_worklogs as w')
            ->leftJoin('users', 'users.id', '=', 'w.user_id')
            ->where('w.task_id', $id)
            ->select('w.*', 'users.user_name')->orderByDesc('w.id')->get();
        $attachments = DB::table('internal_task_attachments as a')
            ->leftJoin('users', 'users.id', '=', 'a.user_id')
            ->where('a.task_id', $id)->select('a.*', 'users.user_name')->orderByDesc('a.id')->get();
        $logs = DB::table('internal_task_activity_logs as l')
            ->leftJoin('users', 'users.id', '=', 'l.user_id')
            ->where('l.task_id', $id)->select('l.*', 'users.user_name')->orderByDesc('l.id')->get();
        $users = InternalAppService::userOptions();
        InternalAppService::touchPresence((int) $id);

        return view('internal-app.project.task', [
            'task' => $task, 'project' => $project, 'subtasks' => $subtasks, 'comments' => $comments,
            'worklogs' => $worklogs, 'attachments' => $attachments, 'logs' => $logs, 'users' => $users,
            'statuses' => $this->statuses, 'priorities' => $this->priorities,
        ]);
    }

    public function taskStore(Request $request)
    {
        $v = $this->validateTask($request);
        $max = (int) DB::table('internal_tasks')->where('project_id', $v['project_id'])->where('status', $v['status'] ?? 'backlog')->max('position');
        $id = DB::table('internal_tasks')->insertGetId($v + [
            'reporter_id' => InternalAppService::userId(),
            'progress' => 0, 'position' => $max + 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        InternalAppService::activity($id, 'created', 'Task dibuat');
        InternalAppService::refreshProjectProgress((int) $v['project_id']);

        return response()->json(['status' => true, 'message' => 'Task berhasil dibuat.', 'id' => $id]);
    }

    public function taskUpdate(Request $request, $id)
    {
        $task = DB::table('internal_tasks')->find($id);
        abort_unless($task, 404);
        $v = $this->validateTask($request);
        $v['progress'] = $this->progressFromStatus($v['status'] ?? $task->status, (int) $task->progress);
        $v['updated_at'] = now();
        DB::table('internal_tasks')->where('id', $id)->update($v);
        InternalAppService::activity($id, 'updated', 'Task diperbarui');
        InternalAppService::refreshProjectProgress((int) $task->project_id);

        return response()->json(['status' => true, 'message' => 'Task diperbarui.']);
    }

    public function taskMove(Request $request, $id)
    {
        $v = $request->validate([
            'status' => 'required|in:backlog,todo,in_progress,in_review,done',
            'position' => 'nullable|integer',
        ]);
        $task = DB::table('internal_tasks')->find($id);
        abort_unless($task, 404);
        $progress = $this->progressFromStatus($v['status'], (int) $task->progress);
        DB::table('internal_tasks')->where('id', $id)->update([
            'status' => $v['status'], 'progress' => $progress,
            'position' => $v['position'] ?? $task->position, 'updated_at' => now(),
        ]);
        InternalAppService::activity($id, 'status', 'Status diubah menjadi '.$v['status']);
        InternalAppService::refreshProjectProgress((int) $task->project_id);

        return response()->json(['status' => true, 'message' => 'Status task diperbarui.']);
    }

    public function subtaskStore(Request $request, $id)
    {
        $v = $request->validate(['title' => 'required|string|max:200']);
        $task = DB::table('internal_tasks')->find($id);
        abort_unless($task, 404);
        $pos = (int) DB::table('internal_subtasks')->where('task_id', $id)->max('position');
        DB::table('internal_subtasks')->insert([
            'task_id' => $id, 'title' => $v['title'], 'is_done' => false, 'position' => $pos + 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->syncProgress($id, (int) $task->project_id);

        return response()->json(['status' => true, 'message' => 'Sub-task ditambahkan.']);
    }

    public function subtaskToggle($id)
    {
        $sub = DB::table('internal_subtasks')->find($id);
        abort_unless($sub, 404);
        DB::table('internal_subtasks')->where('id', $id)->update(['is_done' => ! $sub->is_done, 'updated_at' => now()]);
        $task = DB::table('internal_tasks')->find($sub->task_id);
        $this->syncProgress((int) $sub->task_id, (int) $task->project_id);

        return response()->json(['status' => true, 'message' => 'Sub-task diperbarui.']);
    }

    public function subtaskDelete($id)
    {
        $sub = DB::table('internal_subtasks')->find($id);
        abort_unless($sub, 404);
        DB::table('internal_subtasks')->where('id', $id)->delete();
        $task = DB::table('internal_tasks')->find($sub->task_id);
        $this->syncProgress((int) $sub->task_id, (int) $task->project_id);

        return response()->json(['status' => true, 'message' => 'Sub-task dihapus.']);
    }

    public function commentStore(Request $request, $id)
    {
        $v = $request->validate(['body' => 'required|string|max:5000']);
        preg_match_all('/@([A-Za-z0-9_.-]+)/', $v['body'], $m);
        DB::table('internal_task_comments')->insert([
            'task_id' => $id, 'user_id' => InternalAppService::userId(),
            'body' => $v['body'], 'mentions' => json_encode(array_values(array_unique($m[1] ?? []))),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        InternalAppService::activity((int) $id, 'comment', 'Komentar ditambahkan');

        return response()->json(['status' => true, 'message' => 'Komentar dikirim.']);
    }

    public function worklogStart($id)
    {
        $open = DB::table('internal_task_worklogs')->where('task_id', $id)
            ->where('user_id', InternalAppService::userId())->whereNull('ended_at')->first();
        if ($open) {
            return response()->json(['status' => true, 'message' => 'Timer sudah berjalan.', 'id' => $open->id]);
        }
        $wid = DB::table('internal_task_worklogs')->insertGetId([
            'task_id' => $id, 'user_id' => InternalAppService::userId(),
            'started_at' => now(), 'duration_minutes' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        InternalAppService::touchPresence((int) $id);

        return response()->json(['status' => true, 'message' => 'Timer dimulai.', 'id' => $wid]);
    }

    public function worklogStop($id)
    {
        $log = DB::table('internal_task_worklogs')->where('task_id', $id)
            ->where('user_id', InternalAppService::userId())->whereNull('ended_at')->orderByDesc('id')->first();
        if (! $log) {
            return response()->json(['status' => false, 'message' => 'Tidak ada timer berjalan.'], 422);
        }
        $minutes = max(1, (int) round((time() - strtotime($log->started_at)) / 60));
        DB::table('internal_task_worklogs')->where('id', $log->id)->update([
            'ended_at' => now(), 'duration_minutes' => $minutes, 'updated_at' => now(),
        ]);
        InternalAppService::activity((int) $id, 'worklog', 'Worklog '.$minutes.' menit');

        return response()->json(['status' => true, 'message' => 'Timer dihentikan ('.$minutes.' menit).']);
    }

    public function worklogStore(Request $request, $id)
    {
        $v = $request->validate([
            'duration_minutes' => 'required|integer|min:1|max:1440',
            'note' => 'nullable|string|max:1000',
        ]);
        DB::table('internal_task_worklogs')->insert([
            'task_id' => $id, 'user_id' => InternalAppService::userId(),
            'started_at' => now(), 'ended_at' => now(), 'duration_minutes' => $v['duration_minutes'],
            'note' => $v['note'] ?? null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        InternalAppService::activity((int) $id, 'worklog', 'Worklog manual '.$v['duration_minutes'].' menit');

        return response()->json(['status' => true, 'message' => 'Worklog disimpan.']);
    }

    public function attachmentStore(Request $request, $id)
    {
        $request->validate(['file' => 'required|file|max:20480']);
        $file = $request->file('file');
        $dir = public_path('storages/internal-app/tasks');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = date('YmdHis').'-'.preg_replace('/[^A-Za-z0-9._-]/', '-', $file->getClientOriginalName());
        $file->move($dir, $name);
        $rel = '/storages/internal-app/tasks/'.$name;
        DB::table('internal_task_attachments')->insert([
            'task_id' => $id, 'user_id' => InternalAppService::userId(),
            'file_path' => $rel, 'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(), 'size' => $file->getSize(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        InternalAppService::activity((int) $id, 'attachment', 'Lampiran diunggah: '.$file->getClientOriginalName());

        return response()->json(['status' => true, 'message' => 'Lampiran diunggah.']);
    }

    public function presence()
    {
        InternalAppService::touchPresence();

        return response()->json(['status' => true]);
    }

    protected function validateTask(Request $request): array
    {
        $v = $request->validate([
            'project_id' => 'required|integer|exists:internal_projects,id',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string',
            'status' => 'required|in:backlog,todo,in_progress,in_review,done',
            'priority' => 'required|in:low,medium,high,urgent',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date', 'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'labels' => 'nullable|string|max:500',
            'is_milestone' => 'nullable|boolean',
        ]);
        $v['is_milestone'] = $request->boolean('is_milestone');

        return $v;
    }

    protected function progressFromStatus(string $status, int $current): int
    {
        return match ($status) {
            'done' => 100,
            'in_review' => max($current, 80),
            'in_progress' => max($current, 25),
            default => $current,
        };
    }

    protected function syncProgress(int $taskId, int $projectId): void
    {
        $progress = InternalAppService::taskProgress($taskId);
        DB::table('internal_tasks')->where('id', $taskId)->update(['progress' => $progress, 'updated_at' => now()]);
        InternalAppService::refreshProjectProgress($projectId);
    }

    protected function projectsQuery()
    {
        return DB::table('internal_projects as p')
            ->leftJoin('users as owner', 'owner.id', '=', 'p.owner_id')
            ->select('p.*', 'owner.user_name as owner_name')
            ->orderByDesc('p.updated_at');
    }

    protected function tasksQuery(?int $projectId)
    {
        $q = DB::table('internal_tasks as t')
            ->leftJoin('internal_projects as p', 'p.id', '=', 't.project_id')
            ->leftJoin('users as u', 'u.id', '=', 't.assignee_id')
            ->leftJoin('personal_informations as pi', 'pi.user_id', '=', 't.assignee_id')
            ->select(
                't.*', 'p.name as project_name',
                DB::raw("COALESCE(NULLIF(pi.nick_name, ''), TRIM(COALESCE(pi.first_name, '') || ' ' || COALESCE(pi.last_name, '')), u.user_name, 'Unassigned') as assignee_name")
            )
            ->orderBy('t.position')->orderByDesc('t.id');
        if ($projectId) {
            $q->where('t.project_id', $projectId);
        }

        return $q;
    }
}
