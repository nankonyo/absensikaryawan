<?php

namespace Tests\Feature\Verifikasi;

use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Department $secretariat;

    private LeaveType $leaveType;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'manager', 'level' => 3, 'display_name' => 'Manager']);
        Role::create(['name' => 'staff', 'level' => 5, 'display_name' => 'Staff']);
        Role::create(['name' => 'admin', 'level' => 0, 'display_name' => 'Administrator']);

        $this->secretariat = Department::create(['name' => 'Sekretariat']);
        $this->leaveType = LeaveType::create([
            'name' => 'Cuti Tahunan',
            'category' => 'cuti',
            'requires_reason' => false,
            'deducts_quota' => true,
        ]);
    }

    public function test_secretariat_job_level_1_2_and_3_can_open_verification_menu(): void
    {
        $leave = $this->makeLeave();

        foreach ([1, 2, 3] as $level) {
            $verifier = $this->makeUser('manager', $this->secretariat, $level);

            $response = $this->actingAs($verifier)->get(route('verifikasi.leaves.index'));

            $response->assertOk();
            $response->assertSee($leave->user->name);
        }
    }

    public function test_secretariat_verifier_only_sees_the_verification_menu_not_general_approvals(): void
    {
        $verifier = $this->makeUser('manager', $this->secretariat, 3);

        $this->actingAs($verifier)
            ->get(route('atasan.dashboard'))
            ->assertOk()
            ->assertSee(route('verifikasi.leaves.index'), false)
            ->assertDontSee(route('atasan.approvals.index'), false);
    }

    public function test_verification_menu_lists_leave_requests_from_every_employee(): void
    {
        $verifier = $this->makeUser('manager', $this->secretariat, 3);
        $first = $this->makeLeave();

        $otherDepartment = Department::create(['name' => 'Operasional']);
        $secondEmployee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
            'department_id' => $otherDepartment->id,
        ]);
        $second = $this->makeLeave($secondEmployee);

        $this->actingAs($verifier)
            ->get(route('verifikasi.leaves.index'))
            ->assertOk()
            ->assertSee($first->user->name)
            ->assertSee($second->user->name);
    }

    public function test_system_administrator_can_open_verification_menu(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('name', 'admin')->value('id'),
        ]);
        $leave = $this->makeLeave();

        $response = $this->actingAs($admin)->get(route('verifikasi.leaves.index'));

        $response->assertOk();
        $response->assertSee($leave->user->name);
    }

    public function test_user_outside_secretariat_cannot_verify_leave(): void
    {
        $otherDepartment = Department::create(['name' => 'Operasional']);
        $manager = $this->makeUser('manager', $otherDepartment, 3);

        $this->actingAs($manager)
            ->get(route('verifikasi.leaves.index'))
            ->assertForbidden();
    }

    public function test_secretariat_level_4_cannot_verify_leave(): void
    {
        $supervisor = $this->makeUser('manager', $this->secretariat, 4);

        $this->actingAs($supervisor)
            ->get(route('verifikasi.leaves.index'))
            ->assertForbidden();
    }

    public function test_verification_approves_leave_and_records_approver_and_verifier(): void
    {
        $verifier = $this->makeUser('manager', $this->secretariat, 3);
        $leave = $this->makeLeave();

        $this->actingAs($verifier)
            ->post(route('verifikasi.leaves.approve', $leave), [
                'notes' => 'Dokumen lengkap',
            ])
            ->assertRedirect(route('verifikasi.leaves.index'));

        $leave->refresh();

        $this->assertSame('approved', $leave->status);
        $this->assertSame($verifier->id, $leave->approved_by);
        $this->assertSame($verifier->id, $leave->verified_by);
        $this->assertNotNull($leave->approved_at);
        $this->assertNotNull($leave->verified_at);
        $this->assertSame('Dokumen lengkap', $leave->notes);
    }

    public function test_rejection_records_verifier_and_keeps_leave_invalid(): void
    {
        $verifier = $this->makeUser('manager', $this->secretariat, 3);
        $leave = $this->makeLeave();

        $this->actingAs($verifier)
            ->post(route('verifikasi.leaves.reject', $leave), [
                'notes' => 'Tanggal bentrok',
            ])
            ->assertRedirect(route('verifikasi.leaves.index'));

        $leave->refresh();

        $this->assertSame('rejected', $leave->status);
        $this->assertSame($verifier->id, $leave->verified_by);
        $this->assertNotNull($leave->verified_at);
    }

    public function test_already_processed_leave_cannot_be_verified_again(): void
    {
        $verifier = $this->makeUser('manager', $this->secretariat, 3);
        $leave = $this->makeLeave();
        $leave->update([
            'status' => 'approved',
            'approved_by' => $verifier->id,
            'approved_at' => now(),
            'verified_by' => $verifier->id,
            'verified_at' => now(),
        ]);

        $this->actingAs($verifier)
            ->post(route('verifikasi.leaves.approve', $leave))
            ->assertSessionHas('error');
    }

    private function makeUser(string $role, Department $department, int $level): User
    {
        $position = Position::create([
            'name' => 'Jabatan '.$level,
            'level' => $level,
            'department_id' => $department->id,
        ]);

        return User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
    }

    private function makeLeave(?User $employee = null): Leave
    {
        $employee ??= User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        return Leave::create([
            'user_id' => $employee->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-11',
            'days' => 2,
            'leave_type_id' => $this->leaveType->id,
            'reason' => '',
            'status' => 'pending',
        ]);
    }
}
