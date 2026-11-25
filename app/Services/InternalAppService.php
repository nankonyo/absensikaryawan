<?php

namespace App\Services;

use App\Models\System\Installation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InternalAppService
{
    public static function userId(): int
    {
        return (int) (auth()->id() ?: session('user_id') ?: 0);
    }

    public static function user(): ?object
    {
        $id = self::userId();

        return $id > 0 ? DB::table('users')->whereNull('deleted_at')->find($id) : null;
    }

    public static function displayName(int $userId): string
    {
        $row = DB::table('users')
            ->leftJoin('personal_informations', 'personal_informations.user_id', '=', 'users.id')
            ->where('users.id', $userId)
            ->select(
                'users.user_name',
                DB::raw("COALESCE(NULLIF(personal_informations.nick_name, ''), TRIM(COALESCE(personal_informations.first_name, '') || ' ' || COALESCE(personal_informations.last_name, '')), users.user_name) as nama")
            )
            ->first();

        return $row->nama ?? ('User #'.$userId);
    }

    public static function userOptions(): array
    {
        return DB::table('users')
            ->leftJoin('personal_informations', 'personal_informations.user_id', '=', 'users.id')
            ->whereNull('users.deleted_at')
            ->where('users.is_active', true)
            ->select(
                'users.id',
                'users.user_name',
                DB::raw("COALESCE(NULLIF(personal_informations.nick_name, ''), TRIM(COALESCE(personal_informations.first_name, '') || ' ' || COALESCE(personal_informations.last_name, '')), users.user_name) as nama")
            )
            ->orderBy('nama')
            ->get()
            ->map(fn ($u) => ['id' => (int) $u->id, 'name' => $u->nama])
            ->all();
    }

    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function touchPresence(?int $taskId = null): void
    {
        $userId = self::userId();
        if ($userId <= 0) {
            return;
        }
        $exists = DB::table('internal_user_presence')->where('user_id', $userId)->first();
        $data = [
            'last_seen_at' => now(),
            'status' => 'online',
            'current_task_id' => $taskId,
            'updated_at' => now(),
        ];
        if ($exists) {
            DB::table('internal_user_presence')->where('user_id', $userId)->update($data);
        } else {
            DB::table('internal_user_presence')->insert($data + [
                'user_id' => $userId,
                'created_at' => now(),
            ]);
        }
    }

    public static function activity(int $taskId, string $action, string $description, array $meta = []): void
    {
        DB::table('internal_task_activity_logs')->insert([
            'task_id' => $taskId,
            'user_id' => self::userId() ?: null,
            'action' => $action,
            'description' => $description,
            'meta' => json_encode($meta),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $task = DB::table('internal_tasks')->find($taskId);
        self::logActivity($task && $task->project_id ? (int) $task->project_id : null, $taskId, 'task', $action, $description, $meta);
    }

    public static function projectActivity(int $projectId, string $action, string $description, array $meta = []): void
    {
        self::logActivity($projectId, null, 'project', $action, $description, $meta);
    }

    public static function logActivity(?int $projectId, ?int $taskId, string $type, string $action, string $description, array $meta = []): void
    {
        if (! Schema::hasTable('internal_activity_logs')) {
            return;
        }
        DB::table('internal_activity_logs')->insert([
            'user_id' => self::userId() ?: null,
            'project_id' => $projectId,
            'task_id' => $taskId,
            'type' => $type,
            'action' => $action,
            'description' => $description,
            'meta' => $meta ? json_encode($meta) : null,
            'activity_date' => date('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($projectId && Schema::hasColumn('internal_projects', 'last_status')) {
            DB::table('internal_projects')->where('id', $projectId)->update([
                'last_status' => $description,
                'last_status_at' => now(),
                'last_status_by' => self::userId() ?: null,
                'updated_at' => now(),
            ]);
        }
    }

    public static function taskAssigneeIds(int $taskId): array
    {
        if (! Schema::hasTable('internal_task_assignees')) {
            return [];
        }

        return DB::table('internal_task_assignees')
            ->where('task_id', $taskId)
            ->orderBy('id')
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function syncTaskAssignees(int $taskId, array $userIds): void
    {
        if (! Schema::hasTable('internal_task_assignees')) {
            Installation::executeSchemaDatabase();
        }

        if (! Schema::hasTable('internal_task_assignees')) {
            return;
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        DB::table('internal_task_assignees')->where('task_id', $taskId)->delete();

        foreach ($ids as $userId) {
            DB::table('internal_task_assignees')->insert([
                'task_id' => $taskId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('internal_tasks')->where('id', $taskId)->update([
            'assignee_id' => $ids[0] ?? null,
            'updated_at' => now(),
        ]);
    }

    public static function taskProgress(int $taskId): int
    {
        $total = DB::table('internal_subtasks')->where('task_id', $taskId)->count();
        if ($total === 0) {
            return (int) (DB::table('internal_tasks')->where('id', $taskId)->value('progress') ?: 0);
        }
        $done = DB::table('internal_subtasks')->where('task_id', $taskId)->where('is_done', true)->count();

        return (int) round(($done / $total) * 100);
    }

    public static function pendingApprovalCount(): int
    {
        return DB::table('internal_leave_requests')->where('status', 'pending')->count()
            + DB::table('internal_overtime_requests')->where('status', 'pending')->count()
            + DB::table('internal_attendance_manual')->where('status', 'pending')->count();
    }

    public static function setting(string $key, $default = null)
    {
        $row = DB::table('internal_app_settings')->where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    public static function putSetting(string $key, $value): void
    {
        DB::table('internal_app_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public static function workDays(): array
    {
        $raw = (string) self::setting('work_days', '1,2,3,4,5');
        $days = array_values(array_filter(array_map('intval', explode(',', $raw)), fn ($d) => $d >= 1 && $d <= 7));

        return $days ?: [1, 2, 3, 4, 5];
    }

    public static function holidayDates(string $from, string $to): array
    {
        return DB::table('internal_holidays')
            ->where('is_active', true)
            ->whereBetween('holiday_date', [$from, $to])
            ->pluck('holiday_date')
            ->map(fn ($d) => date('Y-m-d', strtotime($d)))
            ->all();
    }

    public static function isHoliday(string $date): bool
    {
        return DB::table('internal_holidays')
            ->where('is_active', true)
            ->where('holiday_date', $date)
            ->exists();
    }

    public static function refreshProjectProgress(int $projectId): void
    {
        $total = DB::table('internal_tasks')->where('project_id', $projectId)->whereNull('parent_id')->count();
        if ($total === 0) {
            DB::table('internal_projects')->where('id', $projectId)->update(['progress' => 0, 'updated_at' => now()]);

            return;
        }
        $sum = (int) DB::table('internal_tasks')->where('project_id', $projectId)->whereNull('parent_id')->sum('progress');
        DB::table('internal_projects')->where('id', $projectId)->update([
            'progress' => (int) round($sum / $total),
            'updated_at' => now(),
        ]);
    }
}
