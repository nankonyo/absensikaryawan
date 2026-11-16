<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_admin_sidebar_and_page_expose_complete_team_attendance(): void
    {
        $department = Department::create(['name' => 'Operasional']);
        $unit = Unit::create(['name' => 'Project', 'department_id' => $department->id]);
        $employee = $this->makeEmployee('Karyawan Lapangan', $department, $unit);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.team-attendance.index'), false);

        $this->actingAs($this->admin)
            ->get(route('admin.team-attendance.index'))
            ->assertOk()
            ->assertSee('Operasional — Project')
            ->assertSee($employee->name)
            ->assertSee(route('admin.team-attendance.show', $employee), false)
            ->assertDontSee(route('atasan.team-attendance.show', $employee), false);

        $this->actingAs($this->admin)
            ->get(route('admin.team-attendance.show', $employee))
            ->assertOk()
            ->assertSee($employee->name);
    }

    public function test_direksi_sees_every_employee_in_team_attendance(): void
    {
        $direksi = User::factory()->create([
            'role_id' => Role::where('name', 'direksi')->value('id'),
        ]);

        $department = Department::create(['name' => 'Keuangan']);
        $employee = $this->makeEmployee('Karyawan Keuangan', $department);

        $this->actingAs($direksi)
            ->get(route('admin.team-attendance.index'))
            ->assertOk()
            ->assertSee('Keuangan — '.__('ui.no_unit'))
            ->assertSee($employee->name);
    }

    private function makeEmployee(string $name, Department $department, ?Unit $unit = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => Role::where('name', 'staff')->value('id'),
            'department_id' => $department->id,
            'unit_id' => $unit?->id,
        ]);
    }
}
