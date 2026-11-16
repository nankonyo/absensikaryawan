<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $subordinates = User::whereIn('id', $user->descendantIds())
            ->with(['role', 'department', 'unit', 'supervisor'])
            ->orderBy('name')
            ->get();

        $subordinateIds = $subordinates->pluck('id');

        $pendingLeaves = Leave::whereIn('user_id', $subordinateIds)
            ->where('status', 'pending')
            ->count();

        $today = Carbon::today();
        $todayAttendances = Attendance::whereIn('user_id', $subordinateIds)
            ->whereDate('date', $today)
            ->with('user')
            ->get();

        $teamStats = [
            'total_team' => $subordinates->count(),
            'checked_in' => $todayAttendances->where('check_in', '!=', null)->count(),
            'late' => $todayAttendances->where('late_duration', '>', 0)->count(),
            'absent' => $subordinates->count() - $todayAttendances->count(),
        ];

        $groupedTeam = $subordinates->groupBy(fn (User $member) => $user->teamGroupName($member));

        $recentLeaves = Leave::whereIn('user_id', $subordinateIds)
            ->where('status', 'pending')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('atasan.dashboard', compact(
            'subordinates',
            'groupedTeam',
            'pendingLeaves',
            'teamStats',
            'todayAttendances',
            'recentLeaves'
        ));
    }
}
