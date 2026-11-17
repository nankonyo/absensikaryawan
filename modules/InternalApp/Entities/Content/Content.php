<?php

namespace Modules\InternalApp\Entities\Content;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Content extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'is_active',
        'title',
        'slug',
        'type',
        'category',
        'excerpt',
        'body',
        'status',
        'author_id',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public const TYPES = ['article', 'news', 'page', 'announcement'];

    public const STATUSES = ['draft', 'review', 'published', 'archived'];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public static function makeUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'content';
        $slug = $base;
        $counter = 2;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
