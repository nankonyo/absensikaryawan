<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Sekretariat', 'description' => 'Sekretariat perusahaan'],
            ['name' => 'Unit Manajer Operasional', 'description' => 'Unit Manajer Operasional'],
        ];

        foreach ($departments as $dept) {
            Department::firstOrCreate(['name' => $dept['name']], $dept);
        }
    }
}
