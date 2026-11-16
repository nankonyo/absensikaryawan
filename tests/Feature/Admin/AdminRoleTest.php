<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_system_admin_account(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame('admin', $admin->role->name);
        $this->assertSame(0, $admin->role->level);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_admin_role_can_access_admin_dashboard(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_dashboard_counts_employees_excluding_system_admin(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $employeeQuery = User::whereHas('role', fn ($query) => $query->where('name', '!=', 'admin'));
        $expected = (clone $employeeQuery)->count();
        $permanent = (clone $employeeQuery)->where('employment_status', 'permanent')->count();
        $contract = (clone $employeeQuery)->where('employment_status', 'contract')->count();

        $this->assertGreaterThan(0, $expected);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('totalKaryawan', $expected)
            ->assertViewHas('permanentEmployees', $permanent)
            ->assertViewHas('contractEmployees', $contract)
            ->assertSee(__('ui.employment_permanent').': '.$permanent)
            ->assertSee(__('ui.employment_contract').': '.$contract);
    }

    public function test_seeder_creates_secretary_on_the_general_manager_level(): void
    {
        $this->seed();

        $role = Role::where('name', 'sekretaris')->firstOrFail();

        $this->assertSame(2, $role->level);
        $this->assertSame('Sekretaris', $role->display_name);
    }

    public function test_secretary_can_open_the_team_dashboard_and_take_attendance(): void
    {
        $this->seed();

        $secretary = User::factory()->create([
            'role_id' => Role::where('name', 'sekretaris')->value('id'),
            'supervisor_id' => User::where('email', 'direksi@example.com')->value('id'),
        ]);

        $this->actingAs($secretary)->get('/dashboard')->assertRedirect(route('atasan.dashboard'));
        $this->actingAs($secretary)->get(route('atasan.dashboard'))->assertOk();
        $this->actingAs($secretary)->get(route('karyawan.attendance.index'))->assertOk();
    }
}
