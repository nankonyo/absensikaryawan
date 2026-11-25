<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\PasswordOtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $this->get('/forgot-password')->assertStatus(200);
    }

    public function test_otp_form_can_be_rendered_after_requesting_code(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('password.otp'))
            ->assertSessionHas('password_reset_email', $user->email);

        $this->withSession(['password_reset_email' => $user->email])
            ->get(route('password.otp'))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_password_can_be_reset_with_a_valid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $otp = app(PasswordOtpService::class)->issue($user->email);

        $this->withSession(['password_reset_email' => $user->email])
            ->post(route('password.otp.store'), [
                'email' => $user->email,
                'otp' => $otp,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_otps', ['email' => $user->email]);
    }

    public function test_password_reset_is_rejected_with_an_invalid_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        app(PasswordOtpService::class)->issue($user->email);

        $this->post(route('password.otp.store'), [
            'email' => $user->email,
            'otp' => '000000',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('otp');

        $this->assertFalse(Hash::check('new-password-123', $user->fresh()->password));
    }
}
