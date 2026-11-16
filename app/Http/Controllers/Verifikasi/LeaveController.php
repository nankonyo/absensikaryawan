<?php

namespace App\Http\Controllers\Verifikasi;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $status = in_array($status, ['pending', 'approved', 'rejected'], true) ? $status : 'pending';

        $leaves = Leave::with('user', 'approver', 'verifier')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return view('verifikasi.leaves.index', compact('leaves', 'status'));
    }

    public function show(Leave $leave)
    {
        $leave->load('user', 'approver', 'verifier');

        return view('verifikasi.leaves.show', compact('leave'));
    }

    public function approve(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', __('ui.leave_already_processed'));
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldValues = $leave->toArray();

        $leave->update([
            'status' => 'approved',
            'approved_by' => $leave->approved_by ?? auth()->id(),
            'approved_at' => $leave->approved_at ?? now(),
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'notes' => $request->input('notes', $leave->notes),
        ]);

        \App\Models\AuditLog::log('leave_approved', 'Leave', $leave->id, $oldValues, $leave->toArray());

        return redirect()
            ->route('verifikasi.leaves.index')
            ->with('success', __('ui.leave_verified_success'));
    }

    public function reject(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return back()->with('error', __('ui.leave_already_processed'));
        }

        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $oldValues = $leave->toArray();

        $leave->update([
            'status' => 'rejected',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'notes' => $request->notes,
        ]);

        \App\Models\AuditLog::log('leave_rejected', 'Leave', $leave->id, $oldValues, $leave->toArray());

        return redirect()
            ->route('verifikasi.leaves.index')
            ->with('success', __('ui.leave_rejected_success'));
    }
}
