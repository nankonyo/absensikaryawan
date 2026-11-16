<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;

class AttendanceVerificationController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['all', 'pending', 'valid', 'invalid'], true) ? $status : 'pending';

        $attendances = $this->scopedQuery()
            ->with(['user.department', 'user.unit', 'attendanceReason', 'verifier'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $routePrefix = $request->routeIs('admin.*')
            ? 'admin.attendance-verifications'
            : 'atasan.attendance-verifications';

        return view('attendance-verifications.index', compact('attendances', 'status', 'routePrefix'));
    }

    public function approve(Request $request, Attendance $attendance)
    {
        $this->authorizeVerification($attendance);

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldValues = $attendance->toArray();

        $attendance->update([
            'status' => 'valid',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'verification_notes' => $request->notes,
        ]);

        \App\Models\AuditLog::log('attendance_verified', 'Attendance', $attendance->id, $oldValues, $attendance->toArray());

        return back()->with('success', __('ui.attendance_verified_success'));
    }

    public function reject(Request $request, Attendance $attendance)
    {
        $this->authorizeVerification($attendance);

        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $oldValues = $attendance->toArray();

        $attendance->update([
            'status' => 'invalid',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'verification_notes' => $request->notes,
        ]);

        \App\Models\AuditLog::log('attendance_rejected', 'Attendance', $attendance->id, $oldValues, $attendance->toArray());

        return back()->with('success', __('ui.attendance_rejected_success'));
    }

    private function scopedQuery()
    {
        $user = auth()->user();

        return Attendance::query()
            ->when(
                ! $user->canVerifyAllAttendance(),
                fn ($query) => $query->whereIn('user_id', $user->descendantIds())
            );
    }

    private function authorizeVerification(Attendance $attendance): void
    {
        $user = auth()->user();

        abort_unless(
            $user->canVerifyAllAttendance()
                || in_array($attendance->user_id, $user->descendantIds(), true),
            403
        );

        abort_unless($attendance->isPending(), 403, __('ui.attendance_already_verified'));
    }
}
