<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceReason;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AttendanceService
{
    public function __construct(
        protected GpsValidationService $gpsService,
        protected FaceRecognitionService $faceService
    ) {}

    /**
     * Process check-in
     */
    public function checkIn(User $user, array $data): array
    {
        // Validate if already checked in today
        $existing = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($existing && $existing->check_in) {
            return [
                'success' => false,
                'message' => __('ui.already_checked_in'),
            ];
        }

        $workMode = $this->workMode($user);

        // WFO is bound to the office radius; WFH/WFA may attend from anywhere.
        $gpsValidation = $this->gpsService->validateLocation(
            $data['latitude'],
            $data['longitude']
        );

        if ($workMode !== 'WFO') {
            $gpsValidation['valid'] = true;
            $gpsValidation['message'] = __('ui.gps_valid');
        }

        $verification = $this->resolveVerification($gpsValidation, $data, $existing);

        if (isset($verification['error'])) {
            return [
                'success' => false,
                'message' => $verification['error'],
                'distance' => $verification['distance'] ?? $gpsValidation['distance'],
            ];
        }

        // Store photo from base64 data
        $photoPath = null;
        $faceScore = null;

        // Validate Face Recognition (if enabled) using the score computed in the browser.
        // Mobile API may skip this when server-side face matching is not available.
        if ($this->faceService->isEnabled() && empty($data['skip_face_verification'])) {
            if (!$user->avatar) {
                return [
                    'success' => false,
                    'message' => __('ui.avatar_not_uploaded'),
                ];
            }

            $faceScore = isset($data['face_score']) ? (float) $data['face_score'] : null;
            $threshold = $this->faceService->getThreshold();

            if ($faceScore === null || $faceScore < $threshold) {
                return [
                    'success' => false,
                    'message' => __('ui.face_mismatch', ['score' => $faceScore ?? 0, 'threshold' => $threshold]),
                    'score' => $faceScore,
                ];
            }
        }

        if (isset($data['photo_data'])) {
            $photoPath = $this->storePhotoFromBase64($data['photo_data'], 'check_in', $user->id);
        }

        // Calculate late duration
        $workStartTime = Setting::get('work_start_time', '08:00');
        $checkInTime = Carbon::now();
        $lateDuration = $this->calculateLateDuration($checkInTime, $workStartTime);

        // Create or update attendance
        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => Carbon::today(),
            ],
            [
                'check_in' => $checkInTime->format('H:i:s'),
                'check_in_latitude' => $data['latitude'],
                'check_in_longitude' => $data['longitude'],
                'check_in_photo' => $photoPath ?? null,
                'check_in_face_score' => $faceScore,
                'check_in_address' => $data['address'] ?? null,
                'late_duration' => $lateDuration,
                'status' => $verification['status'],
                'attendance_reason_id' => $verification['reason_id'],
                'notes' => $verification['notes'] ?? null,
            ]
        );

        // Log audit
        \App\Models\AuditLog::log('check_in', 'Attendance', $attendance->id, null, $attendance->toArray());

        return [
            'success' => true,
            'message' => $verification['status'] === 'pending'
                ? __('ui.check_in_pending')
                : __('ui.check_in_success'),
            'attendance' => $attendance,
            'late_duration' => $lateDuration,
            'work_mode' => $workMode,
        ];
    }

    /**
     * Process check-out
     */
    public function checkOut(User $user, array $data): array
    {
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', Carbon::today())
            ->first();

        if ($attendance && $attendance->check_out) {
            return [
                'success' => false,
                'message' => __('ui.already_checked_out'),
            ];
        }

        $workMode = $this->workMode($user);

        // WFO is bound to the office radius; WFH/WFA may attend from anywhere.
        $gpsValidation = $this->gpsService->validateLocation(
            $data['latitude'],
            $data['longitude']
        );

        if ($workMode !== 'WFO') {
            $gpsValidation['valid'] = true;
            $gpsValidation['message'] = __('ui.gps_valid');
        }

        $verification = $this->resolveVerification($gpsValidation, $data, $attendance);

        if (isset($verification['error'])) {
            return [
                'success' => false,
                'message' => $verification['error'],
                'distance' => $verification['distance'] ?? $gpsValidation['distance'],
            ];
        }

        // Store photo from base64 data
        $photoPath = null;
        $faceScore = null;

        // Validate Face Recognition (if enabled) using the score computed in the browser.
        // Mobile API may skip this when server-side face matching is not available.
        if ($this->faceService->isEnabled() && empty($data['skip_face_verification'])) {
            if (!$user->avatar) {
                return [
                    'success' => false,
                    'message' => __('ui.avatar_not_uploaded'),
                ];
            }

            $faceScore = isset($data['face_score']) ? (float) $data['face_score'] : null;
            $threshold = $this->faceService->getThreshold();

            if ($faceScore === null || $faceScore < $threshold) {
                return [
                    'success' => false,
                    'message' => __('ui.face_mismatch', ['score' => $faceScore ?? 0, 'threshold' => $threshold]),
                    'score' => $faceScore,
                ];
            }
        }

        if (isset($data['photo_data'])) {
            $photoPath = $this->storePhotoFromBase64($data['photo_data'], 'check_out', $user->id);
        }

        // Calculate work duration and early leave
        $checkOutTime = Carbon::now();
        $workEndTime = Setting::get('work_end_time', '17:00');
        $workDuration = $attendance && $attendance->check_in
            ? $this->calculateWorkDuration($attendance->check_in, $checkOutTime)
            : null;
        $earlyLeaveDuration = $this->calculateEarlyLeaveDuration($checkOutTime, $workEndTime);

        // Update or create attendance (check-out is allowed without check-in)
        $values = [
            'check_out' => $checkOutTime->format('H:i:s'),
            'check_out_latitude' => $data['latitude'],
            'check_out_longitude' => $data['longitude'],
            'check_out_photo' => $photoPath ?? null,
            'check_out_face_score' => $faceScore,
            'check_out_address' => $data['address'] ?? null,
            'work_duration' => $workDuration,
            'early_leave_duration' => $earlyLeaveDuration,
            'status' => $verification['status'],
            'attendance_reason_id' => $verification['reason_id'],
            'notes' => $verification['notes'] ?? $attendance?->notes,
        ];

        if ($attendance) {
            $oldValues = $attendance->toArray();
            $attendance->update($values);
        } else {
            $oldValues = null;
            $attendance = Attendance::create($values + [
                'user_id' => $user->id,
                'date' => Carbon::today(),
            ]);
        }

        // Log audit
        \App\Models\AuditLog::log('check_out', 'Attendance', $attendance->id, $oldValues, $attendance->toArray());

        return [
            'success' => true,
            'message' => $verification['status'] === 'pending'
                ? __('ui.check_out_pending')
                : __('ui.check_out_success'),
            'attendance' => $attendance,
            'work_duration' => $workDuration,
            'work_mode' => $workMode,
        ];
    }

    /**
     * Decide whether an attendance is valid or must wait for verification.
     */
    protected function resolveVerification(array $gpsValidation, array $data, ?Attendance $existing = null): array
    {
        if ($gpsValidation['valid']) {
            $status = match ($existing?->status) {
                'pending' => 'pending',
                'invalid' => 'invalid',
                default => 'valid',
            };

            return [
                'status' => $status,
                'reason_id' => $status === 'pending' ? $existing?->attendance_reason_id : null,
            ];
        }

        // No active office means distance cannot be evaluated; keep the old hard error.
        if (($gpsValidation['distance'] ?? null) === null) {
            return ['error' => $gpsValidation['message'], 'distance' => null];
        }

        $reason = null;
        $reasonId = $data['attendance_reason_id'] ?? null;

        if ($reasonId) {
            $reason = AttendanceReason::whereKey($reasonId)->where('is_active', true)->first();
        }

        if (! $reason && $existing?->attendance_reason_id) {
            $reason = $existing->attendanceReason;
        }

        if (! $reason) {
            return [
                'error' => __('ui.attendance_reason_required'),
                'distance' => $gpsValidation['distance'],
            ];
        }

        return [
            'status' => 'pending',
            'reason_id' => $reason->id,
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * Employee work mode. Unknown values fall back to the strict WFO mode.
     */
    public function workMode(User $user): string
    {
        $mode = strtoupper((string) ($user->employeeSetting?->work_mode ?? 'WFO'));

        return in_array($mode, ['WFO', 'WFH', 'WFA'], true) ? $mode : 'WFO';
    }

    /**
     * Store photo from base64 data
     */
    protected function storePhotoFromBase64(string $base64Data, string $type, int $userId): string
    {
        // Remove data:image/jpeg;base64, prefix
        $image = str_replace('data:image/jpeg;base64,', '', $base64Data);
        $image = str_replace(' ', '+', $image);
        $imageData = base64_decode($image);
        
        // Generate unique filename
        $filename = $type . '_' . $userId . '_' . time() . '.jpg';
        $path = 'attendance/' . $type . '/' . $filename;
        
        // Store file
        Storage::disk('public')->put($path, $imageData);
        
        return $path;
    }

    /**
     * Calculate late duration in minutes
     */
    protected function calculateLateDuration(Carbon $checkIn, string $workStartTime): int
    {
        $startTime = Carbon::createFromFormat('H:i', $workStartTime);
        $tolerance = Setting::get('late_tolerance', 15); // minutes

        if ($checkIn->lessThanOrEqualTo($startTime->copy()->addMinutes($tolerance))) {
            return 0;
        }

        return $checkIn->diffInMinutes($startTime, true);
    }

    /**
     * Calculate work duration in minutes
     */
    protected function calculateWorkDuration(string $checkIn, Carbon $checkOut): int
    {
        // Parse check_in - it could be datetime or time only
        $start = Carbon::parse($checkIn);
        return $checkOut->diffInMinutes($start, true);
    }

    /**
     * Calculate early leave duration in minutes
     */
    protected function calculateEarlyLeaveDuration(Carbon $checkOut, string $workEndTime): int
    {
        $endTime = Carbon::createFromFormat('H:i', $workEndTime);

        if ($checkOut->greaterThanOrEqualTo($endTime)) {
            return 0;
        }

        return $endTime->diffInMinutes($checkOut, true);
    }

    /**
     * Get user attendance history
     */
    public function getUserAttendanceHistory(User $user, ?string $month = null, int $perPage = 10, ?string $status = null)
    {
        $query = Attendance::where('user_id', $user->id);

        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $query->whereYear('date', $date->year)
                  ->whereMonth('date', $date->month);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();
    }
}
