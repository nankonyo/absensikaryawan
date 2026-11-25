<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Services\AttendanceReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->resolveFilters($request);

        $report = app(AttendanceReportService::class)->build(
            Carbon::parse($filters['start_date']),
            Carbon::parse($filters['end_date'])
        );

        return view('admin.reports.index', [
            'filterType' => $filters['filter_type'],
            'month' => $filters['month'],
            'semester' => $filters['semester'],
            'year' => $filters['year'],
            'startDate' => $filters['start_date'],
            'endDate' => $filters['end_date'],
            'report' => $report,
            'summary' => $this->summarize($report),
        ]);
    }

    /**
     * Show the attendance report as an inline PDF preview.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        [$pdf, $fileName] = $this->attendancePdf($validated['start_date'], $validated['end_date']);

        return $pdf->stream($fileName);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel',
        ]);

        return $this->downloadResponse(
            $validated['start_date'],
            $validated['end_date'],
            $validated['format']
        );
    }

    public function attendance(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel',
        ]);

        return $this->downloadResponse(
            $validated['start_date'],
            $validated['end_date'],
            $validated['format']
        );
    }

    private function downloadResponse(string $startDate, string $endDate, string $format)
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        if ($format === 'pdf') {
            [$pdf, $fileName] = $this->attendancePdf($startDate, $endDate);

            return $pdf->download($fileName);
        }

        $report = app(AttendanceReportService::class)->build($start, $end);
        $fileName = 'laporan-absensi-'.$start->format('Y-m-d').'-sd-'.$end->format('Y-m-d').'.xlsx';

        return Excel::download(new AttendanceReportExport($report), $fileName);
    }

    /**
     * Resolve the active filter into a concrete date range.
     *
     * @return array{filter_type: string, month: string, semester: int, year: int, start_date: string, end_date: string}
     */
    private function resolveFilters(Request $request): array
    {
        $filterType = (string) $request->query('filter_type', 'month');
        if (! in_array($filterType, ['month', 'range', 'semester', 'year'], true)) {
            $filterType = 'month';
        }

        $month = (string) $request->query('month');
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        try {
            $monthStart = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        } catch (\Throwable $exception) {
            $month = now()->format('Y-m');
            $monthStart = now()->startOfMonth();
        }

        $year = (int) $request->query('year', $monthStart->year);
        if ($year < 2000 || $year > 2100) {
            $year = now()->year;
        }

        $semester = (int) $request->query('semester', $monthStart->month <= 6 ? 1 : 2);
        if (! in_array($semester, [1, 2], true)) {
            $semester = now()->month <= 6 ? 1 : 2;
        }

        $startDate = $monthStart->toDateString();
        $endDate = $monthStart->copy()->endOfMonth()->toDateString();

        if ($filterType === 'range') {
            $start = $this->parseDate($request->query('start_date'));
            $end = $this->parseDate($request->query('end_date'));

            if ($start && $end && $end->gte($start)) {
                $startDate = $start->toDateString();
                $endDate = $end->toDateString();
            }
        }

        if ($filterType === 'semester') {
            $startMonth = $semester === 1 ? 1 : 7;
            $endMonth = $semester === 1 ? 6 : 12;

            $startDate = Carbon::create($year, $startMonth, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();
        }

        if ($filterType === 'year') {
            $startDate = Carbon::create($year, 1, 1)->startOfYear()->toDateString();
            $endDate = Carbon::create($year, 12, 31)->endOfYear()->toDateString();
        }

        return [
            'filter_type' => $filterType,
            'month' => $month,
            'semester' => $semester,
            'year' => $year,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * Aggregate the report rows for the totals shown below the table.
     */
    private function summarize(array $report): array
    {
        $rows = collect($report['rows']);

        $totalNormal = (int) $rows->sum('normal_work_days');
        $totalPresent = (int) $rows->sum('present_days');
        $percentage = $totalNormal > 0 ? round(($totalPresent / $totalNormal) * 100, 1) : 0.0;

        return [
            'total_normal_work_days' => $totalNormal,
            'total_present_days' => $totalPresent,
            'attendance_percentage' => $percentage,
            'attendance_grade' => AttendanceReportService::attendanceGrade($percentage),
            'totals' => [
                'normal_work_days' => $totalNormal,
                'actual_work_days' => (int) $rows->sum('actual_work_days'),
                'absent' => (int) $rows->sum('absent'),
                'late_hours' => (int) $rows->sum('late_hours'),
                'duty_city' => (int) $rows->sum('duty_city'),
                'wfh' => (int) $rows->sum('wfh'),
                'permit' => (int) $rows->sum('permit'),
                'leave' => (int) $rows->sum('leave'),
                'sick_leave' => (int) $rows->sum('sick_leave'),
                'compensation_hours' => (int) $rows->sum('compensation_hours'),
                'collective_leave' => (int) $rows->sum('collective_leave'),
                'company_holiday' => (int) $rows->sum('company_holiday'),
                'national_holiday' => (int) $rows->sum('national_holiday'),
                'training' => (int) $rows->sum('training'),
                'duty_out_of_town' => (int) $rows->sum('duty_out_of_town'),
                'present_days' => $totalPresent,
            ],
        ];
    }

    /**
     * Build the attendance PDF for the given date range.
     *
     * @return array{0: \Barryvdh\DomPDF\PDF, 1: string}
     */
    private function attendancePdf(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $report = app(AttendanceReportService::class)->build($start, $end);

        $pdf = Pdf::loadView('reports.attendance-pdf', ['report' => $report])
            ->setPaper('a4', 'landscape');

        $fileName = 'laporan-absensi-'.$start->format('Y-m-d').'-sd-'.$end->format('Y-m-d').'.pdf';

        return [$pdf, $fileName];
    }
}
