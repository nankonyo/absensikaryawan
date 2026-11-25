<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ActivityReportMailService
{
    public function send(string $frequency = 'weekly', ?Carbon $now = null): int
    {
        $now = $now ?: now();
        $start = $frequency === 'daily'
            ? $now->copy()->startOfDay()
            : $now->copy()->subDays(7)->startOfDay();
        $end = $now->copy()->endOfDay();

        $activities = DB::table('internal_activity_logs as a')
            ->leftJoin('internal_projects as p', 'p.id', '=', 'a.project_id')
            ->leftJoin('internal_tasks as t', 't.id', '=', 'a.task_id')
            ->whereNotNull('a.user_id')
            ->whereBetween('a.activity_date', [$start->toDateString(), $end->toDateString()])
            ->select(
                'a.id', 'a.user_id', 'a.activity_date', 'a.type', 'a.action', 'a.description',
                'p.name as project_name', 't.title as task_title'
            )
            ->orderBy('a.user_id')
            ->orderByDesc('a.id')
            ->get();

        if ($activities->isEmpty()) {
            return 0;
        }

        $userIds = $activities->pluck('user_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $users = DB::table('users as u')
            ->leftJoin('users as s', 's.id', '=', 'u.supervisor_id')
            ->whereIn('u.id', $userIds)
            ->get([
                'u.id', 'u.name', 'u.email', 'u.supervisor_id',
                's.name as supervisor_name', 's.email as supervisor_email',
            ])
            ->keyBy('id');

        $grouped = [];
        foreach ($activities as $activity) {
            $user = $users->get((int) $activity->user_id);
            if (! $user || ! $user->supervisor_id || ! $user->supervisor_email) {
                continue;
            }

            $supervisorId = (int) $user->supervisor_id;
            $grouped[$supervisorId]['supervisor'] = [
                'id' => $supervisorId,
                'name' => $user->supervisor_name ?: 'Atasan',
                'email' => $user->supervisor_email,
            ];
            $grouped[$supervisorId]['members'][$user->id]['name'] = $user->name;
            $grouped[$supervisorId]['members'][$user->id]['email'] = $user->email;
            $grouped[$supervisorId]['members'][$user->id]['activities'][] = $activity;
        }

        $sent = 0;
        $appName = (string) Setting::get('app_name', config('app.name'));

        foreach ($grouped as $group) {
            $supervisor = $group['supervisor'];
            $periodLabel = $frequency === 'daily'
                ? 'Harian · '.$now->translatedFormat('d F Y')
                : 'Mingguan · '.$start->translatedFormat('d M').' - '.$end->translatedFormat('d M Y');

            $subject = 'Laporan Aktivitas Tim - '.$periodLabel.' - '.$appName;
            $body = view('emails.activity-report', [
                'appName' => $appName,
                'supervisor' => $supervisor,
                'members' => $group['members'],
                'periodLabel' => $periodLabel,
                'startDate' => $start,
                'endDate' => $end,
            ])->render();

            $rowId = DB::table('internal_email_logs')->insertGetId([
                'user_id' => $supervisor['id'],
                'recipient_email' => $supervisor['email'],
                'recipient_name' => $supervisor['name'],
                'subject' => $subject,
                'body' => $body,
                'status' => 'pending',
                'period_from' => $start->toDateString(),
                'period_to' => $end->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            try {
                Mail::html($body, function ($message) use ($supervisor, $subject) {
                    $message->to($supervisor['email'], $supervisor['name'])->subject($subject);
                });

                DB::table('internal_email_logs')->where('id', $rowId)->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);
                $sent++;
            } catch (\Throwable $exception) {
                DB::table('internal_email_logs')->where('id', $rowId)->update([
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                    'updated_at' => now(),
                ]);
            }
        }

        return $sent;
    }
}
