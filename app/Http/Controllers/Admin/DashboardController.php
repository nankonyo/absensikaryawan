<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Total employees = every user except the system administrator.
        $employeeQuery = User::whereHas('role', fn ($q) => $q->where('name', '!=', 'admin'));
        $totalKaryawan = (clone $employeeQuery)->count();
        $permanentEmployees = (clone $employeeQuery)->where('employment_status', 'permanent')->count();
        $contractEmployees = (clone $employeeQuery)->where('employment_status', 'contract')->count();

        // Today's attendance
        $todayAttendances = Attendance::whereDate('date', $today)->count();
        $todayLate = Attendance::whereDate('date', $today)
            ->where('late_duration', '>', 0)
            ->count();

        // Pending approvals
        $pendingLeaves = Leave::where('status', 'pending')->count();

        // This month stats
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        $monthlyAttendance = Attendance::whereBetween('date', [$monthStart, $monthEnd])
            ->whereNotNull('check_in')
            ->count();

        // Recent attendances
        $recentAttendances = Attendance::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Charts data - Last 7 days attendance
        $last7Days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $last7Days[] = [
                'date' => $date->format('d/m/Y'),
                'count' => Attendance::whereDate('date', $date)->whereNotNull('check_in')->count(),
                'late' => Attendance::whereDate('date', $date)->where('late_duration', '>', 0)->count(),
            ];
        }

        return view('admin.dashboard', compact(
            'totalKaryawan',
            'permanentEmployees',
            'contractEmployees',
            'todayAttendances',
            'todayLate',
            'pendingLeaves',
            'monthlyAttendance',
            'recentAttendances',
            'last7Days'
        ));
    }
}
