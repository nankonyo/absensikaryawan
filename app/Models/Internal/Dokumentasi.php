<?php

namespace App\Models\Internal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dokumentasi extends Model
{
    use SoftDeletes;

    protected $table = 'internal_dokumentasi';

    protected $fillable = ['title', 'description', 'file_url', 'icon_color', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];
}
