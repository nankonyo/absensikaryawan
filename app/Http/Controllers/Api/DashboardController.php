<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $today = Attendance::where('user_id', $user->id)->whereDate('date', today())->first();

        $teamIds = $this->teamIds($user);

        $pendingApprovals = Leave::where('status', 'pending')
            ->when(! $user->canVerifyLeaves(), fn ($query) => $query->whereIn('user_id', $teamIds))
            ->count();

        $stats = [
            'today_attendance' => $today,
            'my_pending_leaves' => Leave::where('user_id', $user->id)->where('status', 'pending')->count(),
            'team_members' => count($teamIds),
            'pending_approvals' => $pendingApprovals,
            'projects' => DB::table('internal_projects')->count(),
            'my_activities_this_month' => DB::table('internal_activity_logs')
                ->where('user_id', $user->id)
                ->where('activity_date', '>=', now()->startOfMonth()->toDateString())
                ->count(),
        ];

        $announcements = DB::table('internal_announcements')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get(['id', 'title', 'content', 'created_at']);

        $projects = DB::table('internal_projects')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get(['id', 'name', 'status', 'progress', 'end_date']);

        return response()->json([
            'user' => AuthController::userPayload($user),
            'stats' => $stats,
            'announcements' => $announcements,
            'projects' => $projects,
            'menu' => $this->menu($user),
        ]);
    }

    private function teamIds(User $user): array
    {
        if ($user->canViewAllTeamAttendance()) {
            return User::where('id', '!=', $user->id)->pluck('id')->all();
        }

        return $user->descendantIds();
    }

    private function menu(User $user): array
    {
        $menu = [
            ['key' => 'attendance', 'label' => 'Presensi', 'icon' => 'finger-print'],
            ['key' => 'leave', 'label' => 'Cuti & Izin', 'icon' => 'calendar'],
            ['key' => 'internal', 'label' => 'Internal App', 'icon' => 'apps'],
        ];

        if ($user->isAtasan() || $user->isAdmin()) {
            $menu[] = ['key' => 'team', 'label' => 'Tim Saya', 'icon' => 'people'];
            $menu[] = ['key' => 'approval', 'label' => 'Approval', 'icon' => 'checkmark-done'];
        }

        if ($user->isAdmin()) {
            $menu[] = ['key' => 'reports', 'label' => 'Laporan', 'icon' => 'bar-chart'];
            $menu[] = ['key' => 'users', 'label' => 'Pengguna', 'icon' => 'person'];
            $menu[] = ['key' => 'settings', 'label' => 'Pengaturan', 'icon' => 'settings'];
        }

        return $menu;
    }
}
