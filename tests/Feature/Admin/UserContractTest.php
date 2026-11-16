<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserContractTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        $this->role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi', 'level' => 1]);
        $this->admin = User::factory()->create(['role_id' => $this->role->id]);
    }

    public function test_create_form_shows_employment_contract_fields(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('name="employment_status"', false)
            ->assertSee('name="contract_start_date"', false)
            ->assertSee('name="contract_end_date"', false)
            ->assertSee('name="contract_document"', false);
    }

    public function test_store_saves_contract_dates_and_document(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Contract User',
                'email' => 'contract@example.com',
                'password' => 'password123',
                'employee_id' => 'CONTRACT-001',
                'role_id' => $this->role->id,
                'employment_status' => 'contract',
                'contract_start_date' => '2026-01-01',
                'contract_end_date' => '2026-12-31',
                'contract_document' => UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'contract@example.com')->firstOrFail();

        $this->assertSame('contract', $user->employment_status);
        $this->assertSame('2026-01-01', $user->contract_start_date->format('Y-m-d'));
        $this->assertSame('2026-12-31', $user->contract_end_date->format('Y-m-d'));
        $this->assertDatabaseHas('user_contracts', [
            'user_id' => $user->id,
            'start_date' => '2026-01-01 00:00:00',
            'end_date' => '2026-12-31 00:00:00',
        ]);
        Storage::disk('public')->assertExists($user->contract_document);
        $this->assertSame('/storage/'.$user->contract_document, $user->contract_document_url);
    }

    public function test_permanent_employee_keeps_a_document_without_contract_dates(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Permanent User',
                'email' => 'permanent@example.com',
                'password' => 'password123',
                'employee_id' => 'PERMANENT-001',
                'role_id' => $this->role->id,
                'employment_status' => 'permanent',
                'contract_document' => UploadedFile::fake()->create('keterangan.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'permanent@example.com')->firstOrFail();

        $this->assertSame('permanent', $user->employment_status);
        $this->assertNull($user->contract_start_date);
        $this->assertNull($user->contract_end_date);
        $this->assertNotNull($user->contract_document);
        Storage::disk('public')->assertExists($user->contract_document);
    }

    public function test_edit_form_toggles_contract_dates_without_alpine(): void
    {
        $permanent = User::factory()->create([
            'role_id' => $this->role->id,
            'employee_id' => 'CONTRACT-004',
            'employment_status' => 'permanent',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $permanent))
            ->assertOk()
            ->assertSee('name="contract_start_date"', false)
            ->assertSee('name="contract_document"', false)
            ->assertSee('id="contract-dates" class="mt-4', false)
            ->assertSee('gap-4 hidden', false)
            ->assertSee(__('ui.permanent_document'));

        $contract = User::factory()->create([
            'role_id' => $this->role->id,
            'employee_id' => 'CONTRACT-005',
            'employment_status' => 'contract',
        ]);
        $contract->contract()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $contract))
            ->assertOk()
            ->assertSee('id="contract-dates" class="mt-4', false)
            ->assertDontSee('gap-4 hidden', false)
            ->assertSee(__('ui.contract_document'));
    }

    public function test_contract_dates_are_required_for_contract_employees(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Incomplete Contract',
                'email' => 'incomplete@example.com',
                'password' => 'password123',
                'employee_id' => 'CONTRACT-002',
                'role_id' => $this->role->id,
                'employment_status' => 'contract',
            ])
            ->assertSessionHasErrors(['contract_start_date', 'contract_end_date']);
    }

    public function test_switching_to_permanent_clears_contract_dates_and_keeps_document(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role_id' => $this->role->id,
            'employee_id' => 'CONTRACT-003',
            'employment_status' => 'contract',
        ]);
        $user->contract()->create([
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'document' => 'contracts/old.pdf',
        ]);

        Storage::disk('public')->put('contracts/old.pdf', 'old');

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'role_id' => $this->role->id,
                'employment_status' => 'permanent',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();

        $this->assertSame('permanent', $user->employment_status);
        $this->assertNull($user->contract_start_date);
        $this->assertNull($user->contract_end_date);
        $this->assertSame('contracts/old.pdf', $user->contract_document);
        Storage::disk('public')->assertExists('contracts/old.pdf');
    }
}
