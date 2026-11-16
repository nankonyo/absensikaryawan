<?php

namespace Tests\Feature\Atasan;

use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private Role $staffRole;

    private Role $adminRole;

    private User $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staffRole = Role::create(['name' => 'staff', 'level' => 5, 'display_name' => 'Staff']);
        $secretaryRole = Role::create(['name' => 'sekretaris', 'level' => 2, 'display_name' => 'Sekretaris']);
        $this->adminRole = Role::create(['name' => 'admin', 'level' => 0, 'display_name' => 'Administrator']);

        $secretariat = Department::create(['name' => 'Sekretariat']);
        $position = Position::create([
            'name' => 'Sekretaris Perusahaan',
            'level' => 2,
            'department_id' => $secretariat->id,
        ]);
        $this->verifier = User::factory()->create([
            'role_id' => $secretaryRole->id,
            'department_id' => $secretariat->id,
            'position_id' => $position->id,
        ]);
    }

    public function test_secretariat_verifier_sees_every_employee_grouped_by_department_and_unit(): void
    {
        $operations = Department::create(['name' => 'Operasional']);
        $unit = Unit::create(['name' => 'Project', 'department_id' => $operations->id]);
        $fieldEmployee = $this->makeEmployee('Karyawan Lapangan', $operations, $unit);

        $finance = Department::create(['name' => 'Keuangan']);
        $financeEmployee = $this->makeEmployee('Karyawan Keuangan', $finance);

        $admin = User::factory()->create([
            'name' => 'Administrator Sistem',
            'role_id' => $this->adminRole->id,
        ]);

        $this->actingAs($this->verifier)
            ->get(route('atasan.team-attendance.index'))
            ->assertOk()
            ->assertSee('Operasional — Project')
            ->assertSee('Keuangan — '.__('ui.no_unit'))
            ->assertSee($fieldEmployee->name)
            ->assertSee($financeEmployee->name)
            ->assertDontSee($admin->name);

        $this->actingAs($this->verifier)
            ->get(route('atasan.team-attendance.show', $financeEmployee))
            ->assertOk()
            ->assertSee($financeEmployee->name);
    }

    public function test_general_manager_sees_every_employee_grouped_by_department_and_unit(): void
    {
        $generalManagerRole = Role::create(['name' => 'general_manager', 'level' => 2, 'display_name' => 'General Manager']);
        $generalManager = User::factory()->create(['role_id' => $generalManagerRole->id]);

        $operations = Department::create(['name' => 'Operasional']);
        $unit = Unit::create(['name' => 'Project', 'department_id' => $operations->id]);
        $fieldEmployee = $this->makeEmployee('Karyawan Lapangan', $operations, $unit);

        $finance = Department::create(['name' => 'Keuangan']);
        $financeEmployee = $this->makeEmployee('Karyawan Keuangan', $finance);

        $this->actingAs($generalManager)
            ->get(route('atasan.team-attendance.index'))
            ->assertOk()
            ->assertSee('Operasional — Project')
            ->assertSee('Keuangan — '.__('ui.no_unit'))
            ->assertSee($fieldEmployee->name)
            ->assertSee($financeEmployee->name);

        $this->actingAs($generalManager)
            ->get(route('atasan.team-attendance.show', $financeEmployee))
            ->assertOk()
            ->assertSee($financeEmployee->name);
    }

    public function test_department_filter_scopes_employees_and_reveals_unit_filter(): void
    {
        $operations = Department::create(['name' => 'Operasional']);
        $project = Unit::create(['name' => 'Project', 'department_id' => $operations->id]);
        $support = Unit::create(['name' => 'Support', 'department_id' => $operations->id]);
        $projectEmployee = $this->makeEmployee('Karyawan Project', $operations, $project);
        $supportEmployee = $this->makeEmployee('Karyawan Support', $operations, $support);

        $finance = Department::create(['name' => 'Keuangan']);
        $financeEmployee = $this->makeEmployee('Karyawan Keuangan', $finance);

        $this->actingAs($this->verifier)
            ->get(route('atasan.team-attendance.index'))
            ->assertOk()
            ->assertDontSee('name="unit_id"', false);

        $this->actingAs($this->verifier)
            ->get(route('atasan.team-attendance.index', ['department_id' => $operations->id]))
            ->assertOk()
            ->assertSee('name="unit_id"', false)
            ->assertSee($projectEmployee->name)
            ->assertSee($supportEmployee->name)
            ->assertDontSee($financeEmployee->name);

        $this->actingAs($this->verifier)
            ->get(route('atasan.team-attendance.index', [
                'department_id' => $operations->id,
                'unit_id' => $project->id,
            ]))
            ->assertOk()
            ->assertSee($projectEmployee->name)
            ->assertDontSee($supportEmployee->name)
            ->assertDontSee($financeEmployee->name);
    }

    private function makeEmployee(string $name, Department $department, ?Unit $unit = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'role_id' => $this->staffRole->id,
            'department_id' => $department->id,
            'unit_id' => $unit?->id,
        ]);
    }
}
