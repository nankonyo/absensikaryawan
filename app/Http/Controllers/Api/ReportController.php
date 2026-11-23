<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const TOTALS = [
        'absent',
        'late_hours',
        'duty_city',
        'wfh',
        'training',
        'duty_out_of_town',
        'permit',
        'leave',
        'sick_leave',
        'compensation_hours',
        'collective_leave',
        'company_holiday',
        'national_holiday',
    ];

    public function attendance(Request $request, AttendanceReportService $service)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Laporan absensi hanya untuk admin.');

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $report = $service->build($startDate, $endDate);

        $rows = collect($report['rows'])->map(function (array $row) {
            $row['actual_work_days'] = max(
                0,
                $row['normal_work_days']
                    - $row['absent']
                    - $row['permit']
                    - $row['sick_leave']
                    - $row['collective_leave']
                    - $row['leave']
                    - $row['national_holiday']
                    - $row['company_holiday']
            );

            return $row;
        })->values();

        $totals = collect(self::TOTALS)->mapWithKeys(
            fn (string $key) => [$key => (int) $rows->sum($key)]
        )->all();

        return response()->json([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'normal_work_days' => $report['normal_work_days'],
            'employee_count' => $rows->count(),
            'rows' => $rows,
            'totals' => $totals,
        ]);
    }
}
