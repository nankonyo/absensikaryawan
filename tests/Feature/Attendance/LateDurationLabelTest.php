<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Tests\TestCase;

class LateDurationLabelTest extends TestCase
{
    public function test_short_tardiness_stays_in_minutes(): void
    {
        $attendance = new Attendance(['late_duration' => 45]);

        $this->assertSame('Terlambat 45 menit', $attendance->lateDurationLabel());
    }

    public function test_one_hour_boundary_stays_in_minutes(): void
    {
        $attendance = new Attendance(['late_duration' => 60]);

        $this->assertSame('Terlambat 60 menit', $attendance->lateDurationLabel());
    }

    public function test_long_tardiness_uses_hours_and_remaining_minutes(): void
    {
        $attendance = new Attendance(['late_duration' => 152]);

        $this->assertSame('Terlambat 2 jam 32 menit', $attendance->lateDurationLabel());
    }
}
