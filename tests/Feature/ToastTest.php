<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToastTest extends TestCase
{
    use RefreshDatabase;

    public function test_flash_message_renders_simple_bottom_center_pill_toast(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($admin)
            ->withSession(['success' => 'Data tersimpan'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="toast-root"', false)
            ->assertSee('bottom-6', false)
            ->assertSee('rounded-full', false)
            ->assertSee('bg-gray-900/80', false)
            ->assertSee('Data tersimpan')
            ->assertDontSee('sweetalert2', false);
    }
}
