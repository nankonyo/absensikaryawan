<?php

namespace App\Http\Controllers\Admin;

use App\Exports\AttendanceReportExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
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
            $attendances = Attendance::with('user')
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'desc')
                ->get();

            $pdf = Pdf::loadView('reports.attendance-pdf', compact('attendances', 'startDate', 'endDate'));

            return $pdf->download('attendance-report-'.$startDate->format('Y-m-d').'-to-'.$endDate->format('Y-m-d').'.pdf');
        }

        $report = app(AttendanceReportService::class)->build($startDate, $endDate);
        $fileName = 'laporan-absensi-'.$startDate->format('Y-m-d').'-sd-'.$endDate->format('Y-m-d').'.xlsx';

        return Excel::download(new AttendanceReportExport($report), $fileName);
    }
}
