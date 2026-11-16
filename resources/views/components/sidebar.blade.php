<aside :class="sidebarOpen ? 'translate-x-0' : ''"
       class="sidebar-scroll fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 overflow-y-auto transition-transform duration-200 -translate-x-full lg:translate-x-0">
    <div class="p-6 flex items-start justify-between gap-2">
        <div>
            @php($companyLogo = \App\Models\Setting::get('company_logo'))
            @if($companyLogo)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($companyLogo) }}"
                     alt="{{ config('app.name') }}"
                     class="h-10 w-auto max-w-full object-contain">
            @else
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ config('app.name') }}
                </h1>
            @endif
            <p class="text-sm text-gray-500 mt-1">
                {{ auth()->user()->role ? __('ui.roles.'.auth()->user()->role->name) : '' }}
            </p>
        </div>
        <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100" aria-label="{{ __('ui.close_menu') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <nav class="px-4 pb-4">
        @if(auth()->user()->isAdmin())
            @include('layouts.sidebar.admin')
        @elseif(auth()->user()->isAtasan())
            @include('layouts.sidebar.atasan')
        @elseif(auth()->user()->isKaryawan())
            @include('layouts.sidebar.karyawan')
        @endif
    </nav>
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
