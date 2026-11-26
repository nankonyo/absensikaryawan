<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Attendance;
use App\Models\AttendanceReason;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAttendanceEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_attendance_work_mode_from_mobile(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $employee = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $employee->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'pending',
        ]);

        $token = ApiToken::issue($admin, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/admin/resources/attendances/'.$attendance->id.'/reason', [
                'work_mode' => 'WFH',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $reason = AttendanceReason::where('name', 'WFH')->firstOrFail();
        $attendance->refresh();

        $this->assertSame($reason->id, $attendance->attendance_reason_id);
        $this->assertSame('valid', $attendance->status);
    }
}
