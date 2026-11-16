<?php

namespace Tests\Feature\Attendance;

use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Services\GpsValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class FaceVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_rejected_when_face_score_below_threshold(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['avatar' => 'avatars/face.jpg']);
        $service = $this->makeService(threshold: 80);

        $result = $service->checkIn($user, $this->payload(['face_score' => 40]));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('tidak cocok', $result['message']);
        $this->assertDatabaseMissing('attendances', ['user_id' => $user->id]);
    }

    public function test_check_in_rejected_when_user_has_no_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['avatar' => null]);
        $service = $this->makeService(threshold: 80);

        $result = $service->checkIn($user, $this->payload(['face_score' => 99]));

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Avatar', $result['message']);
    }

    public function test_check_in_accepted_when_face_score_meets_threshold(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['avatar' => 'avatars/face.jpg']);
        $service = $this->makeService(threshold: 80);

        $result = $service->checkIn($user, $this->payload(['face_score' => 92]));

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in_face_score' => 92,
        ]);
    }

    public function test_check_in_page_renders_face_matching_assets(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Staf']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
            'avatar' => 'avatars/face.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.check-in'));

        $response->assertOk();
        $response->assertSee('name="face_score"', false);
        $response->assertSee('face-match', false);
        $response->assertSee('storage\/avatars\/face.jpg', false);
    }

    public function test_check_in_page_uses_single_capture_button_without_location_button(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Staf']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
            'avatar' => 'avatars/face.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.check-in'));

        $response->assertOk();
        $response->assertDontSee(__('ui.get_my_location'));
        $response->assertDontSee('id="locale-switcher"', false);
        $response->assertSee(__('ui.face_position_guide'));
        $response->assertSee(__('ui.check_in_badge'));
        $response->assertSee('camera-frame', false);
        $response->assertSee('rounded-[50%]', false);
        $response->assertSee('border-[#16A34A]', false);
        $response->assertSee('@click="capture()"', false);
    }

    public function test_check_out_page_uses_red_guide_and_out_badge_without_navbar(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Staf']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
            'avatar' => 'avatars/face.jpg',
        ]);

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'valid',
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.check-out'));

        $response->assertOk();
        $response->assertDontSee('id="locale-switcher"', false);
        $response->assertDontSee(__('ui.get_my_location'));
        $response->assertSee(__('ui.check_out_badge'));
        $response->assertSee('border-[#DC2626]', false);
        $response->assertSee('camera-frame', false);
    }

    public function test_check_out_is_allowed_without_check_in(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['avatar' => 'avatars/face.jpg']);
        $service = $this->makeService(threshold: 80);

        $result = $service->checkOut($user, $this->payload(['face_score' => 92]));

        $this->assertTrue($result['success']);
        $this->assertNull($result['attendance']->check_in);
        $this->assertNotNull($result['attendance']->check_out);
        $this->assertNull($result['attendance']->work_duration);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'check_in' => null,
        ]);
    }

    public function test_attendance_page_offers_check_out_without_check_in(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Staf']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.index'));

        $response->assertOk();
        $response->assertSee(route('karyawan.attendance.check-out'), false);
        $response->assertSee('bg-red-600 hover:bg-red-700', false);
        $response->assertSee(__('ui.view_history'));
    }

    public function test_attendance_page_disables_only_the_completed_action(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Staf']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
        ]);

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'valid',
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.index'));

        $response->assertOk();
        $response->assertDontSee('href="'.route('karyawan.attendance.check-in').'"', false);
        $response->assertSee('href="'.route('karyawan.attendance.check-out').'"', false);
        $response->assertSee('aria-disabled="true"', false);
        $response->assertSee(__('ui.check_in'));
    }

    public function test_attendance_page_keeps_check_in_button_disabled_after_check_out(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Staf']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
        ]);

        \App\Models\Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'status' => 'valid',
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.index'));

        $response->assertOk();
        $response->assertDontSee('href="'.route('karyawan.attendance.check-in').'"', false);
        $response->assertDontSee('href="'.route('karyawan.attendance.check-out').'"', false);
        $response->assertSee('aria-disabled="true"', false);
    }

    public function test_check_out_capture_button_is_red(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Staf']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
            'avatar' => 'avatars/face.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.check-out'));

        $response->assertOk();
        $response->assertSee('bg-red-600 hover:bg-red-700', false);
        $response->assertDontSee('bg-green-600 hover:bg-green-700', false);
    }

    private function makeService(int $threshold): AttendanceService
    {
        $gps = Mockery::mock(GpsValidationService::class);
        $gps->shouldReceive('validateLocation')->andReturn([
            'valid' => true,
            'message' => 'Location valid',
            'distance' => 0,
        ]);

        $face = Mockery::mock(FaceRecognitionService::class);
        $face->shouldReceive('isEnabled')->andReturnTrue();
        $face->shouldReceive('getThreshold')->andReturn($threshold);

        return new AttendanceService($gps, $face);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'photo_data' => 'data:image/jpeg;base64,' . base64_encode('fake-image'),
            'face_score' => 90,
        ], $overrides);
    }
}
