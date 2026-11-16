<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            DepartmentSeeder::class,
            UnitSeeder::class,
            PositionSeeder::class,
            BranchSeeder::class,
            UserAttributeSeeder::class,
            UserSeeder::class,
            LeaveTypeSeeder::class,
            OfficeLocationSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
