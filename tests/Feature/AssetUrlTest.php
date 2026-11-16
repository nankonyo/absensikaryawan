<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_assets_follow_the_request_host(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => 'c915-180-242-203-64.ngrok-free.app',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'c915-180-242-203-64.ngrok-free.app',
        ])->get('/login');

        $response->assertOk();

        $html = $response->getContent();

        $this->assertStringNotContainsString('127.0.0.1:5173', $html);
        $this->assertStringNotContainsString('http://localhost', $html);
        $this->assertStringContainsString('https://c915-180-242-203-64.ngrok-free.app/build/assets/', $html);
    }

    public function test_stored_file_urls_are_relative_to_the_request(): void
    {
        $this->assertSame('/storage/avatars/photo.jpg', $this->avatarUrl());

        $user = User::factory()->create(['avatar' => 'avatars/photo.jpg']);

        $this->assertSame('/storage/avatars/photo.jpg', $user->avatar_url);
    }

    private function avatarUrl(): string
    {
        return \Illuminate\Support\Facades\Storage::disk('public')->url('avatars/photo.jpg');
    }
}
