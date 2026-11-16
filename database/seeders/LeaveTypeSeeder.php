<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'name' => 'Sakit',
                'category' => 'sakit',
                'requires_attachment' => true,
                'requires_reason' => false,
                'deducts_quota' => false,
            ],
            [
                'name' => 'Izin',
                'category' => 'izin',
                'requires_attachment' => false,
                'requires_reason' => true,
                'deducts_quota' => false,
            ],
            [
                'name' => 'Cuti Tahunan',
                'category' => 'cuti',
                'requires_attachment' => false,
                'requires_reason' => false,
                'deducts_quota' => true,
            ],
            [
                'name' => 'Cuti Melahirkan',
                'category' => 'cuti',
                'requires_attachment' => false,
                'requires_reason' => false,
                'deducts_quota' => false,
            ],
            [
                'name' => 'Cuti Menikah',
                'category' => 'cuti',
                'requires_attachment' => false,
                'requires_reason' => false,
                'deducts_quota' => false,
            ],
            [
                'name' => 'Cuti Kematian',
                'category' => 'cuti',
                'requires_attachment' => false,
                'requires_reason' => false,
                'deducts_quota' => false,
            ],
            [
                'name' => 'Cuti Haji / Umroh',
                'category' => 'cuti',
                'requires_attachment' => false,
                'requires_reason' => false,
                'deducts_quota' => false,
            ],
        ];

        foreach ($types as $type) {
            LeaveType::updateOrCreate(
                ['name' => $type['name']],
                $type + ['is_active' => true]
            );
        }
    }
}
