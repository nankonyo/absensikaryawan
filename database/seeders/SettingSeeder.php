<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Regional
            [
                'key' => 'timezone',
                'value' => 'Asia/Jakarta',
                'type' => 'string',
                'description' => 'Application timezone',
            ],

            // Work hours
            [
                'key' => 'work_start_time',
                'value' => '08:00',
                'type' => 'string',
                'description' => 'Work start time',
            ],
            [
                'key' => 'work_end_time',
                'value' => '17:00',
                'type' => 'string',
                'description' => 'Work end time',
            ],
            [
                'key' => 'late_tolerance',
                'value' => '15',
                'type' => 'integer',
                'description' => 'Late tolerance in minutes',
            ],
            [
                'key' => 'weekly_holidays',
                'value' => json_encode([0, 6]),
                'type' => 'json',
                'description' => 'Weekly holiday days (0 = Sunday ... 6 = Saturday)',
            ],

            // Leave
            [
                'key' => 'annual_leave_quota',
                'value' => '12',
                'type' => 'integer',
                'description' => 'Annual leave quota in days',
            ],
            
            // Face recognition
            [
                'key' => 'face_recognition_enabled',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable face recognition for attendance',
            ],
            [
                'key' => 'face_recognition_threshold',
                'value' => '80',
                'type' => 'integer',
                'description' => 'Minimum face recognition score (0-100)',
            ],
            
            // Company info
            [
                'key' => 'company_name',
                'value' => 'Raja Sakti Telematika',
                'type' => 'string',
                'description' => 'Company name',
            ],
            [
                'key' => 'company_address',
                'value' => 'Jl. Boulevard Raya Barat, RSA5 No 32, RT.001/RW.002, Jaka Setia, Kec. Bekasi Sel., Kota Bks, Jawa Barat 17147, Indonesia',
                'type' => 'string',
                'description' => 'Company address',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
