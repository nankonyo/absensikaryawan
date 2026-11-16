<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_avatar_used_as_face_reference(): void
    {
        Storage::fake('public');

        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create(['role_id' => $role->id]);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Face User',
            'email' => 'face@example.com',
            'password' => 'password123',
            'employee_id' => 'EMP999',
            'position' => 'Staff',
            'role_id' => $role->id,
            'avatar' => UploadedFile::fake()->image('face.jpg'),
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'face@example.com')->firstOrFail();

        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
        $this->assertSame('/storage/' . $user->avatar, $user->avatar_url);
    }

    public function test_navbar_shows_uploaded_avatar(): void
    {
        Storage::fake('public');

        $role = Role::create(['name' => 'direksi', 'display_name' => 'Direksi']);
        $admin = User::factory()->create([
            'role_id' => $role->id,
            'avatar' => 'avatars/admin.jpg',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('/storage/avatars/admin.jpg', false);
    }
}
