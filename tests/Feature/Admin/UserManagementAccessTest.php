<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementAccessTest extends TestCase
{
    use RefreshDatabase;

    private Department $secretariat;

    private Role $managerRole;

    private Role $staffRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->managerRole = Role::create(['name' => 'manager', 'level' => 3, 'display_name' => 'Manager']);
        $this->staffRole = Role::create(['name' => 'staff', 'level' => 5, 'display_name' => 'Staff']);
        $this->secretariat = Department::create(['name' => 'Sekretariat']);
    }

    public function test_secretariat_level_1_2_and_3_can_open_user_management_pages(): void
    {
        foreach ([1, 2, 3] as $level) {
            $verifier = $this->makeUser($level);

            $this->actingAs($verifier)->get(route('admin.users.index'))->assertOk();
            $this->actingAs($verifier)->get(route('admin.users.create'))->assertOk();
            $this->actingAs($verifier)->get(route('admin.users.show', $verifier))->assertOk();
            $this->actingAs($verifier)->get(route('admin.users.edit', $verifier))->assertOk();
        }
    }

    public function test_secretariat_verifier_has_full_user_crud_access(): void
    {
        $verifier = $this->makeUser(3);
        $target = User::factory()->create(['role_id' => $this->staffRole->id]);

        $this->actingAs($verifier)->post(route('admin.users.store'), [])->assertStatus(302);
        $this->actingAs($verifier)->put(route('admin.users.update', $target), [])->assertStatus(302);
        $this->actingAs($verifier)->delete(route('admin.users.destroy', $target))->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_secretariat_verifier_sees_user_management_menu(): void
    {
        $verifier = $this->makeUser(3);

        $this->actingAs($verifier)
            ->get(route('atasan.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.users.index'), false);
    }

    public function test_other_departments_and_secretariat_level_4_cannot_manage_users(): void
    {
        $otherDepartment = Department::create(['name' => 'Operasional']);

        $this->actingAs($this->makeUser(3, $otherDepartment))
            ->get(route('admin.users.index'))
            ->assertForbidden();

        $this->actingAs($this->makeUser(4))
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    private function makeUser(int $level, ?Department $department = null): User
    {
        $department ??= $this->secretariat;

        $position = Position::create([
            'name' => 'Jabatan '.$level.' '.$department->name,
            'level' => $level,
            'department_id' => $department->id,
        ]);

        return User::factory()->create([
            'role_id' => $this->managerRole->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
    }
}
