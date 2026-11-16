<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_unit_master_page_lists_units(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.units.index'))
            ->assertOk()
            ->assertSee('Software Development')
            ->assertDontSee('name="code"', false);
    }

    public function test_unit_can_be_created_updated_and_deleted(): void
    {
        $department = Department::where('name', 'Engineering')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('admin.units.store'), [
                'department_id' => $department->id,
                'name' => 'Platform Engineering',
                'description' => 'Platform team',
            ])
            ->assertRedirect();

        $unit = Unit::where('name', 'Platform Engineering')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.units.update', $unit), [
                'department_id' => $department->id,
                'name' => 'Platform & Infrastructure',
                'description' => 'Platform and infrastructure team',
            ])
            ->assertRedirect();

        $this->assertSame('Platform & Infrastructure', $unit->fresh()->name);

        $this->actingAs($this->admin)
            ->delete(route('admin.units.destroy', $unit))
            ->assertRedirect();

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }
}
