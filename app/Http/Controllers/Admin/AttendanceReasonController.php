<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceReason;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceReasonController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $perPage = in_array($request->integer('per_page'), [5, 10, 15, 25, 50, 100], true)
            ? $request->integer('per_page')
            : 10;

        $attendanceReasons = AttendanceReason::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        $editing = $request->filled('edit')
            ? AttendanceReason::find($request->integer('edit'))
            : null;

        return view('admin.master.attendance-reasons.index', compact(
            'attendanceReasons',
            'editing',
            'search',
            'perPage',
        ));
    }

    public function store(Request $request)
    {
        AttendanceReason::create($this->validated($request));

        return back()->with('success', __('ui.attendance_reason_created'));
    }

    public function update(Request $request, AttendanceReason $attendanceReason)
    {
        $attendanceReason->update($this->validated($request, $attendanceReason));

        return back()->with('success', __('ui.attendance_reason_updated'));
    }

    public function destroy(AttendanceReason $attendanceReason)
    {
        if ($attendanceReason->attendances()->exists()) {
            return back()->with('error', __('ui.attendance_reason_in_use'));
        }

        $attendanceReason->delete();

        return back()->with('success', __('ui.attendance_reason_deleted'));
    }

    private function validated(Request $request, ?AttendanceReason $attendanceReason = null): array
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('attendance_reasons', 'name')->ignore($attendanceReason),
            ],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
