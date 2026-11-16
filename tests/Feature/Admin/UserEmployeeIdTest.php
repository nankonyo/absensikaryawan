<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Position;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEmployeeIdTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_store_saves_the_manually_entered_employee_id(): void
    {
        $user = $this->createUser('manual-id@example.com', 'NIK-2026-001', 'Engineering', 'Software Development', 'Software Engineer');

        $this->assertSame('NIK-2026-001', $user->employee_id);
    }

    public function test_employee_id_is_required(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'No ID',
                'email' => 'no-id@example.com',
                'password' => 'password123',
                'role_id' => Role::where('name', 'direksi')->value('id'),
            ])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('users', ['email' => 'no-id@example.com']);
    }

    public function test_employee_id_must_be_unique(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Duplicate ID',
                'email' => 'duplicate-id@example.com',
                'password' => 'password123',
                'employee_id' => $this->admin->employee_id,
                'role_id' => Role::where('name', 'direksi')->value('id'),
            ])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('users', ['email' => 'duplicate-id@example.com']);
    }

    public function test_update_can_change_the_employee_id(): void
    {
        $user = $this->createUser('change-id@example.com', 'NIK-OLD', 'Engineering', 'Software Development', 'Software Engineer');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => 'NIK-NEW',
                'role_id' => $user->role_id,
                'supervisor_id' => User::where('email', 'supervisor.software@example.com')->value('id'),
                'department_id' => Department::where('name', 'Engineering')->value('id'),
                'unit_id' => Unit::where('name', 'Software Development')->value('id'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('NIK-NEW', $user->fresh()->employee_id);
    }

    public function test_create_and_edit_forms_render_manual_employee_id_inputs(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('name="employee_id"', false);

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $this->admin))
            ->assertOk()
            ->assertSee('name="employee_id"', false)
            ->assertSee($this->admin->employee_id);
    }

    public function test_seeded_users_still_have_unique_employee_ids(): void
    {
        $users = User::whereNotNull('employee_id')->get();

        $this->assertSame($users->count(), $users->pluck('employee_id')->unique()->count());
    }

    private function createUser(
        string $email,
        string $employeeId,
        ?string $departmentName = null,
        ?string $unitName = null,
        ?string $positionName = null,
    ): User {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Test '.$email,
                'email' => $email,
                'password' => 'password123',
                'employee_id' => $employeeId,
                'role_id' => Role::where('name', $departmentName ? 'staff' : 'direksi')->value('id'),
                'supervisor_id' => $unitName
                    ? User::where('email', 'supervisor.'.match ($unitName) {
                        'Software Development' => 'software',
                        'Data Engineering' => 'data',
                    }.'@example.com')->value('id')
                    : null,
                'department_id' => $departmentName ? Department::where('name', $departmentName)->value('id') : null,
                'unit_id' => $unitName ? Unit::where('name', $unitName)->value('id') : null,
                'position_id' => $positionName ? Position::where('name', $positionName)->value('id') : null,
            ])
            ->assertRedirect(route('admin.users.index'));

        return User::where('email', $email)->firstOrFail();
    }
}
