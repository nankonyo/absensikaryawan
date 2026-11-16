<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_store_saves_birth_place_birth_date_and_gender(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Personal Data',
                'email' => 'personal@example.com',
                'password' => 'password123',
                'employee_id' => 'PERSONAL-001',
                'birth_place' => 'Bekasi',
                'birth_date' => '1995-08-17',
                'gender' => 'female',
                'role_id' => Role::where('name', 'direksi')->value('id'),
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'personal@example.com')->firstOrFail();

        $this->assertSame('Bekasi', $user->birth_place);
        $this->assertSame('1995-08-17', $user->birth_date->format('Y-m-d'));
        $this->assertSame('female', $user->gender);
    }

    public function test_gender_must_be_a_known_value(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Bad Gender',
                'email' => 'bad-gender@example.com',
                'password' => 'password123',
                'employee_id' => 'PERSONAL-002',
                'gender' => 'unknown',
                'role_id' => Role::where('name', 'direksi')->value('id'),
            ])
            ->assertSessionHasErrors('gender');
    }

    public function test_legacy_status_attribute_is_not_seeded_or_shown(): void
    {
        $this->assertDatabaseMissing('user_attributes', ['name' => 'status']);

        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertDontSee('>Status</label>', false)
            ->assertDontSee('<option value="Kontrak"', false);
    }

    public function test_create_form_shows_personal_fields(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('name="birth_place"', false)
            ->assertSee('name="birth_date"', false)
            ->assertSee('name="gender"', false);
    }
}
