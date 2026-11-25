<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('ui.otp_code_help') }} {{ __('ui.otp_expires', ['minutes' => 10]) }}
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.otp.store') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full bg-gray-50" type="email" name="email" :value="old('email', $email)" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="otp" :value="__('ui.otp_code')" />
            <x-text-input id="otp" class="block mt-1 w-full tracking-[0.5em] text-center text-lg font-bold"
                          type="text" name="otp" inputmode="numeric" maxlength="6" required autofocus
                          placeholder="000000" autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('ui.new_password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('ui.confirm_new_password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-4 gap-3">
            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                {{ __('ui.resend_otp') }}
            </a>
            <x-primary-button>
                {{ __('ui.reset_password_action') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
