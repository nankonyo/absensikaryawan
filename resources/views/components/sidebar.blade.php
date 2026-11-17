<aside id="sidebar"
       :class="sidebarOpen ? 'translate-x-0' : ''"
       class="sidebar-shell fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 -translate-x-full flex-col justify-between border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0">
    <div class="sidebar-scroll flex-1 overflow-y-auto p-3">
        <div class="mb-2 flex items-start justify-between gap-2 border-b border-gray-100 px-2 pb-3 pt-1">
            <div class="flex min-w-0 items-center gap-2.5">
                @php($companyLogo = \App\Models\Setting::get('company_logo'))
                @if($companyLogo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($companyLogo) }}"
                         alt="{{ config('app.name') }}"
                         class="h-10 w-10 shrink-0 rounded-xl border border-gray-100 bg-white object-contain p-1">
                @else
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600 text-sm font-black text-white">
                        {{ strtoupper(substr(config('app.name'), 0, 1)) }}
                    </span>
                @endif
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-extrabold tracking-tight text-gray-900">
                        {{ config('app.name') }}
                    </h1>
                    <p class="mt-0.5 truncate text-[10px] font-semibold uppercase tracking-wider text-gray-400">
                        {{ auth()->user()->role ? __('ui.roles.'.auth()->user()->role->name) : '' }}
                    </p>
                </div>
            </div>
            <button @click="sidebarOpen = false" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="{{ __('ui.close_menu') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <nav class="space-y-1">
            @if(auth()->user()->isAdmin())
                @include('layouts.sidebar.admin')
            @elseif(auth()->user()->isAtasan())
                @include('layouts.sidebar.atasan')
            @elseif(auth()->user()->isKaryawan())
                @include('layouts.sidebar.karyawan')
            @endif
        </nav>
    </div>

    <div class="shrink-0 border-t border-gray-100 p-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="group flex w-full items-center gap-3 rounded-xl p-2.5 text-sm font-medium text-red-600 transition-all hover:bg-red-50">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>{{ __('ui.logout') }}</span>
            </button>
        </form>
    </div>
</aside>

<script>
    (() => {
        const key = 'sidebar.scrollTop';
        const sidebar = document.querySelector('.sidebar-scroll');

        if (!sidebar) {
            return;
        }

        const saved = sessionStorage.getItem(key);

        if (saved !== null) {
            sidebar.scrollTop = Number(saved);
        }

        const save = () => sessionStorage.setItem(key, String(sidebar.scrollTop));

        sidebar.addEventListener('scroll', save, { passive: true });
        sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', save));
        window.addEventListener('pagehide', save);
    })();
</script>
