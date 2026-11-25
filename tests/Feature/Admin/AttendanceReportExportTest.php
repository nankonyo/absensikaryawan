<?php

namespace Tests\Feature\Admin;

use App\Exports\AttendanceReportExport;
use App\Models\Attendance;
use App\Models\AttendanceReason;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AttendanceReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->employee = User::factory()->create([
            'name' => 'Karyawan Uji',
            'employee_id' => 'TEST-001',
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);
    }

    public function test_summary_matches_reference_columns_and_holiday_rules(): void
    {
        Holiday::create([
            'date' => '2026-11-02',
            'name' => 'Libur Nasional',
            'type' => 'national',
            'is_active' => true,
        ]);

        Holiday::create([
            'date' => '2026-11-05',
            'name' => 'Libur Nonaktif',
            'type' => 'company',
            'is_active' => false,
        ]);

        $reason = AttendanceReason::where('name', 'WFH')->firstOrFail();

        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-11-03',
            'check_in' => '08:45:00',
            'status' => 'valid',
            'attendance_reason_id' => $reason->id,
            'late_duration' => 45,
        ]);

        $leaveType = LeaveType::where('name', 'Cuti Tahunan')->firstOrFail();

        Leave::create([
            'user_id' => $this->employee->id,
            'start_date' => '2026-11-04',
            'end_date' => '2026-11-04',
            'days' => 1,
            'leave_type_id' => $leaveType->id,
            'reason' => 'Keperluan keluarga',
            'status' => 'approved',
        ]);

        $report = app(AttendanceReportService::class)->build(
            Carbon::parse('2026-11-02'),
            Carbon::parse('2026-11-06')
        );

        $row = collect($report['rows'])->firstWhere('employee_id', 'TEST-001');

        $this->assertNotNull($row);
        $this->assertSame(5, $report['normal_work_days']);
        $this->assertSame(1, $row['national_holiday']);
        $this->assertSame(0, $row['company_holiday']);
        $this->assertSame(1, $row['wfh']);
        $this->assertSame(1, $row['late_hours']);
        $this->assertSame(1, $row['leave']);
        $this->assertSame(2, $row['absent']);
    }

    public function test_every_late_day_counts_at_least_one_hour_and_rounds_up(): void
    {
        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-11-03',
            'check_in' => '08:20:00',
            'status' => 'valid',
            'late_duration' => 20,
        ]);

        Attendance::create([
            'user_id' => $this->employee->id,
            'date' => '2026-11-04',
            'check_in' => '09:15:00',
            'status' => 'valid',
            'late_duration' => 75,
        ]);

        $report = app(AttendanceReportService::class)->build(
            Carbon::parse('2026-11-02'),
            Carbon::parse('2026-11-06')
        );

        $row = collect($report['rows'])->firstWhere('employee_id', 'TEST-001');

        $this->assertNotNull($row);
        // 20 menit -> 1 jam, 75 menit -> 2 jam.
        $this->assertSame(3, $row['late_hours']);
    }

    public function test_attendance_report_downloads_excel_file(): void
    {
        Excel::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.reports.attendance'), [
                'start_date' => '2026-11-02',
                'end_date' => '2026-11-06',
                'format' => 'excel',
            ])
            ->assertOk();

        Excel::assertDownloaded(
            'laporan-absensi-2026-11-02-sd-2026-11-06.xlsx',
            fn ($export) => $export instanceof AttendanceReportExport
        );
    }

    public function test_attendance_report_preview_returns_inline_pdf(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.attendance.preview', [
                'start_date' => '2026-11-02',
                'end_date' => '2026-11-06',
            ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_attendance_report_preview_requires_a_valid_date_range(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.attendance.preview', [
                'start_date' => '2026-11-06',
                'end_date' => '2026-11-02',
            ]))
            ->assertSessionHasErrors('end_date');
    }
}
