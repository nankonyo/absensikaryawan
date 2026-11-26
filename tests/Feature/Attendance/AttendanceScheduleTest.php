<?php

namespace Tests\Feature\Attendance;

use App\Models\EmployeeSetting;
use App\Models\Setting;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_weekly_schedule_applies_only_to_wfo_employees(): void
    {
        Setting::set('weekly_work_modes', [4 => 'WFH'], 'json');

        $wfoUser = User::factory()->create();
        EmployeeSetting::create(['user_id' => $wfoUser->id, 'work_mode' => 'WFO']);
        $wfhUser = User::factory()->create();
        EmployeeSetting::create(['user_id' => $wfhUser->id, 'work_mode' => 'WFH']);

        $service = app(AttendanceService::class);
        $thursday = Carbon::parse('2026-11-26');
        $monday = Carbon::parse('2026-11-23');

        $this->assertSame('WFH', $service->workMode($wfoUser->fresh(), $thursday));
        $this->assertSame('WFO', $service->workMode($wfoUser->fresh(), $monday));
        $this->assertSame('WFH', $service->workMode($wfhUser->fresh(), $monday));
        $this->assertSame('WFH', $service->workMode($wfhUser->fresh(), $thursday));
    }
}
