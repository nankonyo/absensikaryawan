<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function issue(User $user, string $name = 'mobile', int $days = 30): string
    {
        $plain = Str::random(80);

        static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
            'expires_at' => now()->addDays($days),
        ]);

        return $plain;
    }

    public static function findValid(string $plain): ?self
    {
        return static::query()
            ->where('token', hash('sha256', $plain))
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public function touchUsage(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
