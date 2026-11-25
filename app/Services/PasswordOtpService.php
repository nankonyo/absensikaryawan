<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordOtpService
{
    public const EXPIRES_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function issue(string $email): ?string
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            return null;
        }

        $otp = (string) random_int(100000, 999999);

        DB::table('password_reset_otps')->where('email', $email)->delete();

        DB::table('password_reset_otps')->insert([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appName = (string) Setting::get('app_name', config('app.name'));

        Mail::raw(
            "Halo {$user->name},\n\n"
            ."Kode OTP reset password {$appName}: {$otp}\n\n"
            .'Kode berlaku '.self::EXPIRES_MINUTES.' menit. Jangan berikan kode ini kepada siapa pun.'
            ."\n\nJika Anda tidak meminta reset password, abaikan email ini.",
            function ($message) use ($user, $appName) {
                $message->to($user->email)->subject('Kode OTP Reset Password - '.$appName);
            }
        );

        return $otp;
    }

    public function verify(string $email, string $otp): bool
    {
        $row = DB::table('password_reset_otps')
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->orderByDesc('id')
            ->first();

        if (! $row || (int) $row->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (Carbon::parse($row->expires_at)->isPast()) {
            return false;
        }

        DB::table('password_reset_otps')->where('id', $row->id)->increment('attempts');

        if (! Hash::check($otp, $row->otp_hash)) {
            return false;
        }

        DB::table('password_reset_otps')->where('id', $row->id)->update([
            'consumed_at' => now(),
            'updated_at' => now(),
        ]);

        return true;
    }

    public function reset(string $email, string $otp, string $password): bool
    {
        if (! $this->verify($email, $otp)) {
            return false;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        $user->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        DB::table('password_reset_otps')->where('email', $email)->delete();

        return true;
    }
}
