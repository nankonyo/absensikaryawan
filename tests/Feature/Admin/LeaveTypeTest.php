<?php

namespace Tests\Feature\Admin;

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_admin_can_create_leave_type_used_by_employee_request(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.leave-types.store'), [
                'name' => 'Work From Home',
                'category' => 'izin',
                'requires_reason' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $type = LeaveType::where('name', 'Work From Home')->firstOrFail();

        $employee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        $this->actingAs($employee)
            ->get(route('karyawan.leaves.create'))
            ->assertOk()
            ->assertSee('Work From Home');

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'leave_type_id' => $type->id,
                'reason' => 'Working from home',
            ])
            ->assertRedirect(route('karyawan.leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
        ]);
    }

    public function test_inactive_leave_type_is_rejected(): void
    {
        $employee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        $type = LeaveType::create(['name' => 'Old Type', 'is_active' => false]);

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'leave_type_id' => $type->id,
                'reason' => 'Should be rejected',
            ])
            ->assertSessionHasErrors('leave_type_id');
    }

    public function test_leave_type_in_use_cannot_be_deleted(): void
    {
        $type = LeaveType::where('name', 'Cuti Tahunan')->firstOrFail();
        $employee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        Leave::create([
            'user_id' => $employee->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'days' => 1,
            'leave_type_id' => $type->id,
            'reason' => 'Family event',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.leave-types.destroy', $type))
            ->assertRedirect();

        $this->assertDatabaseHas('leave_types', ['id' => $type->id]);
    }

    public function test_edit_query_fills_form_and_keeps_pagination(): void
    {
        $type = LeaveType::where('name', 'Cuti Tahunan')->firstOrFail();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.leave-types.index', [
                'per_page' => 25,
                'edit' => $type->id,
            ]));

        $response->assertOk();
        $response->assertSee('value="'.$type->name.'"', false);
        $response->assertSee(route('admin.leave-types.update', $type), false);
        $response->assertSee('per_page=25', false);
        $response->assertSee('edit='.$type->id, false);
    }

    public function test_leave_type_rules_are_saved(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.leave-types.store'), [
                'name' => 'Sakit Dengan Surat',
                'category' => 'sakit',
                'requires_attachment' => '1',
                'deducts_quota' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect();

        $type = LeaveType::where('name', 'Sakit Dengan Surat')->firstOrFail();

        $this->assertSame('sakit', $type->category);
        $this->assertTrue($type->requires_attachment);
        $this->assertFalse($type->requires_reason);
        $this->assertTrue($type->deducts_quota);
    }

    public function test_sick_leave_requires_attachment(): void
    {
        Storage::fake('public');

        $type = LeaveType::create([
            'name' => 'Sakit',
            'category' => 'sakit',
            'requires_attachment' => true,
            'requires_reason' => false,
            'deducts_quota' => false,
        ]);

        $employee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'leave_type_id' => $type->id,
            ])
            ->assertSessionHasErrors('attachment');

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'leave_type_id' => $type->id,
                'attachment' => UploadedFile::fake()->create('surat-dokter.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('karyawan.leaves.index'));

        $this->assertNotNull(Leave::where('user_id', $employee->id)->value('attachment'));
    }

    public function test_leave_type_requires_reason_when_configured(): void
    {
        $type = LeaveType::create([
            'name' => 'Izin',
            'category' => 'izin',
            'requires_attachment' => false,
            'requires_reason' => true,
            'deducts_quota' => false,
        ]);

        $employee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-11-10',
                'end_date' => '2026-11-10',
                'leave_type_id' => $type->id,
            ])
            ->assertSessionHasErrors('reason');
    }

    public function test_quota_is_enforced_for_deducting_leave_type(): void
    {
        Setting::set('annual_leave_quota', 12, 'integer');

        $type = LeaveType::create([
            'name' => 'Cuti Tahunan',
            'category' => 'cuti',
            'requires_attachment' => false,
            'requires_reason' => false,
            'deducts_quota' => true,
        ]);

        $employee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-10',
                'leave_type_id' => $type->id,
            ])
            ->assertRedirect(route('karyawan.leaves.index'));

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-12-01',
                'end_date' => '2026-12-05',
                'leave_type_id' => $type->id,
            ])
            ->assertSessionHasErrors('leave_type_id');

        $this->assertSame(1, Leave::where('user_id', $employee->id)->count());
    }

    public function test_leave_type_without_quota_is_not_limited(): void
    {
        $type = LeaveType::create([
            'name' => 'Cuti Melahirkan',
            'category' => 'cuti',
            'requires_attachment' => false,
            'requires_reason' => false,
            'deducts_quota' => false,
        ]);

        $employee = User::factory()->create([
            'role_id' => Role::where('name', 'staff')->value('id'),
        ]);

        $this->actingAs($employee)
            ->post(route('karyawan.leaves.store'), [
                'start_date' => '2026-11-01',
                'end_date' => '2026-11-30',
                'leave_type_id' => $type->id,
            ])
            ->assertRedirect(route('karyawan.leaves.index'));

        $this->assertDatabaseHas('leaves', [
            'user_id' => $employee->id,
            'leave_type_id' => $type->id,
            'days' => 30,
        ]);
    }

    public function test_used_by_column_is_not_rendered(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.leave-types.index'))
            ->assertOk()
            ->assertDontSee(__('ui.used_by'));
    }
}
