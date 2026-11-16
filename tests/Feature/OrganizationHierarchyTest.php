<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_descendant_ids_include_every_level_below_the_user(): void
    {
        [$gm, $manager, $supervisor, $staff] = $this->makeHierarchy();

        $this->assertEqualsCanonicalizing(
            [$manager->id, $supervisor->id, $staff->id],
            $gm->descendantIds()
        );

        $this->assertEqualsCanonicalizing([$staff->id], $supervisor->descendantIds());
    }

    public function test_manager_team_page_groups_team_by_department_and_labels_roles(): void
    {
        [$gm, $manager, $supervisor, $staff] = $this->makeHierarchy();

        $response = $this->actingAs($manager)->get(route('atasan.team-attendance.index'));

        $response->assertOk();
        $response->assertSee('Engineering');
        $response->assertSee($supervisor->name);
        $response->assertSee($staff->name);
        $response->assertSee('Supervisor');
        $response->assertSee('Staff');
    }

    public function test_supervisor_team_page_groups_team_by_unit(): void
    {
        [$gm, $manager, $supervisor, $staff] = $this->makeHierarchy();
        $otherUnit = Unit::create([
            'department_id' => $manager->department_id,
            'name' => 'Quality Assurance',
        ]);

        $this->actingAs($supervisor)->get(route('atasan.team-attendance.index'))
            ->assertOk()
            ->assertSee('Software Development')
            ->assertSee($staff->name)
            ->assertDontSee($otherUnit->name);
    }

    public function test_manager_can_open_team_member_attendance_history(): void
    {
        [$gm, $manager, $supervisor, $staff] = $this->makeHierarchy();

        $this->actingAs($manager)
            ->get(route('atasan.team-attendance.show', $staff))
            ->assertOk();
    }

    public function test_role_with_supervisor_can_take_attendance(): void
    {
        [$gm] = $this->makeHierarchy();

        $this->actingAs($gm)->get(route('karyawan.attendance.index'))->assertOk();
    }

    public function test_seeded_managers_report_to_their_department_leader(): void
    {
        $this->seed();

        $managerRoleId = Role::where('name', 'manager')->value('id');
        $umo = Department::where('name', 'Unit Manajer Operasional')->firstOrFail();
        $sekretariat = Department::where('name', 'Sekretariat')->firstOrFail();
        $tresna = User::where('name', 'Tresna Permana')->firstOrFail();
        $anto = User::where('name', 'Anto Darmanto')->firstOrFail();

        $umoManagers = User::where('role_id', $managerRoleId)
            ->where('department_id', $umo->id)
            ->get();
        $sekretariatManagers = User::where('role_id', $managerRoleId)
            ->where('department_id', $sekretariat->id)
            ->get();

        $this->assertNotEmpty($umoManagers);
        $this->assertNotEmpty($sekretariatManagers);

        foreach ($umoManagers as $manager) {
            $this->assertSame($tresna->id, $manager->supervisor_id);
        }

        foreach ($sekretariatManagers as $manager) {
            $this->assertSame($anto->id, $manager->supervisor_id);
        }
    }

    public function test_seeded_project_unit_manager_supervises_all_staff(): void
    {
        $this->seed();

        $project = Unit::where('name', 'Project')->firstOrFail();
        $manager = User::where('employee_id', '1011685019')->firstOrFail();
        $tresna = User::where('name', 'Tresna Permana')->firstOrFail();
        $staff = User::whereHas('role', fn ($query) => $query->where('name', 'staff'))->get();

        $this->assertSame('manager', $manager->role->name);
        $this->assertSame($project->id, $manager->unit_id);
        $this->assertSame($tresna->id, $manager->supervisor_id);
        $this->assertNotEmpty($staff);

        foreach ($staff as $employee) {
            $this->assertSame($manager->id, $employee->supervisor_id);
        }
    }

    public function test_seeded_creative_production_supervisor_reports_to_project_manager(): void
    {
        $this->seed();

        $supervisor = User::where('employee_id', '1111578014')->firstOrFail();
        $manager = User::where('employee_id', '1011685019')->firstOrFail();

        $this->assertSame('supervisor', $supervisor->role->name);
        $this->assertSame('SPV Creative Production', $supervisor->positionMaster?->name);
        $this->assertSame('Creative Production', $supervisor->unit?->name);
        $this->assertSame($manager->id, $supervisor->supervisor_id);
    }

    public function test_direksi_and_users_without_supervisor_cannot_take_attendance(): void
    {
        $direksiRole = Role::create(['name' => 'direksi', 'display_name' => 'Direksi', 'level' => 1]);
        $direksi = User::factory()->create(['role_id' => $direksiRole->id]);

        $this->actingAs($direksi)->get(route('karyawan.attendance.index'))->assertForbidden();

        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);
        $staff = User::factory()->create(['role_id' => $staffRole->id]);

        $this->actingAs($staff)->get(route('karyawan.attendance.index'))->assertForbidden();
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: User}
     */
    private function makeHierarchy(): array
    {
        $direksiRole = Role::create(['name' => 'direksi', 'display_name' => 'Direksi', 'level' => 1]);
        $gmRole = Role::create(['name' => 'general_manager', 'display_name' => 'General Manager', 'level' => 2]);
        $managerRole = Role::create(['name' => 'manager', 'display_name' => 'Manager', 'level' => 3]);
        $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor', 'level' => 4]);
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);

        $department = Department::create(['name' => 'Engineering']);
        $unit = Unit::create([
            'department_id' => $department->id,
            'name' => 'Software Development',
        ]);

        $direksi = User::factory()->create(['role_id' => $direksiRole->id]);
        $gm = User::factory()->create([
            'role_id' => $gmRole->id,
            'supervisor_id' => $direksi->id,
        ]);
        $manager = User::factory()->create([
            'role_id' => $managerRole->id,
            'department_id' => $department->id,
            'supervisor_id' => $gm->id,
        ]);
        $supervisor = User::factory()->create([
            'role_id' => $supervisorRole->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'supervisor_id' => $manager->id,
        ]);
        $staff = User::factory()->create([
            'role_id' => $staffRole->id,
            'department_id' => $department->id,
            'unit_id' => $unit->id,
            'supervisor_id' => $supervisor->id,
        ]);

        return [$gm, $manager, $supervisor, $staff];
    }
}
