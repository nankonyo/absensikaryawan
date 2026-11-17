<?php

namespace App\Models\Internal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VideoTutorial extends Model
{
    use SoftDeletes;

    protected $table = 'internal_video_tutorials';

    protected $fillable = ['title', 'youtube_url', 'badge_text', 'badge_color', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];
}
