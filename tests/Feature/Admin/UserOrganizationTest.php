<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserOrganizationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_store_accepts_position_that_matches_department_and_unit(): void
    {
        $this->postUser([
            'department_id' => $this->departmentId('Engineering'),
            'unit_id' => $this->unitId('Software Development'),
            'position_id' => $this->positionId('Software Engineer'),
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'org-user@example.com', 'position_id' => $this->positionId('Software Engineer')]);
    }

    public function test_store_rejects_position_from_another_unit(): void
    {
        $this->postUser([
            'department_id' => $this->departmentId('Engineering'),
            'unit_id' => $this->unitId('Software Development'),
            'position_id' => $this->positionId('QA Engineer'),
        ])->assertSessionHasErrors('position_id');

        $this->assertDatabaseMissing('users', ['email' => 'org-user@example.com']);
    }

    public function test_store_rejects_position_from_another_department(): void
    {
        $this->postUser([
            'department_id' => $this->departmentId('Engineering'),
            'unit_id' => $this->unitId('Software Development'),
            'position_id' => $this->positionId('Data Engineer'),
        ])->assertSessionHasErrors('position_id');
    }

    public function test_store_rejects_unit_from_another_department(): void
    {
        $this->postUser([
            'department_id' => $this->departmentId('Engineering'),
            'unit_id' => $this->unitId('Data Engineering'),
            'position_id' => $this->positionId('Data Engineer'),
        ])->assertSessionHasErrors('unit_id');
    }

    public function test_store_accepts_company_wide_position_without_department(): void
    {
        $this->postUser([
            'role_id' => Role::where('name', 'direksi')->value('id'),
            'supervisor_id' => null,
            'position_id' => $this->positionId('General Manager'),
        ])->assertRedirect(route('admin.users.index'));
    }

    public function test_manager_can_be_placed_in_a_unit_without_losing_the_manager_role(): void
    {
        $this->postUser([
            'role_id' => Role::where('name', 'manager')->value('id'),
            'supervisor_id' => User::where('email', 'gm@example.com')->value('id'),
            'department_id' => $this->departmentId('Engineering'),
            'unit_id' => $this->unitId('Software Development'),
            'position_id' => $this->positionId('Manager Engineering'),
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'org-user@example.com')->firstOrFail();

        $this->assertSame('manager', $user->role->name);
        $this->assertSame($this->unitId('Software Development'), $user->unit_id);
        $this->assertSame($this->positionId('Manager Engineering'), $user->position_id);
    }

    public function test_create_form_ships_scoped_organization_data(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('userForm')
            ->assertSee('"department_id"', false)
            ->assertSee('"unit_id"', false)
            ->assertSee('\u0022level\u0022:3', false);
    }

    public function test_positions_are_scoped_to_departments_and_units(): void
    {
        $manager = Position::where('name', 'Manager Engineering')->firstOrFail();
        $this->assertSame($this->departmentId('Engineering'), $manager->department_id);
        $this->assertNull($manager->unit_id);

        $engineer = Position::where('name', 'Software Engineer')->firstOrFail();
        $this->assertSame($this->departmentId('Engineering'), $engineer->department_id);
        $this->assertSame($this->unitId('Software Development'), $engineer->unit_id);

        $director = Position::where('name', 'Direktur Utama')->firstOrFail();
        $this->assertNull($director->department_id);
        $this->assertNull($director->unit_id);
    }

    public function test_edit_form_ships_the_saved_manager_position_without_locking_it(): void
    {
        $manager = User::where('employee_id', '1071574004')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $manager))
            ->assertOk()
            ->assertDontSee('x-show="positionLocked"', false)
            ->assertSee('\u0022position\u0022:\u0022'.$manager->position_id.'\u0022', false);
    }

    private function postUser(array $overrides): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.users.store'), $overrides + [
            'name' => 'Org User',
            'email' => 'org-user@example.com',
            'password' => 'password123',
            'employee_id' => 'ORG-TEST-001',
            'role_id' => Role::where('name', 'staff')->value('id'),
            'supervisor_id' => User::where('email', 'supervisor.software@example.com')->value('id'),
        ]);
    }

    private function departmentId(string $name): int
    {
        return Department::where('name', $name)->value('id');
    }

    private function unitId(string $name): int
    {
        return Unit::where('name', $name)->value('id');
    }

    private function positionId(string $name): int
    {
        return Position::where('name', $name)->value('id');
    }
}
