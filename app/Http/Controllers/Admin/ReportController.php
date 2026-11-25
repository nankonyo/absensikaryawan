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
    public function index()
    {
        return view('admin.reports.index');
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

    public function attendance(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:pdf,excel',
        ]);

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        if ($request->format === 'pdf') {
            [$pdf, $fileName] = $this->attendancePdf($request->start_date, $request->end_date);

            return $pdf->download($fileName);
        }

        $report = app(AttendanceReportService::class)->build($startDate, $endDate);
        $fileName = 'laporan-absensi-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d').'.xlsx';

        return Excel::download(new AttendanceReportExport($report), $fileName);
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
