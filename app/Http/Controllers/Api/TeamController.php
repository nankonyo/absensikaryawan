<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isAtasan() || $user->isAdmin(), 403);

        $ids = $user->canViewAllTeamAttendance()
            ? User::where('id', '!=', $user->id)->pluck('id')->all()
            : $user->descendantIds();

        $members = User::with(['role', 'unit', 'department'])
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get()
            ->map(function (User $member) {
                $attendance = Attendance::where('user_id', $member->id)
                    ->whereDate('date', today())
                    ->first();

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'role' => $member->role?->display_name ?? $member->role?->name,
                    'unit' => $member->unit?->name,
                    'department' => $member->department?->name,
                    'attendance' => $attendance,
                ];
            });

        return response()->json(['members' => $members]);
    }

    public function show(Request $request, User $member)
    {
        $user = $request->user();
        abort_unless($user->isAtasan() || $user->isAdmin(), 403);

        $allowed = $user->canViewAllTeamAttendance()
            || in_array((int) $member->id, $user->descendantIds(), true);
        abort_unless($allowed, 403);

        $history = Attendance::where('user_id', $member->id)
            ->orderByDesc('date')
            ->paginate((int) ($request->query('per_page') ?: 15));

        return response()->json([
            'member' => AuthController::userPayload($member),
            'attendances' => $history,
        ]);
    }
}
