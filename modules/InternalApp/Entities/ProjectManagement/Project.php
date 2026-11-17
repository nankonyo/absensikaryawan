<?php

namespace Modules\InternalApp\Entities\ProjectManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\InternalApp\Entities\Activity\Activity;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'is_active',
        'code',
        'name',
        'description',
        'manager_id',
        'start_date',
        'end_date',
        'status',
        'priority',
        'progress',
        'budget',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
        'budget' => 'decimal:2',
    ];

    public const STATUSES = ['planning', 'running', 'on_hold', 'completed', 'cancelled'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTask::class);
    }

    public function members()
    {
        return $this->hasMany(ProjectMember::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
