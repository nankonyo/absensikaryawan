<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::firstOrCreate(
            ['name' => 'Pusat'],
            ['code' => 'PUSAT', 'address' => null, 'is_active' => true],
        );
    }
}
