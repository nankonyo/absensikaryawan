<?php

namespace Modules\InternalApp\Entities\Activity;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\InternalApp\Entities\ProjectManagement\Project;

class Activity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'is_active',
        'project_id',
        'user_id',
        'title',
        'description',
        'activity_date',
        'start_time',
        'end_time',
        'location',
        'status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'activity_date' => 'date',
    ];

    public const STATUSES = ['planned', 'ongoing', 'completed', 'cancelled'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
