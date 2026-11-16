<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $employeeRole = Role::create(['name' => 'karyawan', 'display_name' => 'Employee']);

        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->employee = User::factory()->create(['role_id' => $employeeRole->id]);
    }

    public function test_admin_leave_page_uses_approval_menu_name(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.leaves.index'))
            ->assertOk()
            ->assertSee(__('ui.leave_approval'));
    }

    public function test_admin_can_open_leave_detail_page(): void
    {
        $leave = $this->makeLeave();

        $response = $this->actingAs($this->admin)->get(route('admin.leaves.show', $leave));

        $response->assertOk();
        $response->assertSee($this->employee->name);
        $response->assertSee('Family event');
        $response->assertSee(route('admin.leaves.approve', $leave), false);
    }

    public function test_admin_can_approve_leave_from_detail_page(): void
    {
        $leave = $this->makeLeave();

        $response = $this->actingAs($this->admin)->post(route('admin.leaves.approve', $leave), [
            'notes' => 'Approved by admin',
        ]);

        $response->assertRedirect(route('admin.leaves.index'));

        $leave->refresh();
        $this->assertSame('approved', $leave->status);
        $this->assertSame($this->admin->id, $leave->approved_by);
        $this->assertSame('Approved by admin', $leave->notes);
    }

    public function test_leave_index_shows_employee_identity_and_department_without_unit(): void
    {
        $department = Department::create(['name' => 'Engineering']);
        $unit = Unit::create(['name' => 'Software Development', 'department_id' => $department->id]);

        $this->employee->update([
            'employee_id' => 'EMP-LEAVE-01',
            'department_id' => $department->id,
            'unit_id' => $unit->id,
        ]);

        $this->makeLeave();

        $this->actingAs($this->admin)
            ->get(route('admin.leaves.index'))
            ->assertOk()
            ->assertSee($this->employee->name)
            ->assertSee('EMP-LEAVE-01')
            ->assertSee('Engineering')
            ->assertDontSee('Software Development')
            ->assertSee('rounded-full bg-blue-100', false);
    }

    public function test_leave_dates_use_day_month_year_format(): void
    {
        $leave = $this->makeLeave();

        $this->actingAs($this->admin)
            ->get(route('admin.leaves.show', $leave))
            ->assertOk()
            ->assertSee('10/11/2026')
            ->assertSee('11/11/2026')
            ->assertDontSee('10 Nov 2026')
            ->assertDontSee('10 November 2026');
    }

    public function test_leave_status_badge_uses_neutral_white_style(): void
    {
        $leave = $this->makeLeave();

        $this->actingAs($this->admin)
            ->get(route('admin.leaves.show', $leave))
            ->assertOk()
            ->assertSee('inline-flex items-center rounded border border-gray-300 bg-white', false)
            ->assertDontSee('rounded-full bg-yellow-100', false);
    }

    private function makeLeave(): Leave
    {
        $type = LeaveType::firstOrCreate(['name' => 'Cuti Tahunan']);

        return Leave::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'days' => 2,
            'leave_type_id' => $type->id,
            'reason' => 'Family event',
            'status' => 'pending',
        ]);
    }
}
