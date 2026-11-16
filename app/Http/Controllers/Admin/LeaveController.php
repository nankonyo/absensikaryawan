<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = $request->query('status');
        $leaveTypeId = $request->integer('leave_type_id') ?: null;
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $leaves = Leave::with('user.department', 'approver')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($leaveTypeId, fn ($query) => $query->where('leave_type_id', $leaveTypeId))
            ->orderBy('start_date', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.leaves.index', [
            'leaves' => $leaves,
            'search' => $search,
            'status' => $status,
            'leaveTypeId' => $leaveTypeId,
            'perPage' => $perPage,
            'leaveTypes' => LeaveType::orderBy('name')->get(),
        ]);
    }

    public function show(Leave $leave)
    {
        $leave->load('user', 'approver');
        return view('admin.leaves.show', compact('leave'));
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
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->notes,
        ]);

        \App\Models\AuditLog::log('leave_approved', 'Leave', $leave->id, $oldValues, $leave->toArray());

        return redirect()
            ->route('admin.leaves.index')
            ->with('success', __('ui.leave_approved_success'));
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
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'notes' => $request->notes,
        ]);

        \App\Models\AuditLog::log('leave_rejected', 'Leave', $leave->id, $oldValues, $leave->toArray());

        return redirect()
            ->route('admin.leaves.index')
            ->with('success', __('ui.leave_rejected_success'));
    }
}
