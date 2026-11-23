<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class MobileFaceCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_face_capture_page_renders_and_unsigned_is_rejected(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/face.jpg']);

        $url = URL::temporarySignedRoute('mobile.face-capture', now()->addMinutes(10), [
            'user' => $user->id,
            'mode' => 'in',
        ]);

        $this->get($url)
            ->assertOk()
            ->assertSee('FACE_CAPTURE', false)
            ->assertSee('face_score', false);

        $this->get(route('mobile.face-capture', ['user' => $user->id, 'mode' => 'in']))
            ->assertForbidden();
    }

    public function test_api_issues_signed_face_capture_url_for_user_with_avatar(): void
    {
        $user = User::factory()->create(['avatar' => 'avatars/face.jpg']);
        $token = ApiToken::issue($user, 'test');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/attendance/face-capture-url?mode=out');

        $response->assertOk()
            ->assertJsonPath('threshold', 80)
            ->assertJsonStructure(['url']);

        $this->assertStringContainsString('/mobile/face-capture/'.$user->id, $response->json('url'));
        $this->assertStringContainsString('mode=out', $response->json('url'));
    }

    public function test_api_returns_no_url_when_avatar_is_missing(): void
    {
        $user = User::factory()->create(['avatar' => null]);
        $token = ApiToken::issue($user, 'test');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/attendance/face-capture-url')
            ->assertOk()
            ->assertJsonPath('url', null)
            ->assertJsonPath('reason', 'no_avatar');
    }
}
