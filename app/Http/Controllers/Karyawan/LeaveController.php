<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = Leave::where('user_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->paginate(15);

        return view('karyawan.leave.index', compact('leaves'));
    }

    public function create()
    {
        return view('karyawan.leave.create', [
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'reason' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
        ]);

        $type = LeaveType::where('is_active', true)->findOrFail($request->integer('leave_type_id'));

        // Calculate days
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $days = $start->diffInDays($end) + 1;

        $this->assertTypeRules($request, $type, $days);

        // Handle file upload
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('leave-attachments', 'public');
        }

        Leave::create([
            'user_id' => auth()->id(),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $days,
            'leave_type_id' => $type->id,
            'reason' => (string) $request->input('reason', ''),
            'attachment' => $attachmentPath,
            'status' => 'pending',
        ]);

        // Log audit
        \App\Models\AuditLog::log('leave_requested', 'Leave', null);

        return redirect()
            ->route('karyawan.leaves.index')
            ->with('success', __('ui.leave_submitted'));
    }

    public function show(Leave $leave)
    {
        // Ensure user can only view their own leave
        if ((int) $leave->user_id !== (int) auth()->id()) {
            abort(403, __('ui.own_leave_only'));
        }

        return view('karyawan.leave.show', compact('leave'));
    }

    public function edit(Leave $leave)
    {
        // Ensure user can only edit their own pending leave
        if ((int) $leave->user_id !== (int) auth()->id() || $leave->status !== 'pending') {
            abort(403);
        }

        return view('karyawan.leave.edit', compact('leave') + [
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Leave $leave)
    {
        // Ensure user can only update their own pending leave
        if ((int) $leave->user_id !== (int) auth()->id() || $leave->status !== 'pending') {
            abort(403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'reason' => 'nullable|string|max:500',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $type = LeaveType::where('is_active', true)->findOrFail($request->integer('leave_type_id'));

        // Calculate days
        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $days = $start->diffInDays($end) + 1;

        $this->assertTypeRules($request, $type, $days, $leave);

        $oldValues = $leave->toArray();

        // Handle file upload
        if ($request->hasFile('attachment')) {
            // Delete old attachment
            if ($leave->attachment) {
                Storage::disk('public')->delete($leave->attachment);
            }
            $attachmentPath = $request->file('attachment')->store('leave-attachments', 'public');
            $leave->attachment = $attachmentPath;
        }

        $leave->update([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $days,
            'leave_type_id' => $type->id,
            'reason' => (string) $request->input('reason', ''),
        ]);

        // Log audit
        \App\Models\AuditLog::log('leave_updated', 'Leave', $leave->id, $oldValues, $leave->toArray());

        return redirect()
            ->route('karyawan.leaves.index')
            ->with('success', __('ui.leave_updated'));
    }

    private function assertTypeRules(Request $request, LeaveType $type, int $days, ?Leave $leave = null): void
    {
        $errors = [];

        if ($type->requires_attachment && ! $request->hasFile('attachment') && ! $leave?->attachment) {
            $errors['attachment'] = __('ui.attachment_required_for_type');
        }

        if ($type->requires_reason && trim((string) $request->input('reason')) === '') {
            $errors['reason'] = __('ui.reason_required_for_type');
        }

        if ($type->deducts_quota) {
            $quota = (int) Setting::get('annual_leave_quota', 12);

            $used = Leave::where('user_id', auth()->id())
                ->where('leave_type_id', $type->id)
                ->whereIn('status', ['pending', 'approved'])
                ->whereYear('start_date', Carbon::parse($request->start_date)->year)
                ->when($leave, fn ($query) => $query->where('id', '!=', $leave->id))
                ->sum('days');

            if ($used + $days > $quota) {
                $errors['leave_type_id'] = __('ui.leave_quota_exceeded', [
                    'remaining' => max(0, $quota - $used),
                    'quota' => $quota,
                ]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function destroy(Leave $leave)
    {
        // Ensure user can only delete their own pending leave
        if ((int) $leave->user_id !== (int) auth()->id() || $leave->status !== 'pending') {
            abort(403);
        }

        // Delete attachment
        if ($leave->attachment) {
            Storage::disk('public')->delete($leave->attachment);
        }

        $leave->delete();

        // Log audit
        \App\Models\AuditLog::log('leave_deleted', 'Leave', $leave->id);

        return redirect()
            ->route('karyawan.leaves.index')
            ->with('success', __('ui.leave_deleted'));
    }
}
