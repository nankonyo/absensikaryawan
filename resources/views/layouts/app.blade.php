<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ !empty($header) ? $header.' — '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @include('partials.assets')
    @livewireStyles
</head>
@php($lockScroll = request()->routeIs('karyawan.attendance.check-in', 'karyawan.attendance.check-out'))
<body class="font-sans antialiased bg-[#F8FAFC] {{ $lockScroll ? 'fixed inset-0 overflow-hidden overscroll-none' : '' }}">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
        <!-- Mobile overlay -->
        <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-black/50 lg:hidden" x-cloak></div>

        <!-- Sidebar -->
        <x-sidebar />

        <!-- Main Content -->
        <div class="min-w-0 flex-1 lg:ml-64">
            @unless(request()->routeIs('karyawan.attendance.check-in', 'karyawan.attendance.check-out'))
                <x-navbar :header="$header ?? ''" />
            @endunless

            <!-- Page Content -->
            <main class="p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />
    @livewireScripts
    <script>
        (function () {
            document.addEventListener('change', function (event) {
                if (event.target && event.target.name === 'per_page') {
                    localStorage.setItem('perPage', event.target.value);
                }
            }, true);

            document.addEventListener('DOMContentLoaded', function () {
                var params = new URLSearchParams(window.location.search);
                if (params.has('per_page')) {
                    return;
                }

                var stored = localStorage.getItem('perPage');
                if (!stored) {
                    return;
                }

                document.querySelectorAll('select[name="per_page"]').forEach(function (select) {
                    if (select.value === stored) {
                        return;
                    }

                    if (Array.prototype.some.call(select.options, function (option) {
                        return option.value === stored;
                    })) {
                        select.value = stored;

                        var url = new URL(window.location.href);
                        url.searchParams.set('per_page', stored);
                        window.location.replace(url.toString());
                    }
                });
            });
        })();
    </script>
    @include('partials.dev-reload')
</body>
</html>
