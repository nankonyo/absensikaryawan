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

    /**
     * Weekly holiday days (0 = Sunday ... 6 = Saturday) configured in Settings.
     * Defaults to Saturday and Sunday when the setting has never been saved.
     */
    public static function weeklyDays(): array
    {
        $days = Setting::get('weekly_holidays', [0, 6]);

        if (is_string($days)) {
            $days = json_decode($days, true) ?: [];
        }

        return collect((array) $days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= 0 && $day <= 6)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function isWeeklyHoliday(Carbon $date): bool
    {
        return in_array((int) $date->dayOfWeek, self::weeklyDays(), true);
    }

    public static function activeOn(Carbon $date): ?self
    {
        return self::query()
            ->active()
            ->whereDate('date', $date->toDateString())
            ->first();
    }

    public static function isDayOff(Carbon $date): bool
    {
        return self::isWeeklyHoliday($date) || self::activeOn($date) !== null;
    }

    /**
     * Label shown on attendance screens. Specific holidays use their name,
     * weekly holidays fall back to the day name (e.g. "Sabtu").
     */
    public static function dayOffName(Carbon $date): string
    {
        $holiday = self::activeOn($date);

        if ($holiday) {
            return $holiday->name;
        }

        return self::isWeeklyHoliday($date)
            ? __('ui.day_names.'.$date->dayOfWeek)
            : '';
    }

    public function typeLabel(): string
    {
        return __('ui.holiday_types.'.$this->type);
    }
}
