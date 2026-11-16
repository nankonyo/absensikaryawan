<?php

namespace Tests\Feature\Admin;

use App\Models\OfficeLocation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);

        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $this->admin = User::factory()->create(['role_id' => $role->id]);
    }

    public function test_settings_page_shows_timezone_setting(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('name="timezone"', false);
        $response->assertSee('value="Asia/Jakarta"', false);
    }

    public function test_update_saves_timezone(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->payload([
            'timezone' => 'Asia/Makassar',
        ]));

        $response->assertRedirect(route('admin.settings.index'));

        $this->assertSame('Asia/Makassar', Setting::get('timezone'));
    }

    public function test_update_saves_annual_leave_quota(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->payload([
            'annual_leave_quota' => 14,
        ]));

        $response->assertRedirect(route('admin.settings.index'));

        $this->assertSame(14, Setting::get('annual_leave_quota'));
    }

    public function test_settings_page_shows_office_coordinates(): void
    {
        OfficeLocation::create([
            'name' => 'Head Office',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));

        $response->assertOk();
        $response->assertSee('name="latitude"', false);
        $response->assertSee('name="longitude"', false);
        $response->assertSee('value="-6.2"', false);
    }

    public function test_update_saves_coordinates_to_active_office_location(): void
    {
        OfficeLocation::create([
            'name' => 'Head Office',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius' => 100,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->payload([
            'latitude' => -7.2575,
            'longitude' => 112.7521,
            'radius' => 250,
        ]));

        $response->assertRedirect(route('admin.settings.index'));

        $office = OfficeLocation::where('is_active', true)->firstOrFail();
        $this->assertEquals(-7.2575, (float) $office->latitude);
        $this->assertEquals(112.7521, (float) $office->longitude);
        $this->assertEquals(250, $office->radius);

        $this->assertNull(Setting::where('key', 'latitude')->first());
        $this->assertNull(Setting::where('key', 'longitude')->first());
        $this->assertNull(Setting::where('key', 'radius')->first());
    }

    public function test_removed_payroll_settings_are_not_shown(): void
    {
        $this->assertNull(Setting::where('key', 'overtime_rate_per_hour')->first());
        $this->assertNull(Setting::where('key', 'late_penalty_per_minute')->first());
        $this->assertNull(Setting::where('key', 'leave_deduction_per_day')->first());

        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertDontSee('overtime_rate_per_hour', false)
            ->assertDontSee('late_penalty_per_minute', false)
            ->assertDontSee('leave_deduction_per_day', false);
    }

    public function test_uploaded_logo_is_saved_and_shown_in_sidebar(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post(route('admin.settings.update'), $this->payload([
            'company_logo' => UploadedFile::fake()->image('logo.png'),
        ]));

        $response->assertRedirect(route('admin.settings.index'));

        $logo = Setting::get('company_logo');
        $this->assertNotNull($logo);
        Storage::disk('public')->assertExists($logo);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(Storage::url($logo), false);
    }

    public function test_sidebar_shows_app_name_when_logo_is_not_uploaded(): void
    {
        $this->assertNull(Setting::get('company_logo'));

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(config('app.name'));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'work_start_time' => '08:00',
            'work_end_time' => '17:00',
            'late_tolerance' => 15,
            'annual_leave_quota' => 12,
            'timezone' => 'Asia/Jakarta',
            'face_recognition_enabled' => 0,
            'face_recognition_threshold' => 80,
            'company_name' => 'PT Contoh',
            'company_address' => 'Jl. Contoh No. 1',
            'latitude' => -6.2,
            'longitude' => 106.816666,
            'radius' => 100,
        ], $overrides);
    }
}
