<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RemovedFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_dark_mode_is_removed(): void
    {
        $this->seed();

        $response = $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('darkMode')
            ->assertDontSee('Dark Mode Toggle');

        $this->assertStringNotContainsString('dark:', $response->getContent());
    }

    public function test_overtime_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('admin.overtimes.index'));
        $this->assertFalse(Route::has('atasan.overtimes.index'));
        $this->assertFalse(Route::has('karyawan.overtimes.index'));
    }

    public function test_qr_code_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('admin.qr-code.index'));
        $this->assertFalse(Route::has('admin.qr-code.generate'));
    }

    public function test_payroll_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('admin.payrolls.index'));
        $this->assertFalse(Route::has('admin.payrolls.generate'));
        $this->assertFalse(Route::has('karyawan.payrolls.index'));
    }

    public function test_atasan_leave_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('atasan.leaves.index'));
        $this->assertFalse(Route::has('atasan.leaves.show'));
        $this->assertFalse(Route::has('atasan.leaves.approve'));
        $this->assertFalse(Route::has('atasan.leaves.reject'));
    }

    public function test_leave_report_is_removed(): void
    {
        $this->seed();

        $this->assertFalse(Route::has('admin.reports.leave'));

        $this->actingAs(User::where('email', 'admin@example.com')->firstOrFail())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(__('ui.attendance_report'))
            ->assertDontSee('Laporan Cuti');
    }
}
