<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAttribute extends Model
{
    protected $fillable = ['name', 'label', 'type', 'options', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function values()
    {
        return $this->hasMany(UserAttributeValue::class);
    }
}
