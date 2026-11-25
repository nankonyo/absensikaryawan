<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Holiday;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileHolidayReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_returns_active_holidays_for_the_requested_month(): void
    {
        $user = User::factory()->create();
        $token = ApiToken::issue($user, 'test');

        Holiday::create(['date' => '2026-11-25', 'name' => 'Hari Guru', 'type' => 'national', 'is_active' => true]);
        Holiday::create(['date' => '2026-11-28', 'name' => 'Libur Nonaktif', 'type' => 'company', 'is_active' => false]);
        Holiday::create(['date' => '2026-12-25', 'name' => 'Natal', 'type' => 'national', 'is_active' => true]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/holidays?month=2026-11')
            ->assertOk()
            ->assertJsonPath('month', '2026-11')
            ->assertJsonCount(1, 'holidays')
            ->assertJsonPath('holidays.0.name', 'Hari Guru')
            ->assertJsonPath('holidays.0.date', '2026-11-25');
    }

    public function test_mobile_attendance_report_is_available_to_admin(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $token = ApiToken::issue($admin, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/reports/attendance?start_date=2026-11-02&end_date=2026-11-06')
            ->assertOk()
            ->assertJsonPath('start_date', '2026-11-02')
            ->assertJsonPath('end_date', '2026-11-06')
            ->assertJsonPath('normal_work_days', 5)
            ->assertJsonStructure([
                'rows',
                'totals' => ['absent', 'late_hours', 'leave', 'national_holiday'],
            ]);
    }

    public function test_admin_can_get_a_signed_report_download_url(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $token = ApiToken::issue($admin, 'test');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/reports/attendance/download-url?start_date=2026-11-02&end_date=2026-11-06&format=pdf')
            ->assertOk()
            ->assertJsonStructure(['url']);

        $this->get($response->json('url'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_manage_holidays_from_the_mobile_resource_api(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $token = ApiToken::issue($admin, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/admin/resources/holidays', [
                'date' => '2026-11-25',
                'name' => 'Hari Guru',
                'type' => 'national',
                'is_active' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('holidays', [
            'date' => '2026-11-25',
            'name' => 'Hari Guru',
            'type' => 'national',
            'is_active' => true,
        ]);
    }

    public function test_mobile_attendance_report_is_forbidden_for_staff(): void
    {
        $this->seed();
        $staff = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);
        $token = ApiToken::issue($staff, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/reports/attendance?start_date=2026-11-02&end_date=2026-11-06')
            ->assertForbidden();
    }
}
