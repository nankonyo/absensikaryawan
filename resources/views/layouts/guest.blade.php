@props(['full' => false])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @include('partials.assets')
    </head>
    <body class="font-sans text-gray-900 antialiased">
        @if($full)
            {{ $slot }}
        @else
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="w-full sm:max-w-md flex justify-end px-6">
                <label for="guest-locale-switcher" class="sr-only">{{ __('ui.language') }}</label>
                <select id="guest-locale-switcher"
                        onchange="window.location.href = this.value"
                        class="text-sm rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                    <option value="{{ route('locale.switch', 'id') }}" @selected(app()->getLocale() === 'id')>{{ __('ui.indonesian') }}</option>
                    <option value="{{ route('locale.switch', 'en') }}" @selected(app()->getLocale() === 'en')>{{ __('ui.english') }}</option>
                </select>
            </div>

            <div>
                <a href="/">
                    @php($companyLogo = \App\Models\Setting::get('company_logo'))
                    @if($companyLogo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($companyLogo) }}"
                             alt="{{ config('app.name') }}"
                             class="h-20 w-auto max-w-full object-contain">
                    @else
                        <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                    @endif
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
        @endif
        @include('partials.dev-reload')
</body>
</html>
