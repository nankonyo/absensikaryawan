<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_shows_each_selfie_next_to_its_location_map(): void
    {
        $role = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $attendance = Attendance::create([
            'user_id' => $admin->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'check_in_photo' => 'attendance/check_in/in.jpg',
            'check_in_latitude' => -6.2779463,
            'check_in_longitude' => 106.9731455,
            'check_out' => '17:00:00',
            'check_out_photo' => 'attendance/check_out/out.jpg',
            'check_out_latitude' => -6.2779285,
            'check_out_longitude' => 106.9731389,
            'status' => 'valid',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.attendances.show', $attendance))
            ->assertOk()
            ->assertSee(__('ui.check_in_selfie'))
            ->assertSee(__('ui.check_out_selfie'))
            ->assertSee(__('ui.check_in_location'))
            ->assertSee(__('ui.check_out_location'))
            ->assertSee('attributionControl: false', false)
            ->assertSee('[-6.2779463, 106.9731455]', false)
            ->assertSee('[-6.2779285, 106.9731389]', false)
            ->assertSee('leaflet', false);
    }

    public function test_admin_and_direksi_see_every_employee_attendance_by_default(): void
    {
        $adminRole = Role::create(['name' => 'admin', 'display_name' => 'Admin']);
        $direksiRole = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff']);

        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $direksi = User::factory()->create(['role_id' => $direksiRole->id]);
        $employee = User::factory()->create([
            'name' => 'Karyawan Lain',
            'role_id' => $staffRole->id,
        ]);

        Attendance::create([
            'user_id' => $employee->id,
            'date' => '2025-01-15',
            'check_in' => '08:00:00',
            'status' => 'valid',
        ]);

        foreach ([$admin, $direksi] as $viewer) {
            $this->actingAs($viewer)
                ->get(route('admin.attendances.index'))
                ->assertOk()
                ->assertSee('Karyawan Lain')
                ->assertSee('15/01/2025');
        }
    }
}
