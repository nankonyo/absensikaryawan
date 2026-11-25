<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class PasswordOtpController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email') ?: $request->query('email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password-otp', ['email' => $email]);
    }

    public function resend(Request $request, PasswordOtpService $service): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $otp = $service->issue($data['email']);

        if ($otp === null) {
            return back()->withErrors(['email' => __('ui.email_not_registered')]);
        }

        $request->session()->put('password_reset_email', $data['email']);

        return redirect()->route('password.otp')
            ->with('status', __('ui.otp_sent'));
    }

    public function store(Request $request, PasswordOtpService $service): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        if (! $service->reset($data['email'], $data['otp'], $data['password'])) {
            return back()->withInput($request->only('email'))
                ->withErrors(['otp' => __('ui.otp_invalid')]);
        }

        $request->session()->forget('password_reset_email');

        return redirect()->route('login')->with('status', __('ui.password_reset_success'));
    }
}
