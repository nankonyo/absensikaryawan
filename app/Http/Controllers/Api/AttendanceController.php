<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceReason;
use App\Models\OfficeLocation;
use App\Models\Setting;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function today(Request $request, AttendanceService $service)
    {
        $user = $request->user();
        $attendance = Attendance::with('attendanceReason')
            ->where('user_id', $user->id)
            ->whereDate('date', today())
            ->first();

        $office = OfficeLocation::where('is_active', true)->first();

        return response()->json([
            'attendance' => $attendance,
            'office' => $office ? [
                'id' => $office->id,
                'name' => $office->name,
                'latitude' => (float) $office->latitude,
                'longitude' => (float) $office->longitude,
                'radius' => (int) $office->radius,
                'address' => $office->address,
            ] : null,
            'work_start_time' => Setting::get('work_start_time', '08:00'),
            'work_end_time' => Setting::get('work_end_time', '17:00'),
            'reasons' => AttendanceReason::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function history(Request $request, AttendanceService $service)
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:valid,pending,invalid'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $history = $service->getUserAttendanceHistory(
            $request->user(),
            $request->query('month'),
            (int) ($request->query('per_page') ?: 15),
            $request->query('status')
        );

        return response()->json($history);
    }

    public function checkIn(Request $request, AttendanceService $service)
    {
        $data = $this->validated($request);
        $data['skip_face_verification'] = ! Setting::get('mobile_face_recognition_enabled', false);

        $result = $service->checkIn($request->user(), $data);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function checkOut(Request $request, AttendanceService $service)
    {
        $data = $this->validated($request);
        $data['skip_face_verification'] = ! Setting::get('mobile_face_recognition_enabled', false);

        $result = $service->checkOut($request->user(), $data);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function reasons()
    {
        return response()->json([
            'reasons' => AttendanceReason::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['nullable', 'string', 'max:500'],
            'photo_data' => ['nullable', 'string'],
            'face_score' => ['nullable', 'numeric'],
            'attendance_reason_id' => ['nullable', 'integer', 'exists:attendance_reasons,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
