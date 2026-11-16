<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'level',
        'display_name',
        'description',
    ];

    public const LEVELS = [
        'admin' => 0,
        'direksi' => 1,
        'general_manager' => 2,
        'sekretaris' => 2,
        'manager' => 3,
        'supervisor' => 4,
        'staff' => 5,
    ];

    /**
     * Get all users with this role
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->name, ['admin', 'direksi'], true);
    }

    public function isAtasan(): bool
    {
        return in_array($this->name, ['general_manager', 'sekretaris', 'manager', 'supervisor'], true);
    }

    public function isKaryawan(): bool
    {
        return $this->name === 'staff';
    }

    public function label(): string
    {
        return __('ui.roles.'.$this->name);
    }
}
