<?php

namespace Tests\Feature\Karyawan;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeavePagesTest extends TestCase
{
    use RefreshDatabase;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'staff', 'display_name' => 'Employee']);
        $this->employee = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_leave_list_only_shows_view_action(): void
    {
        $leave = $this->makeLeave($this->employee);

        $response = $this->actingAs($this->employee)->get(route('karyawan.leaves.index'));

        $response->assertOk();
        $response->assertSee(route('karyawan.leaves.show', $leave), false);
        $response->assertDontSee(route('karyawan.leaves.edit', $leave), false);
    }

    public function test_employee_can_view_own_leave(): void
    {
        $leave = $this->makeLeave($this->employee);

        $response = $this->actingAs($this->employee)->get(route('karyawan.leaves.show', $leave));

        $response->assertOk();
        $response->assertSee('Family event');
    }

    public function test_employee_cannot_view_another_employees_leave(): void
    {
        $colleague = User::factory()->create(['role_id' => $this->employee->role_id]);
        $leave = $this->makeLeave($colleague);

        $response = $this->actingAs($this->employee)->get(route('karyawan.leaves.show', $leave));

        $response->assertForbidden();
    }

    public function test_leave_form_exposes_rule_flags_for_dynamic_fields(): void
    {
        LeaveType::create([
            'name' => 'Sakit',
            'category' => 'sakit',
            'requires_attachment' => true,
            'requires_reason' => false,
        ]);
        LeaveType::create([
            'name' => 'Izin',
            'category' => 'izin',
            'requires_attachment' => false,
            'requires_reason' => true,
        ]);

        $response = $this->actingAs($this->employee)->get(route('karyawan.leaves.create'));

        $response->assertOk();
        $response->assertSee('id="reason_wrapper"', false);
        $response->assertSee('id="attachment_wrapper"', false);
        $response->assertSee('data-requires-attachment="1"', false);
        $response->assertSee('data-requires-reason="1"', false);
    }

    public function test_karyawan_sidebar_uses_personal_menu_names(): void
    {
        $response = $this->actingAs($this->employee)->get(route('karyawan.leaves.index'));

        $response->assertOk();
        $response->assertSee(__('ui.my_attendance'));
        $response->assertSee(__('ui.my_leave_requests'));
    }

    private function makeLeave(User $user): Leave
    {
        $type = LeaveType::firstOrCreate(['name' => 'Cuti Tahunan']);

        return Leave::create([
            'user_id' => $user->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'days' => 2,
            'leave_type_id' => $type->id,
            'reason' => 'Family event',
            'status' => 'pending',
        ]);
    }
}
