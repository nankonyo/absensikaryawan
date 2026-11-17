@props(['header' => ''])

@php
    $user = auth()->user();
@endphp

<header class="sticky top-0 z-20 border-b border-gray-200/80 bg-white/95 backdrop-blur">
    <div class="flex h-16 items-center justify-between gap-3 px-4 lg:px-6">
        {{-- LEFT: toggle + page title --}}
        <div class="flex min-w-0 items-center gap-2 sm:gap-3">
            <button @click="sidebarOpen = true"
                    class="-ml-1 rounded-xl p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
                    aria-label="{{ __('ui.open_menu') }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>

            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-[11px] text-gray-500">
                    <span>NIP {{ $user?->employee_id ?? '-' }}</span>
                    <span class="text-gray-300">•</span>
                    <span class="truncate">{{ $user?->department?->name ?? '-' }}</span>
                    <span class="text-gray-300">•</span>
                    <span class="truncate">{{ $user?->positionMaster?->name ?? $user?->position ?? '-' }}</span>
                </div>
                <p class="flex items-center gap-1.5 text-[10px] text-gray-400">
                    <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                </p>
            </div>
        </div>

        {{-- RIGHT: actions --}}
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            {{-- Notifications --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @keydown.escape.window="open = false"
                        class="relative rounded-xl p-2 text-gray-600 transition hover:bg-gray-100"
                        aria-label="Notifikasi">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="absolute right-1.5 top-1.5 flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-70"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                    </span>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-2 w-72 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <p class="text-sm font-bold text-gray-900">Notifikasi</p>
                        <p class="text-[11px] text-gray-400">Informasi terbaru aplikasi</p>
                    </div>
                    <div class="px-4 py-6 text-center text-sm text-gray-500">
                        Belum ada notifikasi baru.
                    </div>
                    <a href="{{ route('dashboard') }}"
                       class="block border-t border-gray-100 bg-gray-50/70 px-4 py-2.5 text-center text-xs font-semibold text-blue-600 hover:bg-gray-100">
                        Kembali ke Dashboard
                    </a>
                </div>
            </div>

            {{-- Language dropdown --}}
            <div x-data="{ langOpen: false }" class="relative">
                <button type="button" @click="langOpen = !langOpen" @keydown.escape.window="langOpen = false"
                        class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-2.5 py-2 text-xs font-semibold text-gray-600 transition hover:bg-gray-50"
                        aria-label="{{ __('ui.language') }}">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0zM3.6 9h16.8M3.6 15h16.8M12 3a15.3 15.3 0 010 18M12 3a15.3 15.3 0 000 18"></path>
                    </svg>
                    <span>{{ strtoupper(app()->getLocale()) }}</span>
                    <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="langOpen" x-cloak @click.outside="langOpen = false"
                     class="absolute right-0 mt-2 w-40 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-xl">
                    <a href="{{ route('locale.switch', 'id') }}" data-no-spa
                       @click="langOpen = false"
                       class="flex items-center justify-between px-3 py-2 text-xs font-semibold {{ app()->getLocale() === 'id' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>{{ __('ui.indonesian') }}</span>
                        @if(app()->getLocale() === 'id')
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @endif
                    </a>
                    <a href="{{ route('locale.switch', 'en') }}" data-no-spa
                       @click="langOpen = false"
                       class="flex items-center justify-between px-3 py-2 text-xs font-semibold {{ app()->getLocale() === 'en' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                        <span>{{ __('ui.english') }}</span>
                        @if(app()->getLocale() === 'en')
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @endif
                    </a>
                </div>
            </div>

            {{-- User menu --}}
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" @keydown.escape.window="open = false"
                        class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white py-1.5 pl-1.5 pr-2 transition hover:bg-gray-50">
                    @if($user?->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
                             class="h-8 w-8 rounded-lg object-cover">
                    @else
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-sm font-bold text-white">
                            {{ strtoupper(substr($user?->name ?? '?', 0, 1)) }}
                        </span>
                    @endif

                    <span class="hidden min-w-0 text-left leading-tight sm:block">
                        <span class="block max-w-[160px] truncate text-xs font-semibold text-gray-800">{{ $user?->name }}</span>
                        <span class="block max-w-[160px] truncate text-[10px] text-gray-400">{{ $user?->email }}</span>
                    </span>

                    <svg class="hidden h-4 w-4 text-gray-400 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div x-show="open" x-cloak @click.outside="open = false"
                     class="absolute right-0 mt-2 w-56 overflow-hidden rounded-2xl border border-gray-200 bg-white py-1 shadow-xl">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <p class="truncate text-sm font-bold text-gray-900">{{ $user?->name }}</p>
                    </div>

                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        {{ __('ui.profile') }}
                    </a>

                    @if($user?->isAdmin())
                        <a href="{{ route('admin.settings.index') }}"
                           class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            {{ __('ui.settings') }}
                        </a>
                    @endif

                    <div class="my-1 border-t border-gray-100"></div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm font-medium text-red-600 hover:bg-red-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            {{ __('ui.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
