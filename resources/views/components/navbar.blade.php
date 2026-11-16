@props(['header' => ''])

<header class="bg-white border-b border-gray-200 sticky top-0 z-10">
    <div class="px-4 lg:px-6 py-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 rounded-lg text-gray-600 hover:bg-gray-100" aria-label="{{ __('ui.open_menu') }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <div class="flex items-center gap-4">
            <!-- Language Switcher -->
            <label for="locale-switcher" class="sr-only">{{ __('ui.language') }}</label>
            <select id="locale-switcher"
                    onchange="window.location.href = this.value"
                    class="text-sm rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                <option value="{{ route('locale.switch', 'id') }}" @selected(app()->getLocale() === 'id')>{{ __('ui.indonesian') }}</option>
                <option value="{{ route('locale.switch', 'en') }}" @selected(app()->getLocale() === 'en')>{{ __('ui.english') }}</option>
            </select>

            <!-- User Menu -->
            <div x-data="{ open: false }" class="relative">
                <button @click="open = !open" class="flex items-center gap-2 p-2 rounded-lg hover:bg-gray-100 transition">
                    @if(auth()->user()->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                             class="w-8 h-8 rounded-full object-cover">
                    @else
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    @endif
                    <span class="hidden sm:inline text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                </button>

                <div x-show="open" x-cloak @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1">
                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">{{ __('ui.profile') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('ui.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
