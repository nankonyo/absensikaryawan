<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSupervisorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_supervisor_is_required_for_non_top_level_roles(): void
    {
        $this->postUser('staff')->assertSessionHasErrors('supervisor_id');
    }

    public function test_supervisor_is_optional_for_direksi(): void
    {
        $this->postUser('direksi')->assertRedirect(route('admin.users.index'));
    }

    public function test_staff_uses_supervisor_of_the_selected_unit(): void
    {
        $this->postUser('staff', [
            'supervisor_id' => User::where('email', 'supervisor.software@example.com')->value('id'),
        ])->assertRedirect(route('admin.users.index'));
    }

    public function test_staff_cannot_use_manager_as_supervisor(): void
    {
        $this->postUser('staff', [
            'supervisor_id' => User::where('email', 'manager.engineering@example.com')->value('id'),
        ])->assertSessionHasErrors('supervisor_id');
    }

    public function test_staff_cannot_use_supervisor_from_another_unit(): void
    {
        $this->postUser('staff', [
            'supervisor_id' => User::where('email', 'supervisor.qa@example.com')->value('id'),
        ])->assertSessionHasErrors('supervisor_id');
    }

    public function test_supervisor_role_uses_manager_of_the_department(): void
    {
        $this->postUser('supervisor', [
            'supervisor_id' => User::where('email', 'manager.engineering@example.com')->value('id'),
        ])->assertRedirect(route('admin.users.index'));
    }

    public function test_supervisor_role_cannot_use_manager_from_another_department(): void
    {
        $this->postUser('supervisor', [
            'supervisor_id' => User::where('email', 'manager.data@example.com')->value('id'),
        ])->assertSessionHasErrors('supervisor_id');
    }

    public function test_supervisor_must_have_a_higher_role_level(): void
    {
        $peer = User::whereHas('role', fn ($query) => $query->where('name', 'staff'))->firstOrFail();

        $this->postUser('staff', ['supervisor_id' => $peer->id])
            ->assertSessionHasErrors('supervisor_id');
    }

    public function test_update_keeps_supervisor_optional_for_admin_role(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'employee_id' => $this->admin->employee_id,
                'role_id' => Role::where('name', 'admin')->value('id'),
            ])
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_create_form_hides_supervisor_requirement_for_top_level_roles(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('userForm')
            ->assertSee(__('ui.supervisor_required'));
    }

    public function test_manager_can_use_department_scoped_general_manager_as_supervisor(): void
    {
        $manager = User::where('employee_id', '1011685019')->firstOrFail();
        $generalManager = User::where('employee_id', '1121580016')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'employee_id' => $manager->employee_id,
                'role_id' => $manager->role_id,
                'supervisor_id' => $generalManager->id,
                'department_id' => $manager->department_id,
                'unit_id' => $manager->unit_id,
                'position_id' => $manager->position_id,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame($generalManager->id, $manager->fresh()->supervisor_id);
    }

    public function test_manager_cannot_use_general_manager_from_another_department(): void
    {
        $manager = User::where('employee_id', '1071574004')->firstOrFail();
        $generalManager = User::where('employee_id', '1121580016')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $manager), [
                'name' => $manager->name,
                'email' => $manager->email,
                'employee_id' => $manager->employee_id,
                'role_id' => $manager->role_id,
                'supervisor_id' => $generalManager->id,
                'department_id' => $manager->department_id,
                'position_id' => $manager->position_id,
            ])
            ->assertSessionHasErrors('supervisor_id');
    }

    public function test_supervisor_can_use_a_unit_manager_from_the_same_department(): void
    {
        $supervisor = User::where('employee_id', '1111578014')->firstOrFail();
        $manager = User::where('employee_id', '1011685019')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $supervisor), [
                'name' => $supervisor->name,
                'email' => $supervisor->email,
                'employee_id' => $supervisor->employee_id,
                'role_id' => $supervisor->role_id,
                'supervisor_id' => $manager->id,
                'department_id' => $supervisor->department_id,
                'unit_id' => $supervisor->unit_id,
                'position_id' => $supervisor->position_id,
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame($manager->id, $supervisor->fresh()->supervisor_id);
    }

    public function test_edit_form_resyncs_supervisor_value_after_options_render(): void
    {
        $supervisor = User::where('email', 'supervisor.software@example.com')->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $supervisor))
            ->assertOk()
            ->assertSee('name="supervisor_id"', false)
            ->assertSee(':value="supervisor"', false);
    }

    private function postUser(string $role, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->admin)->post(route('admin.users.store'), $overrides + [
            'name' => 'Supervisor Test',
            'email' => 'supervisor-test@example.com',
            'password' => 'password123',
            'employee_id' => 'SUP-TEST-001',
            'role_id' => Role::where('name', $role)->value('id'),
            'department_id' => Department::where('name', 'Engineering')->value('id'),
            'unit_id' => Unit::where('name', 'Software Development')->value('id'),
        ]);
    }
}
