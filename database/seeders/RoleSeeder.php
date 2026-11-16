<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'admin', 'level' => 0, 'display_name' => 'Administrator Sistem', 'description' => 'System administrator, full access to all data'],
            ['name' => 'direksi', 'level' => 1, 'display_name' => 'Direksi', 'description' => 'Board of directors, full access to all data'],
            ['name' => 'general_manager', 'level' => 2, 'display_name' => 'General Manager', 'description' => 'Manages all departments and managers'],
            ['name' => 'sekretaris', 'level' => 2, 'display_name' => 'Sekretaris', 'description' => 'Company secretary, same level as the general manager'],
            ['name' => 'manager', 'level' => 3, 'display_name' => 'Manager', 'description' => 'Manages one department and its supervisors'],
            ['name' => 'supervisor', 'level' => 4, 'display_name' => 'Supervisor', 'description' => 'Manages one unit and its staff'],
            ['name' => 'staff', 'level' => 5, 'display_name' => 'Staff', 'description' => 'Takes attendance and requests leave'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
