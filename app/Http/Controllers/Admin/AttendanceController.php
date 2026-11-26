<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceReason;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $month = (string) $request->query('month', now()->format('Y-m'));
        $status = $request->query('status');
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $query = Attendance::with(['user.department', 'user.employeeSetting', 'attendanceReason']);

        // Search by employee name or employee ID
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Filter by month and year
        if ($month !== '' && preg_match('/^(\d{4})-(\d{2})$/', $month, $matches)) {
            $query->whereYear('date', $matches[1])->whereMonth('date', $matches[2]);
        }

        // Filter by status
        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();
        $reasons = AttendanceReason::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.attendances.index', [
            'attendances' => $attendances,
            'search' => $search,
            'month' => $month,
            'status' => $status,
            'perPage' => $perPage,
            'reasons' => $reasons,
        ]);
    }

    public function updateReason(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'attendance_reason_id' => ['nullable', 'integer', 'exists:attendance_reasons,id'],
            'work_mode' => ['nullable', 'in:WFO,WFH,WFA'],
        ]);

        $reason = null;

        if (! empty($data['attendance_reason_id'])) {
            $reason = AttendanceReason::whereKey($data['attendance_reason_id'])->where('is_active', true)->first();
        }

        if (! $reason && ! empty($data['work_mode'])) {
            $reason = AttendanceReason::firstOrCreate(
                ['name' => $data['work_mode']],
                ['is_active' => true]
            );
        }

        if (! $reason) {
            return back()->withErrors(['attendance_reason_id' => 'Alasan kehadiran wajib dipilih.']);
        }

        $oldValues = $attendance->toArray();
        $attendance->update([
            'attendance_reason_id' => $reason->id,
            'status' => 'valid',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'verification_notes' => 'Mode absensi diubah admin menjadi '.$reason->name.'.',
        ]);

        \App\Models\AuditLog::log(
            'attendance_reason_updated',
            'Attendance',
            $attendance->id,
            $oldValues,
            $attendance->fresh()->toArray()
        );

        return back()->with('success', 'Mode absensi berhasil diubah ke '.$reason->name.'.');
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'work_mode' => ['required', 'in:WFO,WFH,WFA'],
        ]);

        $reason = AttendanceReason::firstOrCreate(
            ['name' => $data['work_mode']],
            ['is_active' => true]
        );

        $date = Carbon::parse($data['date'])->toDateString();
        $checkInTime = Carbon::createFromFormat('H:i', (string) Setting::get('work_start_time', '08:00'))->format('H:i:s');
        $users = User::query()
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->whereHas('role', fn ($query) => $query->whereNotIn('name', ['admin', 'direksi']))
            ->get();

        $created = 0;
        $updated = 0;

        foreach ($users as $user) {
            $attendance = Attendance::firstOrNew([
                'user_id' => $user->id,
                'date' => $date,
            ]);

            $isNew = ! $attendance->exists;

            $attendance->attendance_reason_id = $reason->id;
            $attendance->status = 'valid';
            $attendance->verified_by = auth()->id();
            $attendance->verified_at = now();
            $attendance->verification_notes = 'Absen massal admin: '.$data['work_mode'];

            if (! $attendance->check_in && ! $attendance->check_out) {
                $attendance->check_in = $checkInTime;
            }

            $attendance->save();

            $isNew ? $created++ : $updated++;
        }

        \App\Models\AuditLog::log('attendance_bulk_updated', 'Attendance', null, null, [
            'date' => $date,
            'work_mode' => $data['work_mode'],
            'created' => $created,
            'updated' => $updated,
        ]);

        return back()->with('success', 'Absen massal '.$data['work_mode'].' disimpan. '.$created.' dibuat, '.$updated.' diperbarui.');
    }

    public function show(Attendance $attendance)
    {
        $attendance->load('user');

        return view('admin.attendances.show', compact('attendance'));
    }
}
