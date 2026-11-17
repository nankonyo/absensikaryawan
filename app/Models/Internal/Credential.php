<?php

namespace App\Models\Internal;

use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    protected $table = 'internal_credentials';

    protected $fillable = [
        'category', 'group_name', 'vendor', 'name', 'ip_address', 'url',
        'username', 'password', 'email', 'expired_date', 'status', 'notes', 'extra',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'expired_date' => 'date',
    ];
}
