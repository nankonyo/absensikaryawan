<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSetting extends Model
{
    protected $table = 'internal_employee_settings';

    protected $fillable = [
        'user_id',
        'work_mode',
        'office_id',
        'shift_id',
        'is_active',
        'cuti_quota',
        'divisi',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
