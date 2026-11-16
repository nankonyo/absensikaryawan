<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_restores_its_scroll_position_between_page_loads(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('sidebar.scrollTop', false)
            ->assertSee('sessionStorage', false)
            ->assertSee('pagehide', false);
    }

    public function test_settings_menu_sits_at_the_bottom_of_the_admin_sidebar(): void
    {
        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                route('admin.leave-types.index'),
                route('admin.settings.index'),
            ], false);
    }
}
