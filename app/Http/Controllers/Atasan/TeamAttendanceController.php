<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TeamAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $departmentId = $request->integer('department_id') ?: null;
        $unitId = $request->integer('unit_id') ?: null;
        $canViewAll = $user->canViewAllTeamAttendance();

        $departments = Department::orderBy('name')->get();
        $units = $departmentId
            ? Unit::where('department_id', $departmentId)->orderBy('name')->get()
            : collect();

        if (! $units->contains('id', $unitId)) {
            $unitId = null;
        }

        $members = $canViewAll
            ? User::whereHas('role', fn ($query) => $query->where('name', '!=', 'admin'))
            : User::whereIn('id', $user->descendantIds());

        $members = $members
            ->with(['role', 'department', 'unit', 'supervisor'])
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->when($unitId, fn ($query) => $query->where('unit_id', $unitId))
            ->orderBy('name')
            ->get();

        $attendances = Attendance::whereIn('user_id', $members->pluck('id'))
            ->whereDate('date', $date)
            ->get()
            ->keyBy('user_id');

        $groupedTeam = $canViewAll
            ? $members->groupBy(fn (User $member) => sprintf(
                '%s — %s',
                $member->department?->name ?? __('ui.no_department'),
                $member->unit?->name ?? __('ui.no_unit'),
            ))->sortKeys()
            : $members->groupBy(fn (User $member) => $user->teamGroupName($member));

        $routePrefix = $request->routeIs('admin.*') ? 'admin.team-attendance' : 'atasan.team-attendance';

        return view('atasan.team-attendance.index', compact(
            'groupedTeam',
            'attendances',
            'date',
            'departments',
            'units',
            'departmentId',
            'unitId',
            'routePrefix',
        ));
    }

    public function show(User $user, Request $request)
    {
        $verifier = auth()->user()->canViewAllTeamAttendance();

        abort_unless($verifier || in_array($user->id, auth()->user()->descendantIds(), true), 403);

        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $month);

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->orderBy('date', 'desc')
            ->paginate(15)
            ->withQueryString();

        $user->load('role', 'department', 'unit', 'supervisor');

        $routePrefix = $request->routeIs('admin.*') ? 'admin.team-attendance' : 'atasan.team-attendance';

        return view('atasan.team-attendance.show', compact('user', 'attendances', 'month', 'routePrefix'));
    }
}
