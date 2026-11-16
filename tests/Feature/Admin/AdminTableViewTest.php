<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTableViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_navbar_does_not_render_the_page_name(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee(__('ui.user_management'));
    }

    public function test_navbar_user_menu_stays_hidden_before_alpine_loads(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('x-show="open" x-cloak', false);
    }

    public function test_attendance_records_use_search_instead_of_employee_select(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.attendances.index'))
            ->assertOk()
            ->assertSee('name="q"', false)
            ->assertDontSee('name="user_id"', false);
    }

    public function test_attendance_list_defaults_to_the_current_month(): void
    {
        $currentMonth = now()->format('Y-m');

        Attendance::create([
            'user_id' => $this->admin->id,
            'date' => now()->startOfMonth()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'valid',
        ]);
        $previous = Attendance::create([
            'user_id' => $this->admin->id,
            'date' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'check_in' => '09:00:00',
            'status' => 'valid',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.attendances.index'))
            ->assertOk()
            ->assertSee('name="month"', false)
            ->assertSee('value="'.$currentMonth.'"', false)
            ->assertSee('08:00')
            ->assertDontSee('09:00');

        $this->actingAs($this->admin)
            ->get(route('admin.attendances.index', ['month' => $previous->date->format('Y-m')]))
            ->assertOk()
            ->assertSee('09:00')
            ->assertDontSee('08:00');
    }

    public function test_attendance_employee_cell_shows_department_without_unit(): void
    {
        $department = Department::create(['name' => 'Engineering']);
        $unit = Unit::create(['name' => 'Software Development', 'department_id' => $department->id]);

        $this->admin->update([
            'employee_id' => 'EMP-ATT-01',
            'department_id' => $department->id,
            'unit_id' => $unit->id,
        ]);

        Attendance::create([
            'user_id' => $this->admin->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'status' => 'valid',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.attendances.index'))
            ->assertOk()
            ->assertSee($this->admin->name)
            ->assertSee('EMP-ATT-01')
            ->assertSee('Engineering')
            ->assertDontSee('Software Development');
    }

    public function test_table_pages_expose_the_per_page_control(): void
    {
        $routes = [
            'admin.attendances.index',
            'admin.leaves.index',
            'admin.audit-logs.index',
            'admin.departments.index',
            'admin.units.index',
            'admin.positions.index',
            'admin.leave-types.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee('name="per_page"', false);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.users.index'))
            ->assertSee("localStorage.setItem('perPage'", false);
    }

    public function test_table_pages_use_the_action_dropdown(): void
    {
        Leave::create([
            'user_id' => $this->admin->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'days' => 1,
            'leave_type_id' => LeaveType::where('name', 'Cuti Tahunan')->value('id'),
            'reason' => 'Family event',
            'status' => 'pending',
        ]);

        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'user_updated',
            'model' => 'User',
            'ip_address' => '127.0.0.1',
        ]);

        $routes = [
            'admin.leaves.index',
            'admin.audit-logs.index',
            'admin.departments.index',
            'admin.units.index',
            'admin.positions.index',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin)
                ->get(route($route))
                ->assertOk()
                ->assertSee('aria-label="'.__('ui.actions').'"', false);
        }
    }

    public function test_audit_log_detail_page_shows_the_log(): void
    {
        $log = AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'user_updated',
            'model' => 'User',
            'model_id' => $this->admin->id,
            'old_values' => ['name' => 'Old Name'],
            'new_values' => ['name' => 'New Name'],
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.show', $log))
            ->assertOk()
            ->assertSee(__('ui.audit_log_details'))
            ->assertSee(__('ui.audit_actions.user_updated'))
            ->assertSee('Old Name')
            ->assertSee('New Name');
    }

    public function test_audit_log_table_paginates_with_the_selected_per_page(): void
    {
        foreach (range(1, 6) as $index) {
            AuditLog::create([
                'user_id' => $this->admin->id,
                'action' => 'user_updated',
                'model' => 'User',
                'ip_address' => '127.0.0.1',
            ]);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.audit-logs.index', ['per_page' => 5]))
            ->assertOk()
            ->assertSee('per_page=5', false)
            ->assertSee('page=2', false);
    }
}
