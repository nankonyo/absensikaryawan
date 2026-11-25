<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;

class AttendanceReportService
{
    /**
     * Monthly attendance summary used by the Excel report.
     */
    public function build(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        $holidays = Holiday::query()
            ->active()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn (Holiday $holiday) => $holiday->date->toDateString());

        $workDays = [];
        $weeklyHolidayDays = Holiday::weeklyDays();
        $isWorkday = fn (Carbon $day) => ! in_array((int) $day->dayOfWeek, $weeklyHolidayDays, true);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($isWorkday($day)) {
                $workDays[] = $day->copy();
            }
        }

        $users = User::query()
            ->with(['employeeSetting', 'role'])
            ->whereHas('role', fn ($query) => $query->whereNotIn('name', ['admin', 'direksi']))
            ->orderBy('id')
            ->get();

        $attendances = Attendance::query()
            ->with('attendanceReason')
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()
            ->groupBy('user_id');

        $leaves = Leave::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->groupBy('user_id');

        $rows = $users->map(function (User $user) use ($start, $end, $workDays, $holidays, $attendances, $leaves, $isWorkday) {
            $summary = [
                'employee_id' => $user->employee_id ?: $user->id,
                'name' => $user->name,
                'normal_work_days' => count($workDays),
                'absent' => 0,
                'late_hours' => 0,
                'duty_city' => 0,
                'wfh' => 0,
                'training' => 0,
                'duty_out_of_town' => 0,
                'permit' => 0,
                'leave' => 0,
                'sick_leave' => 0,
                'compensation_hours' => 0,
                'collective_leave' => 0,
                'company_holiday' => 0,
                'national_holiday' => 0,
                'present_days' => 0,
                'attendance_percentage' => 0.0,
                'attendance_grade' => '',
            ];

            $userAttendances = $attendances->get($user->id, collect())
                ->keyBy(fn (Attendance $attendance) => $attendance->date->toDateString());

            $userLeaves = [];
            foreach ($leaves->get($user->id, collect()) as $leave) {
                $from = $leave->start_date->copy()->startOfDay();
                $to = $leave->end_date->copy()->startOfDay();

                if ($from->lt($start)) {
                    $from = $start->copy();
                }

                if ($to->gt($end)) {
                    $to = $end->copy();
                }

                for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
                    if ($isWorkday($day)) {
                        $userLeaves[$day->toDateString()] = $leave->leaveType?->category ?? 'izin';
                    }
                }
            }

            foreach ($workDays as $day) {
                $date = $day->toDateString();
                $holiday = $holidays->get($date);

                if ($holiday) {
                    match ($holiday->type) {
                        'company' => $summary['company_holiday']++,
                        'collective' => $summary['collective_leave']++,
                        default => $summary['national_holiday']++,
                    };

                    continue;
                }

                if (isset($userLeaves[$date])) {
                    match ($userLeaves[$date]) {
                        'cuti' => $summary['leave']++,
                        'sakit' => $summary['sick_leave']++,
                        default => $summary['permit']++,
                    };

                    continue;
                }

                $attendance = $userAttendances->get($date);

                if ($attendance && ($attendance->check_in || $attendance->check_out)) {
                    $summary['present_days']++;
                    $reason = strtolower((string) $attendance->attendanceReason?->name);

                    if (str_contains($reason, 'wfh')) {
                        $summary['wfh']++;
                    } elseif (str_contains($reason, 'dalam kota')) {
                        $summary['duty_city']++;
                    } elseif (str_contains($reason, 'pelatihan')) {
                        $summary['training']++;
                    } elseif (str_contains($reason, 'keluar kota')) {
                        $summary['duty_out_of_town']++;
                    } elseif ($user->work_mode === 'WFH') {
                        $summary['wfh']++;
                    }

                    $lateMinutes = (int) $attendance->late_duration;
                    if ($lateMinutes > 0) {
                        // Setiap keterlambatan dihitung minimal 1 jam, lalu
                        // dibulatkan ke atas per jam (75 menit => 2 jam).
                        $summary['late_hours'] += max(1, (int) ceil($lateMinutes / 60));
                    }

                    continue;
                }

                if ($day->lte(now()->startOfDay())) {
                    $summary['absent']++;
                }
            }

            $summary['actual_work_days'] = max(
                0,
                $summary['normal_work_days']
                    - $summary['absent']
                    - $summary['permit']
                    - $summary['sick_leave']
                    - $summary['collective_leave']
                    - $summary['leave']
                    - $summary['national_holiday']
                    - $summary['company_holiday']
            );

            $percentage = $summary['normal_work_days'] > 0
                ? round(($summary['present_days'] / $summary['normal_work_days']) * 100, 1)
                : 0.0;

            $summary['attendance_percentage'] = $percentage;
            $summary['attendance_grade'] = self::attendanceGrade($percentage);

            return $summary;
        })->values()->all();

        return [
            'start_date' => $start,
            'end_date' => $end,
            'normal_work_days' => count($workDays),
            'rows' => $rows,
        ];
    }

    public static function attendanceGrade(float $percentage): string
    {
        if ($percentage > 91) {
            return 'Sangat Baik';
        }

        if ($percentage >= 71) {
            return 'Baik';
        }

        if ($percentage >= 51) {
            return 'Kurang';
        }

        return 'Sangat Kurang';
    }
}
