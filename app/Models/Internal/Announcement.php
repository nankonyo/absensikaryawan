<?php

namespace App\Models\Internal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $table = 'internal_announcements';

    protected $fillable = ['code', 'slug', 'title', 'content', 'pdf_path', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
