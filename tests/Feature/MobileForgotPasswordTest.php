<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PasswordOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MobileForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_can_request_an_otp_code(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'staff@example.com']);

        $this->postJson('/api/mobile/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertDatabaseHas('password_reset_otps', ['email' => $user->email]);
    }

    public function test_mobile_can_reset_password_with_a_valid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'staff@example.com']);
        $otp = app(PasswordOtpService::class)->issue($user->email);

        $this->postJson('/api/mobile/reset-password-otp', [
            'email' => $user->email,
            'otp' => $otp,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertOk()
            ->assertJsonPath('status', true);

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_mobile_forgot_password_requires_a_valid_email(): void
    {
        $this->postJson('/api/mobile/forgot-password', ['email' => 'bukan-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
