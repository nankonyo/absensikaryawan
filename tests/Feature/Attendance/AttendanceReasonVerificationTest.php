<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceReason;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Services\GpsValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AttendanceReasonVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_outside_zone_check_in_requires_an_attendance_reason(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $result = $this->service(valid: false, distance: 150)->checkIn($user, $this->payload());

        $this->assertFalse($result['success']);
        $this->assertSame(__('ui.attendance_reason_required'), $result['message']);
        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }

    public function test_outside_zone_check_in_is_pending_until_verified(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $reason = AttendanceReason::where('name', 'Tugas luar kota')->first()
            ?? AttendanceReason::create(['name' => 'Tugas luar kota', 'is_active' => true]);

        $result = $this->service(valid: false, distance: 150)
            ->checkIn($user, $this->payload(['attendance_reason_id' => $reason->id]));

        $this->assertTrue($result['success']);
        $this->assertSame('pending', $result['attendance']->status);
        $this->assertSame($reason->id, $result['attendance']->attendance_reason_id);
        $this->assertSame(__('ui.check_in_pending'), $result['message']);
    }

    public function test_inside_zone_check_in_stays_valid_without_reason(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $result = $this->service(valid: true, distance: 10)->checkIn($user, $this->payload());

        $this->assertTrue($result['success']);
        $this->assertSame('valid', $result['attendance']->status);
        $this->assertNull($result['attendance']->attendance_reason_id);
    }

    public function test_supervisor_outside_secretariat_only_sees_their_team_pending_attendance(): void
    {
        $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor', 'level' => 4]);
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);

        $supervisor = User::factory()->create(['role_id' => $supervisorRole->id]);
        $member = User::factory()->create(['role_id' => $staffRole->id, 'supervisor_id' => $supervisor->id]);
        $outsider = User::factory()->create(['role_id' => $staffRole->id]);

        $memberAttendance = $this->pendingAttendance($member);
        $outsiderAttendance = $this->pendingAttendance($outsider);

        $this->actingAs($supervisor)
            ->get(route('atasan.attendance-verifications.index'))
            ->assertOk()
            ->assertSee($member->name)
            ->assertDontSee($outsider->name)
            ->assertSee(route('atasan.attendance-verifications.approve', $memberAttendance), false)
            ->assertDontSee(route('atasan.attendance-verifications.approve', $outsiderAttendance), false);
    }

    public function test_secretariat_level_two_sees_all_pending_attendance(): void
    {
        $secretariat = Department::create(['name' => 'Sekretariat']);
        $secretaryRole = Role::create(['name' => 'sekretaris', 'display_name' => 'Sekretaris', 'level' => 2]);
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);

        $secretary = User::factory()->create([
            'role_id' => $secretaryRole->id,
            'department_id' => $secretariat->id,
        ]);
        $first = User::factory()->create(['role_id' => $staffRole->id]);
        $second = User::factory()->create(['role_id' => $staffRole->id]);

        $this->pendingAttendance($first);
        $this->pendingAttendance($second);

        $this->actingAs($secretary)
            ->get(route('atasan.attendance-verifications.index'))
            ->assertOk()
            ->assertSee($first->name)
            ->assertSee($second->name);
    }

    public function test_supervisor_can_approve_team_pending_attendance(): void
    {
        $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor', 'level' => 4]);
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);
        $supervisor = User::factory()->create(['role_id' => $supervisorRole->id]);
        $member = User::factory()->create(['role_id' => $staffRole->id, 'supervisor_id' => $supervisor->id]);
        $attendance = $this->pendingAttendance($member);

        $this->actingAs($supervisor)
            ->post(route('atasan.attendance-verifications.approve', $attendance), ['notes' => 'Sesuai tugas'])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'valid',
            'verified_by' => $supervisor->id,
            'verification_notes' => 'Sesuai tugas',
        ]);
    }

    public function test_supervisor_cannot_verify_an_outsider(): void
    {
        $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor', 'level' => 4]);
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);
        $supervisor = User::factory()->create(['role_id' => $supervisorRole->id]);
        $outsider = User::factory()->create(['role_id' => $staffRole->id]);
        $attendance = $this->pendingAttendance($outsider);

        $this->actingAs($supervisor)
            ->post(route('atasan.attendance-verifications.approve', $attendance))
            ->assertForbidden();

        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 'pending']);
    }

    public function test_rejecting_pending_attendance_requires_notes_and_marks_it_invalid(): void
    {
        $supervisorRole = Role::create(['name' => 'supervisor', 'display_name' => 'Supervisor', 'level' => 4]);
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);
        $supervisor = User::factory()->create(['role_id' => $supervisorRole->id]);
        $member = User::factory()->create(['role_id' => $staffRole->id, 'supervisor_id' => $supervisor->id]);
        $attendance = $this->pendingAttendance($member);

        $this->actingAs($supervisor)
            ->post(route('atasan.attendance-verifications.reject', $attendance), [])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseHas('attendances', ['id' => $attendance->id, 'status' => 'pending']);

        $this->actingAs($supervisor)
            ->post(route('atasan.attendance-verifications.reject', $attendance), ['notes' => 'Tidak sesuai'])
            ->assertRedirect();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'invalid',
            'verified_by' => $supervisor->id,
        ]);
    }

    public function test_check_in_page_hides_reason_picker_until_location_is_outside_zone(): void
    {
        $staffRole = Role::create(['name' => 'staff', 'display_name' => 'Staff', 'level' => 5]);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $staffRole->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $this->actingAs($user)
            ->get(route('karyawan.attendance.check-in'))
            ->assertOk()
            ->assertSee('x-show="needsReason"', false)
            ->assertSee('name="attendance_reason_choice"', false)
            ->assertSee('name="notes"', false)
            ->assertSee('distanceToOffice', false)
            ->assertDontSee('<select id="attendance-reason"', false);
    }

    public function test_outside_zone_check_in_saves_the_written_reason_note(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $reason = AttendanceReason::where('name', 'WFH')->first();

        $result = $this->service(valid: false, distance: 150)->checkIn($user, $this->payload([
            'attendance_reason_id' => $reason->id,
            'notes' => 'Mengerjakan laporan di rumah',
        ]));

        $this->assertTrue($result['success']);
        $this->assertSame('Mengerjakan laporan di rumah', $result['attendance']->notes);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'notes' => 'Mengerjakan laporan di rumah',
        ]);
    }

    public function test_inside_zone_check_in_ignores_reason_note(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $result = $this->service(valid: true, distance: 10)->checkIn($user, $this->payload([
            'notes' => 'Catatan yang tidak relevan',
        ]));

        $this->assertTrue($result['success']);
        $this->assertNull($result['attendance']->notes);
    }

    private function service(bool $valid, float $distance): AttendanceService
    {
        $gps = Mockery::mock(GpsValidationService::class);
        $gps->shouldReceive('validateLocation')->andReturn([
            'valid' => $valid,
            'message' => $valid ? 'Location valid' : 'Outside radius',
            'distance' => $distance,
        ]);

        $face = Mockery::mock(FaceRecognitionService::class);
        $face->shouldReceive('isEnabled')->andReturnFalse();

        return new AttendanceService($gps, $face);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => -7.25,
            'longitude' => 112.75,
            'photo_data' => 'data:image/jpeg;base64,'.base64_encode('fake-image'),
        ], $overrides);
    }

    private function pendingAttendance(User $user): Attendance
    {
        $reason = AttendanceReason::firstOrCreate(['name' => 'WFO'], ['is_active' => true]);

        return Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'pending',
            'attendance_reason_id' => $reason->id,
            'check_in_latitude' => -7.25,
            'check_in_longitude' => 112.75,
            'check_in_address' => '-7.25, 112.75',
        ]);
    }
}
