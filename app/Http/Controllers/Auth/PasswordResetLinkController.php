<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $otp = app(PasswordOtpService::class)->issue($request->string('email')->toString());

        if ($otp === null) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => __('ui.email_not_registered')]);
        }

        $request->session()->put('password_reset_email', $request->string('email')->toString());

        return redirect()->route('password.otp')
            ->with('status', __('ui.otp_sent'));
    }
}
