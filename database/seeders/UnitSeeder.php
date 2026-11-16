<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $dep = Department::where('name', 'Unit Manajer Operasional')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'Creative Production'],
                [
                    'department_id' => $dep->id,
                    'description' => 'Unit Creative Production',
                ]
            );
        }

        $dep = Department::where('name', 'Unit Manajer Operasional')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'Administrasi Project'],
                [
                    'department_id' => $dep->id,
                    'description' => NULL,
                ]
            );
        }

        $dep = Department::where('name', 'Unit Manajer Operasional')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'Project Development'],
                [
                    'department_id' => $dep->id,
                    'description' => NULL,
                ]
            );
        }

        $dep = Department::where('name', 'Sekretariat')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'Keuangan'],
                [
                    'department_id' => $dep->id,
                    'description' => NULL,
                ]
            );
        }

        $dep = Department::where('name', 'Sekretariat')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'HR & GA'],
                [
                    'department_id' => $dep->id,
                    'description' => NULL,
                ]
            );
        }

        $dep = Department::where('name', 'Unit Manajer Operasional')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'Marketing'],
                [
                    'department_id' => $dep->id,
                    'description' => NULL,
                ]
            );
        }

        $dep = Department::where('name', 'Unit Manajer Operasional')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'Project'],
                [
                    'department_id' => $dep->id,
                    'description' => NULL,
                ]
            );
        }

        $dep = Department::where('name', 'Unit Manajer Operasional')->first();
        if ($dep) {
            Unit::firstOrCreate(
                ['name' => 'Penjualan'],
                [
                    'department_id' => $dep->id,
                    'description' => NULL,
                ]
            );
        }

    }
}
