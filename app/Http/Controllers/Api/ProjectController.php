<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
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

        return response()->json($projects);
    }

    public function show(Request $request, int $id)
    {
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

        return response()->json([
            'project' => $project,
            'tasks' => $tasks,
            'members' => $members,
        ]);
    }

    public function tasks(Request $request, int $id)
    {
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

    public function updateTask(Request $request, int $id)
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:backlog,todo,in_progress,in_review,done'],
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = DB::table('internal_tasks')->find($id);
        abort_unless($task, 404);

        $progress = $data['progress'] ?? $task->progress;
        if (isset($data['status'])) {
            $progress = match ($data['status']) {
                'done' => 100,
                'in_review' => 80,
                'in_progress' => 40,
                default => $progress,
            };
        }

        DB::table('internal_tasks')->where('id', $id)->update(array_filter([
            'status' => $data['status'] ?? null,
            'progress' => $progress,
            'assignee_id' => $data['assignee_id'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'updated_at' => now(),
        ], fn ($value) => ! is_null($value)));

        return response()->json(['message' => 'Task diperbarui.', 'task' => DB::table('internal_tasks')->find($id)]);
    }
}
