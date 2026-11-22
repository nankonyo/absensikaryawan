<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    public const TYPES = ['national', 'company', 'collective'];

    protected $fillable = [
        'date',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Store a plain Y-m-d value so SQLite date comparisons stay exact.
     */
    protected function date(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value instanceof Carbon ? $value : Carbon::parse($value),
            set: fn ($value) => Carbon::parse($value)->toDateString(),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function typeLabel(): string
    {
        return __('ui.holiday_types.'.$this->type);
    }
}
