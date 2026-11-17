<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $leaves = Leave::with(['leaveType', 'approver', 'verifier'])
            ->where('user_id', $request->user()->id)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->orderByDesc('start_date')
            ->paginate((int) ($request->query('per_page') ?: 15));

        return response()->json($leaves);
    }

    public function types()
    {
        return response()->json([
            'types' => LeaveType::where('is_active', true)->orderBy('name')->get(['id', 'name', 'description']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'leave_type_id' => ['required', Rule::exists('leave_types', 'id')->where('is_active', true)],
            'reason' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);

        $attachment = $request->hasFile('attachment')
            ? $request->file('attachment')->store('leave-attachments', 'public')
            : null;

        $leave = Leave::create([
            'user_id' => $request->user()->id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days' => $start->diffInDays($end) + 1,
            'leave_type_id' => $data['leave_type_id'],
            'reason' => $data['reason'] ?? '',
            'attachment' => $attachment,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Pengajuan cuti berhasil dikirim.',
            'leave' => $leave->load('leaveType'),
        ], 201);
    }

    public function show(Request $request, Leave $leave)
    {
        abort_unless((int) $leave->user_id === (int) $request->user()->id, 403);

        return response()->json(['leave' => $leave->load(['leaveType', 'approver', 'verifier'])]);
    }

    public function destroy(Request $request, Leave $leave)
    {
        abort_unless((int) $leave->user_id === (int) $request->user()->id, 403);
        abort_unless($leave->status === 'pending', 422, 'Hanya pengajuan pending yang bisa dibatalkan.');

        if ($leave->attachment) {
            Storage::disk('public')->delete($leave->attachment);
        }

        $leave->delete();

        return response()->json(['message' => 'Pengajuan cuti dibatalkan.']);
    }
}
