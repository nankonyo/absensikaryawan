<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use Tests\TestCase;

class LateDurationLabelTest extends TestCase
{
    public function test_tardiness_below_thirty_minutes_rounds_down(): void
    {
        $attendance = new Attendance(['late_duration' => 29]);

        $this->assertSame(0, $attendance->lateDurationHours());
        $this->assertSame('Terlambat 0 jam', $attendance->lateDurationLabel());
    }

    public function test_tardiness_thirty_minutes_or_more_rounds_up(): void
    {
        $attendance = new Attendance(['late_duration' => 45]);

        $this->assertSame(1, $attendance->lateDurationHours());
        $this->assertSame('Terlambat 1 jam', $attendance->lateDurationLabel());
    }

    public function test_tardiness_is_rounded_per_started_threshold(): void
    {
        $overOneHour = new Attendance(['late_duration' => 89]);
        $oneAndHalfHours = new Attendance(['late_duration' => 90]);
        $longTardiness = new Attendance(['late_duration' => 152]);

        $this->assertSame(1, $overOneHour->lateDurationHours());
        $this->assertSame(2, $oneAndHalfHours->lateDurationHours());
        $this->assertSame(3, $longTardiness->lateDurationHours());
        $this->assertSame('Terlambat 3 jam', $longTardiness->lateDurationLabel());
    }
}
