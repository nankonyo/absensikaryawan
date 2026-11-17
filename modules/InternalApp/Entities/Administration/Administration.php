<?php

namespace Modules\InternalApp\Entities\Administration;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Administration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'is_active',
        'number',
        'title',
        'category',
        'description',
        'file_path',
        'document_date',
        'status',
        'user_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'document_date' => 'date',
    ];

    public const CATEGORIES = ['surat', 'sop', 'sk', 'laporan', 'lainnya'];

    public const STATUSES = ['active', 'archived'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
