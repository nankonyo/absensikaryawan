<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Position;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $dept_1 = Department::where('name', 'Sekretariat')->first();
        $dept_2 = Department::where('name', 'Unit Manajer Operasional')->first();
        $unit_2 = Unit::where('name', 'Creative Production')->first();
        $unit_3 = Unit::where('name', 'Administrasi Project')->first();
        $unit_4 = Unit::where('name', 'Project Development')->first();
        $unit_5 = Unit::where('name', 'Keuangan')->first();
        $unit_6 = Unit::where('name', 'HR & GA')->first();
        $unit_7 = Unit::where('name', 'Marketing')->first();
        $unit_8 = Unit::where('name', 'Project')->first();
        $unit_9 = Unit::where('name', 'Penjualan')->first();

        $positions = [
            ['name' => 'Direktur', 'level' => 1, 'department_id' => null, 'unit_id' => null],
            ['name' => 'Sekretaris Perusahaan', 'level' => 2, 'department_id' => null, 'unit_id' => null],
            ['name' => 'General Manager', 'level' => 2, 'department_id' => null, 'unit_id' => null],
            ['name' => 'Manajer Keuangan', 'level' => 3, 'department_id' => $dept_1?->id, 'unit_id' => null],
            ['name' => 'Manager HRD & GA', 'level' => 3, 'department_id' => $dept_1?->id, 'unit_id' => null],
            ['name' => 'Manajer Proyek', 'level' => 3, 'department_id' => $dept_2?->id, 'unit_id' => null],
            ['name' => 'Marketing Manager', 'level' => 3, 'department_id' => $dept_2?->id, 'unit_id' => null],
            ['name' => 'SPV Creative Production', 'level' => 4, 'department_id' => $dept_2?->id, 'unit_id' => $unit_2?->id],
            ['name' => 'Staff Creative Production', 'level' => 5, 'department_id' => $dept_2?->id, 'unit_id' => $unit_2?->id],
            ['name' => 'Sales Admin', 'level' => 5, 'department_id' => $dept_2?->id, 'unit_id' => $unit_9?->id],
            ['name' => 'Staff GA', 'level' => 5, 'department_id' => $dept_1?->id, 'unit_id' => $unit_6?->id],
            ['name' => 'Programer', 'level' => 5, 'department_id' => $dept_2?->id, 'unit_id' => $unit_4?->id],
            ['name' => 'Admin UMO', 'level' => 5, 'department_id' => $dept_2?->id, 'unit_id' => $unit_8?->id],
            ['name' => 'Akunting', 'level' => 5, 'department_id' => $dept_1?->id, 'unit_id' => $unit_5?->id],
            ['name' => 'HRD', 'level' => 5, 'department_id' => $dept_1?->id, 'unit_id' => $unit_6?->id],
            ['name' => 'Konsultan', 'level' => 5, 'department_id' => $dept_2?->id, 'unit_id' => $unit_8?->id],
            ['name' => 'SPV Help Desk', 'level' => 4, 'department_id' => $dept_2?->id, 'unit_id' => $unit_4?->id],
        ];

        foreach ($positions as $pos) {
            Position::firstOrCreate(['name' => $pos['name']], $pos);
        }
    }
}
