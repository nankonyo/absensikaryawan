<?php

namespace App\Http\Controllers\Api;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Services\AttendanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Maatwebsite\Excel\Facades\Excel;

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
        'present_days',
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

    public function downloadUrl(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Laporan absensi hanya untuk admin.');

        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'format' => ['required', 'in:pdf,excel'],
        ]);

        $url = URL::temporarySignedRoute(
            'mobile.reports.attendance.download',
            now()->addMinutes(10),
            $data
        );

        return response()->json(['url' => $url]);
    }

    public function download(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'format' => ['required', 'in:pdf,excel'],
        ]);

        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $report = app(AttendanceReportService::class)->build($startDate, $endDate);
        $fileName = 'laporan-absensi-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d');

        if ($data['format'] === 'pdf') {
            return Pdf::loadView('reports.attendance-pdf', ['report' => $report])
                ->setPaper('a4', 'landscape')
                ->download($fileName.'.pdf');
        }

        return Excel::download(new AttendanceReportExport($report), $fileName.'.xlsx');
    }
}
