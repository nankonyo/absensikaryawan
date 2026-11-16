<?php

namespace Tests\Feature\Karyawan;

use App\Models\Attendance;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_table_headers_match_body_columns_and_links_to_detail_page(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Employee']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => now()->setTime(8, 5),
            'check_in_latitude' => -6.2,
            'check_in_longitude' => 106.8,
            'check_in_photo' => 'attendance/check-in.jpg',
            'status' => 'valid',
            'work_duration' => 480,
            'late_duration' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.history'));

        $response->assertOk();

        $html = $response->getContent();
        $this->assertSame(5, substr_count($html, '<th '));
        $this->assertSame(5, substr_count($html, '<td '));

        $response->assertSee('Semua Status');
        $response->assertSee(route('karyawan.attendance.show', $attendance), false);
        $response->assertDontSee('showModal', false);
    }

    public function test_employee_can_open_own_attendance_detail_page(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Employee']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'check_out' => '17:00:00',
            'check_in_photo' => 'attendance/check-in.jpg',
            'check_in_latitude' => -6.2,
            'check_in_longitude' => 106.8,
            'status' => 'valid',
        ]);

        $response = $this->actingAs($user)->get(route('karyawan.attendance.show', $attendance));

        $response->assertOk();
        $response->assertSee(__('ui.attendance_details'));
        $response->assertSee(__('ui.back_to_attendance_history'));
        $response->assertSee(route('karyawan.attendance.history'), false);
        $response->assertSee('leaflet', false);
    }

    public function test_employee_cannot_open_another_employees_attendance_detail(): void
    {
        $role = Role::create(['name' => 'staff', 'display_name' => 'Employee']);
        $supervisor = User::factory()->create();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
        ]);
        $other = User::factory()->create([
            'role_id' => $role->id,
            'supervisor_id' => $supervisor->id,
        ]);

        $attendance = Attendance::create([
            'user_id' => $other->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'valid',
        ]);

        $this->actingAs($user)
            ->get(route('karyawan.attendance.show', $attendance))
            ->assertForbidden();
    }
}
