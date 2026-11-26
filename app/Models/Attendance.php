<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_photo',
        'check_in_face_score',
        'check_in_address',
        'check_out',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_photo',
        'check_out_face_score',
        'check_out_address',
        'status',
        'attendance_reason_id',
        'verified_by',
        'verified_at',
        'verification_notes',
        'notes',
        'work_duration',
        'late_duration',
        'early_leave_duration',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime:H:i',
        'check_out' => 'datetime:H:i',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendanceReason(): BelongsTo
    {
        return $this->belongsTo(AttendanceReason::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function modeLabel(): string
    {
        $reason = strtoupper(trim((string) ($this->attendanceReason?->name ?? '')));

        if (in_array($reason, ['WFO', 'WFH', 'WFA'], true)) {
            return $reason;
        }

        $mode = strtoupper((string) ($this->user?->work_mode ?? 'WFO'));

        return in_array($mode, ['WFO', 'WFH', 'WFA'], true) ? $mode : 'WFO';
    }

    public function statusLabel(): string
    {
        return __('ui.statuses.'.($this->status ?: 'pending'));
    }

    public function isLate(): bool
    {
        return $this->late_duration > 0;
    }

    public function hasEarlyLeave(): bool
    {
        return $this->early_leave_duration > 0;
    }

    /**
     * Tardiness rounded to whole hours: the remaining minutes round down
     * below 30 minutes and round up at 30 minutes or more.
     */
    public function lateDurationHours(): int
    {
        $minutes = max(0, (int) $this->late_duration);
        $hours = intdiv($minutes, 60);

        if (($minutes % 60) >= 30) {
            $hours++;
        }

        return $hours;
    }

    /**
     * Human readable tardiness in whole hours.
     */
    public function lateDurationLabel(): string
    {
        return __('ui.late_hours', ['hours' => $this->lateDurationHours()]);
    }
}
