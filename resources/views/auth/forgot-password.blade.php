<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('ui.forgot_password_help') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('ui.send_otp') }}
            </x-primary-button>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                {{ __('ui.back_to_login') }}
            </a>
        </div>
    </form>
</x-guest-layout>
