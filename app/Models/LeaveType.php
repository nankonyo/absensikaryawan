<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'category',
        'requires_attachment',
        'requires_reason',
        'deducts_quota',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_attachment' => 'boolean',
        'requires_reason' => 'boolean',
        'deducts_quota' => 'boolean',
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }
}
