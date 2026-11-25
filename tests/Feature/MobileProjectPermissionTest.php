<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileProjectPermissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithPermissions(array $actions): User
    {
        $role = Role::create([
            'name' => 'programmer',
            'slug' => 'programmer',
            'display_name' => 'Programmer',
            'level' => 5,
        ]);

        foreach ($actions as $action) {
            DB::table('permissions')->insert([
                'first_string_key' => $role->slug,
                'first_big_integer_key' => $role->id,
                'code' => 'internalapp.project.project',
                'action' => $action,
                'model' => 'feature',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function grant(User $user, string $code, string $action): void
    {
        DB::table('permissions')->insert([
            'first_string_key' => $user->role?->slug,
            'first_big_integer_key' => $user->role_id,
            'code' => $code,
            'action' => $action,
            'model' => 'feature',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_auth_payload_exposes_role_feature_permissions(): void
    {
        $user = $this->makeUserWithPermissions(['read', 'create']);
        $token = ApiToken::issue($user, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonFragment([
                'permissions' => [
                    'internalapp.project.project' => [
                        'read' => true,
                        'create' => true,
                    ],
                ],
            ]);
    }

    public function test_staff_with_create_permission_can_create_a_project(): void
    {
        $user = $this->makeUserWithPermissions(['read', 'create']);
        $token = ApiToken::issue($user, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/projects', [
                'name' => 'Project dari Mobile',
                'description' => 'Dibuat lewat API mobile',
                'status' => 'planning',
            ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('internal_projects', [
            'name' => 'Project dari Mobile',
            'owner_id' => $user->id,
        ]);
    }

    public function test_staff_without_create_permission_gets_forbidden(): void
    {
        $user = $this->makeUserWithPermissions(['read']);
        $token = ApiToken::issue($user, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/projects', ['name' => 'Tidak Boleh'])
            ->assertForbidden();

        $this->assertDatabaseMissing('internal_projects', ['name' => 'Tidak Boleh']);
    }

    public function test_mobile_can_update_project_team_and_task_assignments(): void
    {
        $user = $this->makeUserWithPermissions(['read', 'create', 'update']);
        $this->grant($user, 'internalapp.project.kanban', 'update');
        $token = ApiToken::issue($user, 'test');
        $member = User::factory()->create();

        $projectId = DB::table('internal_projects')->insertGetId([
            'name' => 'Project Tim',
            'owner_id' => $user->id,
            'status' => 'in_progress',
            'progress' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/projects/'.$projectId.'/members', ['members' => [$member->id]])
            ->assertOk();

        $this->assertDatabaseHas('internal_project_members', [
            'project_id' => $projectId,
            'user_id' => $member->id,
        ]);

        $taskId = DB::table('internal_tasks')->insertGetId([
            'project_id' => $projectId,
            'title' => 'Task Assignment',
            'status' => 'todo',
            'priority' => 'medium',
            'progress' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/mobile/projects/tasks/'.$taskId, ['assignee_ids' => [$member->id]])
            ->assertOk();

        $this->assertDatabaseHas('internal_task_assignees', [
            'task_id' => $taskId,
            'user_id' => $member->id,
        ]);
    }

    public function test_staff_without_read_permission_cannot_open_project_list(): void
    {
        $user = $this->makeUserWithPermissions([]);
        $token = ApiToken::issue($user, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/projects')
            ->assertForbidden();
    }
}
