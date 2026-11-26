<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeFeature($request, 'internalapp.project.project');

        $search = trim((string) $request->query('q'));

        $projects = DB::table('internal_projects as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.owner_id')
            ->when($search !== '', fn ($query) => $query->where('p.name', 'like', "%{$search}%"))
            ->select('p.*', 'u.name as owner_name')
            ->selectSub(
                DB::table('internal_tasks')->selectRaw('count(*)')->whereColumn('project_id', 'p.id'),
                'tasks_count'
            )
            ->selectSub(
                DB::table('internal_tasks')->selectRaw('count(*)')->whereColumn('project_id', 'p.id')->where('status', 'done'),
                'tasks_done'
            )
            ->orderByDesc('p.updated_at')
            ->paginate((int) ($request->query('per_page') ?: 15));

        $payload = $projects->toArray();
        $payload['users'] = \App\Services\InternalAppService::userOptions();

        return response()->json($payload);
    }

    public function store(Request $request)
    {
        $this->authorizeFeature($request, 'internalapp.project.project', 'create');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:planning,in_progress,completed,overdue,on_hold'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $user = $request->user();
        $id = DB::table('internal_projects')->insertGetId([
            'code' => $data['code'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'owner_id' => $user->id,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? 'planning',
            'progress' => 0,
            'color' => $data['color'] ?? '#D34B52',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('internal_project_members')->insertOrIgnore([
            'project_id' => $id,
            'user_id' => $user->id,
            'role' => 'owner',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('internal_activity_logs')->insert([
            'user_id' => $user->id,
            'project_id' => $id,
            'task_id' => null,
            'type' => 'project',
            'action' => 'created',
            'description' => 'Project dibuat: '.$data['name'],
            'activity_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Project berhasil dibuat.',
            'id' => $id,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeFeature($request, 'internalapp.project.project', 'update');

        $project = DB::table('internal_projects')->find($id);
        abort_unless($project, 404);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'in:planning,in_progress,completed,overdue,on_hold'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        DB::table('internal_projects')->where('id', $id)->update(array_filter([
            'name' => $data['name'] ?? null,
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'status' => $data['status'] ?? null,
            'color' => $data['color'] ?? null,
            'updated_at' => now(),
        ], fn ($value) => ! is_null($value)));

        \App\Services\InternalAppService::projectActivity((int) $id, 'updated', 'Project diperbarui via mobile');

        return response()->json(['status' => true, 'message' => 'Project diperbarui.']);
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeFeature($request, 'internalapp.project.project', 'delete');

        $name = DB::table('internal_projects')->where('id', $id)->value('name');
        abort_unless($name, 404);

        DB::table('internal_projects')->where('id', $id)->delete();
        \App\Services\InternalAppService::logActivity(null, null, 'project', 'deleted', 'Project dihapus via mobile: '.$name);

        return response()->json(['status' => true, 'message' => 'Project dihapus.']);
    }

    public function storeTask(Request $request)
    {
        $this->authorizeFeature($request, 'internalapp.project.kanban', 'create');

        $data = $this->validateTask($request, true);
        $assigneeIds = array_values(array_unique(array_map('intval', (array) ($data['assignee_ids'] ?? []))));
        unset($data['assignee_ids']);
        $data['assignee_id'] = $assigneeIds[0] ?? null;
        $data['status'] = $data['status'] ?? 'backlog';
        $data['priority'] = $data['priority'] ?? 'medium';
        $data['reporter_id'] = $request->user()->id;
        $data['progress'] = 0;
        $data['position'] = (int) DB::table('internal_tasks')->where('project_id', $data['project_id'])->max('position') + 1;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('internal_tasks')->insertGetId($data);
        \App\Services\InternalAppService::syncTaskAssignees((int) $id, $assigneeIds);
        \App\Services\InternalAppService::activity((int) $id, 'created', 'Task dibuat via mobile');
        \App\Services\InternalAppService::refreshProjectProgress((int) $data['project_id']);

        return response()->json(['status' => true, 'message' => 'Task berhasil dibuat.', 'id' => $id]);
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeFeature($request, 'internalapp.project.project');

        $project = DB::table('internal_projects as p')
            ->leftJoin('users as u', 'u.id', '=', 'p.owner_id')
            ->where('p.id', $id)
            ->select('p.*', 'u.name as owner_name')
            ->first();

        abort_unless($project, 404);

        $tasks = DB::table('internal_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.assignee_id')
            ->where('t.project_id', $id)
            ->select('t.*', 'u.name as assignee_name')
            ->orderBy('t.position')
            ->orderBy('t.id')
            ->get();

        $members = DB::table('internal_project_members as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->where('m.project_id', $id)
            ->select('m.role', 'u.id', 'u.name', 'u.email')
            ->get();

        $users = \App\Services\InternalAppService::userOptions();
        $userNames = collect($users)->pluck('name', 'id');

        $tasks = $tasks->map(function ($task) use ($userNames) {
            $assigneeIds = \App\Services\InternalAppService::taskAssigneeIds((int) $task->id);
            $task->assignee_ids = $assigneeIds;
            $task->assignee_names = collect($assigneeIds)->map(fn ($uid) => $userNames[$uid] ?? null)->filter()->values()->all();

            return $task;
        });

        return response()->json([
            'project' => $project,
            'tasks' => $tasks,
            'members' => $members,
            'available_users' => $users,
        ]);
    }

    public function tasks(Request $request, int $id)
    {
        $this->authorizeFeature($request, 'internalapp.project.project');

        $tasks = DB::table('internal_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.assignee_id')
            ->where('t.project_id', $id)
            ->select('t.*', 'u.name as assignee_name')
            ->orderBy('t.position')
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function kanban(Request $request)
    {
        $this->authorizeFeature($request, 'internalapp.project.kanban');

        $projectId = $request->integer('project_id') ?: null;

        $tasks = DB::table('internal_tasks as t')
            ->leftJoin('users as u', 'u.id', '=', 't.assignee_id')
            ->leftJoin('internal_projects as p', 'p.id', '=', 't.project_id')
            ->when($projectId, fn ($query) => $query->where('t.project_id', $projectId))
            ->select('t.*', 'u.name as assignee_name', 'p.name as project_name')
            ->orderBy('t.position')
            ->get();

        $statuses = ['backlog', 'todo', 'in_progress', 'in_review', 'done'];

        $board = [];
        foreach ($statuses as $status) {
            $board[$status] = $tasks->where('status', $status)->values();
        }

        return response()->json([
            'statuses' => $statuses,
            'board' => $board,
            'projects' => DB::table('internal_projects')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function gantt(Request $request)
    {
        $this->authorizeFeature($request, 'internalapp.project.gantt');

        $projectId = $request->integer('project_id') ?: null;

        $tasks = DB::table('internal_tasks as t')
            ->leftJoin('internal_projects as p', 'p.id', '=', 't.project_id')
            ->when($projectId, fn ($query) => $query->where('t.project_id', $projectId))
            ->whereNotNull('t.start_date')
            ->whereNotNull('t.due_date')
            ->select('t.id', 't.title', 't.status', 't.progress', 't.start_date', 't.due_date', 'p.name as project_name')
            ->orderBy('t.start_date')
            ->get();

        return response()->json([
            'tasks' => $tasks,
            'projects' => DB::table('internal_projects')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function updateMembers(Request $request, int $id)
    {
        $this->authorizeFeature($request, 'internalapp.project.project', 'update');

        $data = $request->validate([
            'members' => ['nullable', 'array'],
            'members.*' => ['integer', 'exists:users,id'],
        ]);

        $project = DB::table('internal_projects')->find($id);
        abort_unless($project, 404);

        $memberIds = array_values(array_unique(array_map('intval', (array) ($data['members'] ?? []))));
        if ((int) $project->owner_id > 0) {
            $memberIds[] = (int) $project->owner_id;
        }
        $memberIds = array_values(array_unique($memberIds));

        DB::table('internal_project_members')->where('project_id', $id)->where('role', '<>', 'owner')->delete();

        foreach ($memberIds as $userId) {
            DB::table('internal_project_members')->updateOrInsert(
                ['project_id' => $id, 'user_id' => $userId],
                [
                    'role' => $userId === (int) $project->owner_id ? 'owner' : 'member',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        \App\Services\InternalAppService::projectActivity((int) $id, 'updated', 'Tim project diperbarui ('.count($memberIds).' anggota).');

        return response()->json(['status' => true, 'message' => 'Tim project diperbarui.']);
    }

    public function updateTask(Request $request, int $id)
    {
        $this->authorizeFeature($request, 'internalapp.project.kanban', 'update');

        $data = $this->validateTask($request, false, $id);
        $task = DB::table('internal_tasks')->find($id);
        abort_unless($task, 404);

        $assigneeIds = array_key_exists('assignee_ids', $data)
            ? array_values(array_unique(array_map('intval', (array) $data['assignee_ids'])))
            : null;
        unset($data['assignee_ids']);

        $progress = $data['progress'] ?? $task->progress;
        if (isset($data['status'])) {
            $progress = match ($data['status']) {
                'done' => 100,
                'in_review' => 80,
                'in_progress' => 40,
                default => $progress,
            };
        }

        $update = array_filter(array_merge($data, [
            'progress' => $progress,
            'assignee_id' => $assigneeIds !== null ? ($assigneeIds[0] ?? null) : null,
            'updated_at' => now(),
        ]), fn ($value) => ! is_null($value));

        DB::table('internal_tasks')->where('id', $id)->update($update);

        if ($assigneeIds !== null) {
            \App\Services\InternalAppService::syncTaskAssignees($id, $assigneeIds);
        }

        \App\Services\InternalAppService::activity($id, 'updated', 'Task diperbarui via mobile');
        \App\Services\InternalAppService::refreshProjectProgress((int) $task->project_id);

        $task = DB::table('internal_tasks')->find($id);
        $task->assignee_ids = \App\Services\InternalAppService::taskAssigneeIds($id);

        return response()->json(['message' => 'Task diperbarui.', 'task' => $task]);
    }

    public function destroyTask(Request $request, int $id)
    {
        $this->authorizeFeature($request, 'internalapp.project.kanban', 'delete');

        $task = DB::table('internal_tasks')->find($id);
        abort_unless($task, 404);

        DB::table('internal_tasks')->where('id', $id)->delete();
        \App\Services\InternalAppService::refreshProjectProgress((int) $task->project_id);

        return response()->json(['status' => true, 'message' => 'Task dihapus.']);
    }

    private function validateTask(Request $request, bool $creating = false, ?int $taskId = null): array
    {
        $required = $creating ? 'required' : 'sometimes';

        $data = $request->validate([
            'project_id' => [$creating ? 'required' : 'sometimes', 'integer', 'exists:internal_projects,id'],
            'title' => [$required, 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:backlog,todo,in_progress,in_review,done'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', 'exists:users,id'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0'],
            'labels' => ['nullable', 'string', 'max:500'],
            'is_milestone' => ['nullable', 'boolean'],
        ]);

        $data['is_milestone'] = $request->boolean('is_milestone');

        return $data;
    }

    private function authorizeFeature(Request $request, string $code, string $action = 'read'): void
    {
        abort_unless($request->user()?->canFeature($code, $action), 403, 'Akses tidak diizinkan.');
    }
}
