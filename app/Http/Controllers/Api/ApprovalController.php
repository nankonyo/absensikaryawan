<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $teamIds = $user->canVerifyLeaves()
            ? User::where('id', '!=', $user->id)->pluck('id')->all()
            : $user->descendantIds();

        $leaves = Leave::with(['user', 'leaveType'])
            ->where('status', 'pending')
            ->when(! $user->canVerifyLeaves(), fn ($query) => $query->whereIn('user_id', $teamIds))
            ->orderBy('start_date')
            ->paginate((int) ($request->query('per_page') ?: 15));

        return response()->json($leaves);
    }

    public function approve(Request $request, Leave $leave)
    {
        $this->authorizeLeave($request->user(), $leave);

        $leave->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['message' => 'Pengajuan disetujui.', 'leave' => $leave]);
    }

    public function reject(Request $request, Leave $leave)
    {
        $this->authorizeLeave($request->user(), $leave);

        $leave->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['message' => 'Pengajuan ditolak.', 'leave' => $leave]);
    }

    public function verify(Request $request, Leave $leave)
    {
        $user = $request->user();
        abort_unless($user->canVerifyLeaves(), 403);

        $leave->update([
            'status' => 'approved',
            'verified_by' => $user->id,
            'verified_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        return response()->json(['message' => 'Pengajuan diverifikasi.', 'leave' => $leave]);
    }

    private function authorizeLeave(User $user, Leave $leave): void
    {
        if ($user->canVerifyLeaves() || $user->canManageUsers()) {
            return;
        }

        abort_unless(in_array((int) $leave->user_id, $user->descendantIds(), true), 403);
    }
}
