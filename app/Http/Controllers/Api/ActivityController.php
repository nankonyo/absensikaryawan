<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $scope = $request->query('scope', 'mine');

        $query = DB::table('internal_activity_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('internal_projects as p', 'p.id', '=', 'a.project_id')
            ->leftJoin('internal_tasks as t', 't.id', '=', 'a.task_id')
            ->select(
                'a.*',
                'u.name as user_name',
                'p.name as project_name',
                't.title as task_title'
            )
            ->orderByDesc('a.activity_date')
            ->orderByDesc('a.id');

        if ($scope === 'mine' || ! ($user->isAtasan() || $user->isAdmin())) {
            $query->where('a.user_id', $user->id);
        } elseif (! $user->canViewAllTeamAttendance()) {
            $query->whereIn('a.user_id', $user->descendantIds());
        }

        if ($request->filled('project_id')) {
            $query->where('a.project_id', $request->integer('project_id'));
        }

        if ($request->filled('from')) {
            $query->where('a.activity_date', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->where('a.activity_date', '<=', $request->query('to'));
        }

        return response()->json([
            'activities' => $query->paginate((int) ($request->query('per_page') ?: 20)),
            'projects' => DB::table('internal_projects')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => ['required', 'integer', 'exists:internal_projects,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', 'in:backlog,todo,in_progress,in_review,done'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'activity_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
        ]);

        $user = $request->user();

        $progress = match ($data['status']) {
            'done' => 100,
            'in_review' => 80,
            'in_progress' => 40,
            default => 0,
        };

        $taskId = DB::table('internal_tasks')->insertGetId([
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'priority' => $data['priority'],
            'assignee_id' => $user->id,
            'reporter_id' => $user->id,
            'due_date' => $data['due_date'] ?? null,
            'progress' => $progress,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('internal_project_members')->insertOrIgnore([
            'project_id' => $data['project_id'],
            'user_id' => $user->id,
            'role' => 'member',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('internal_activity_logs')->insert([
            'user_id' => $user->id,
            'project_id' => $data['project_id'],
            'task_id' => $taskId,
            'type' => 'task',
            'action' => 'created',
            'description' => $data['title'],
            'meta' => json_encode(['source' => 'mobile']),
            'activity_date' => $data['activity_date'] ?? now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Aktivitas berhasil disimpan.',
            'task_id' => $taskId,
        ], 201);
    }
}
