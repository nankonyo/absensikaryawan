<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Leave extends Model
{
    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'days',
        'leave_type_id',
        'reason',
        'attachment',
        'status',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $with = ['leaveType'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->leaveType?->name ?? __('ui.empty');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function getAttachmentUrl(): ?string
    {
        return $this->attachment ? Storage::disk('public')->url($this->attachment) : null;
    }
}
