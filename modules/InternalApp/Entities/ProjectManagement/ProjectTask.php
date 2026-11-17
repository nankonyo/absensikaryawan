<?php

namespace Modules\InternalApp\Entities\ProjectManagement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id',
        'is_active',
        'title',
        'description',
        'assigned_to',
        'status',
        'priority',
        'start_date',
        'due_date',
        'progress',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'date',
        'due_date' => 'date',
        'progress' => 'integer',
    ];

    public const STATUSES = ['todo', 'in_progress', 'review', 'done', 'cancelled'];

    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
