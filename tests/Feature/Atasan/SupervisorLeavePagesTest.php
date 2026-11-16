<?php

namespace Tests\Feature\Atasan;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorLeavePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_open_approvals_page(): void
    {
        [$supervisor, $employee] = $this->makeTeam();
        $leave = $this->makeLeave($employee);

        $response = $this->actingAs($supervisor)->get(route('atasan.approvals.index'));

        $response->assertOk();
        $response->assertSee(__('ui.leave_approval'));
        $response->assertSee($employee->name);
        $response->assertSee(route('atasan.approvals.show', $leave), false);
    }

    public function test_approvals_status_filter_only_shows_matching_leaves(): void
    {
        [$supervisor, $pendingEmployee] = $this->makeTeam();
        $approvedEmployee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
            'supervisor_id' => $supervisor->id,
        ]);

        $this->makeLeave($pendingEmployee, 'pending');
        $this->makeLeave($approvedEmployee, 'approved');

        $this->actingAs($supervisor)
            ->get(route('atasan.approvals.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee($approvedEmployee->name)
            ->assertDontSee($pendingEmployee->name);

        $this->actingAs($supervisor)
            ->get(route('atasan.approvals.index', ['status' => 'all']))
            ->assertOk()
            ->assertSee($approvedEmployee->name)
            ->assertSee($pendingEmployee->name);
    }

    public function test_supervisor_can_open_approval_detail_page(): void
    {
        [$supervisor, $employee] = $this->makeTeam();
        $leave = $this->makeLeave($employee);

        $response = $this->actingAs($supervisor)->get(route('atasan.approvals.show', $leave));

        $response->assertOk();
        $response->assertSee($employee->name);
        $response->assertSee(route('atasan.approvals.approve', $leave), false);
    }

    public function test_supervisor_can_approve_leave_from_approval_detail_page(): void
    {
        [$supervisor, $employee] = $this->makeTeam();
        $leave = $this->makeLeave($employee);

        $this->actingAs($supervisor)
            ->post(route('atasan.approvals.approve', $leave), ['notes' => 'Disetujui'])
            ->assertRedirect(route('atasan.approvals.index'));

        $leave->refresh();

        $this->assertSame('approved', $leave->status);
        $this->assertSame($supervisor->id, $leave->approved_by);
        $this->assertSame('Disetujui', $leave->notes);
    }

    public function test_supervisor_sidebar_separates_personal_menu(): void
    {
        [$supervisor] = $this->makeTeam();

        $response = $this->actingAs($supervisor)->get(route('atasan.approvals.index'));

        $response->assertOk();
        $response->assertSee(__('ui.me'));
        $response->assertSee(route('karyawan.attendance.index'), false);
    }

    private function makeTeam(): array
    {
        $atasanRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor']);
        $karyawanRole = Role::create(['name' => 'staff', 'display_name' => 'Employee']);

        $supervisor = User::factory()->create(['role_id' => $atasanRole->id]);
        $employee = User::factory()->create([
            'role_id' => $karyawanRole->id,
            'supervisor_id' => $supervisor->id,
        ]);

        return [$supervisor, $employee];
    }

    private function makeLeave(User $employee, string $status = 'pending'): Leave
    {
        $type = LeaveType::firstOrCreate(['name' => 'Cuti Tahunan']);

        return Leave::create([
            'user_id' => $employee->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'days' => 2,
            'leave_type_id' => $type->id,
            'reason' => 'Family event',
            'status' => $status,
        ]);
    }
}
