<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_filters_users_by_name_email_and_employee_id(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'employee_id' => 'EMP001',
            'role_id' => $role->id,
        ]);
        $target = User::factory()->create([
            'name' => 'Zulaikha Target',
            'email' => 'zulaikha@example.com',
            'employee_id' => 'EMP777',
            'role_id' => $role->id,
        ]);
        $other = User::factory()->create([
            'name' => 'Other Person',
            'email' => 'other@example.com',
            'employee_id' => 'EMP888',
            'role_id' => $role->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Zulaikha']))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'zulaikha@example.com']))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'EMP777']))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name);
    }

    public function test_search_filters_users_by_position_and_placeholder_matches_columns(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        $target = User::factory()->create([
            'role_id' => $role->id,
            'position' => 'Software Engineer',
        ]);
        $other = User::factory()->create([
            'role_id' => $role->id,
            'position' => 'Akuntan',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'Software Engineer']))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Cari nama, email, peran, departemen, atau jabatan...')
            ->assertDontSee('NIK');
    }

    public function test_department_and_unit_filters_scope_the_user_list(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $engineering = Department::create(['name' => 'Engineering']);
        $data = Department::create(['name' => 'Data & AI']);
        $software = Unit::create(['name' => 'Software Development', 'department_id' => $engineering->id]);
        $dataEngineering = Unit::create(['name' => 'Data Engineering', 'department_id' => $data->id]);

        $softwareUser = User::factory()->create([
            'name' => 'Software Person',
            'role_id' => $role->id,
            'department_id' => $engineering->id,
            'unit_id' => $software->id,
        ]);
        $managerUser = User::factory()->create([
            'name' => 'Engineering Manager',
            'role_id' => $role->id,
            'department_id' => $engineering->id,
        ]);
        $dataUser = User::factory()->create([
            'name' => 'Data Person',
            'role_id' => $role->id,
            'department_id' => $data->id,
            'unit_id' => $dataEngineering->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['department_id' => $engineering->id]))
            ->assertOk()
            ->assertSee($softwareUser->name)
            ->assertSee($managerUser->name)
            ->assertDontSee($dataUser->name)
            ->assertSee($software->name);

        $this->actingAs($admin)
            ->get(route('admin.users.index', [
                'department_id' => $engineering->id,
                'unit_id' => $software->id,
            ]))
            ->assertOk()
            ->assertSee($softwareUser->name)
            ->assertDontSee($managerUser->name)
            ->assertDontSee($dataUser->name);
    }

    public function test_unit_filter_is_hidden_without_a_department(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);
        Unit::create([
            'name' => 'Software Development',
            'department_id' => Department::create(['name' => 'Engineering'])->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('name="unit_id"', false);
    }

    public function test_user_list_shows_empty_state_when_no_user_matches(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'tidak-ada-yang-cocok']))
            ->assertOk()
            ->assertSee(__('ui.no_users_found'))
            ->assertSee('table-empty', false);
    }
}
