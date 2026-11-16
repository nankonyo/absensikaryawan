<?php

namespace Database\Seeders;

use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;

class OfficeLocationSeeder extends Seeder
{
    public function run(): void
    {
        $office = OfficeLocation::where('is_active', true)->first() ?? new OfficeLocation();

        $office->fill([
            'name' => 'Raja Sakti Telematika',
            'latitude' => -6.27731860,
            'longitude' => 106.97294579,
            'radius' => 100, // 100 meters
            'is_active' => true,
        ])->save();
    }
}
