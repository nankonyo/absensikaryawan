<?php

namespace Tests\Feature\Admin;

use App\Models\Holiday;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HolidayCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_admin_can_create_national_and_company_holidays(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.holidays.store'), [
                'date' => '2026-11-25',
                'name' => 'Hari Guru',
                'type' => 'national',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->post(route('admin.holidays.store'), [
                'date' => '2026-11-28',
                'name' => 'Libur Perusahaan',
                'type' => 'company',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('holidays', [
            'date' => '2026-11-25',
            'name' => 'Hari Guru',
            'type' => 'national',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('holidays', [
            'date' => '2026-11-28',
            'name' => 'Libur Perusahaan',
            'type' => 'company',
            'is_active' => true,
        ]);
    }

    public function test_calendar_shows_holiday_and_prefills_edit_form(): void
    {
        $holiday = Holiday::create([
            'date' => '2026-11-25',
            'name' => 'Hari Guru',
            'type' => 'national',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.holidays.index', ['month' => '2026-11', 'edit' => $holiday->id]))
            ->assertOk()
            ->assertSee('Hari Guru')
            ->assertSee(__('ui.holiday_types.national'))
            ->assertSee(route('admin.holidays.update', $holiday), false);
    }

    public function test_holiday_can_be_updated_and_deleted(): void
    {
        $holiday = Holiday::create([
            'date' => '2026-11-25',
            'name' => 'Hari Guru',
            'type' => 'national',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.holidays.update', $holiday), [
                'date' => '2026-11-25',
                'name' => 'Hari Guru Nasional',
                'type' => 'collective',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.holidays.index', ['month' => '2026-11']));

        $this->assertDatabaseHas('holidays', [
            'id' => $holiday->id,
            'name' => 'Hari Guru Nasional',
            'type' => 'collective',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.holidays.destroy', $holiday))
            ->assertRedirect(route('admin.holidays.index', ['month' => '2026-11']));

        $this->assertDatabaseMissing('holidays', ['id' => $holiday->id]);
    }

    public function test_holiday_date_must_be_unique(): void
    {
        Holiday::create([
            'date' => '2026-11-25',
            'name' => 'Hari Guru',
            'type' => 'national',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.holidays.store'), [
                'date' => '2026-11-25',
                'name' => 'Hari Libur Lain',
                'type' => 'company',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('date');
    }
}
