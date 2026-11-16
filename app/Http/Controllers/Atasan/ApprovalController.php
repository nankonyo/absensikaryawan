<?php

namespace App\Http\Controllers\Atasan;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['all', 'pending', 'approved', 'rejected'], true) ? $status : 'pending';

        $leaves = Leave::whereIn('user_id', auth()->user()->descendantIds())
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->with('user', 'approver', 'verifier')
            ->orderBy('start_date', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('atasan.approvals.index', compact('leaves', 'status'));
    }

    public function show(Leave $leave)
    {
        $this->authorizeSubordinate($leave);

        $leave->load('user', 'approver', 'verifier');

        return view('atasan.approvals.show', compact('leave'));
    }

    public function approve(Request $request, Leave $leave)
    {
        $this->authorizeSubordinate($leave);

        if ($leave->status !== 'pending') {
            return back()->with('error', __('ui.leave_already_processed'));
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldValues = $leave->toArray();

        $leave->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->notes,
        ]);

        \App\Models\AuditLog::log('leave_approved', 'Leave', $leave->id, $oldValues, $leave->toArray());

        return redirect()
            ->route('atasan.approvals.index')
            ->with('success', __('ui.leave_approved_success'));
    }

    public function reject(Request $request, Leave $leave)
    {
        $this->authorizeSubordinate($leave);

        if ($leave->status !== 'pending') {
            return back()->with('error', __('ui.leave_already_processed'));
        }

        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $oldValues = $leave->toArray();

        $leave->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->notes,
        ]);

        \App\Models\AuditLog::log('leave_rejected', 'Leave', $leave->id, $oldValues, $leave->toArray());

        return redirect()
            ->route('atasan.approvals.index')
            ->with('success', __('ui.leave_rejected_success'));
    }

    private function authorizeSubordinate(Leave $leave): void
    {
        abort_unless(in_array($leave->user_id, auth()->user()->descendantIds(), true), 403);
    }
}
