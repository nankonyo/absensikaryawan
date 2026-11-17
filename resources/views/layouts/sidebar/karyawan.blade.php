<x-nav-link href="{{ route('karyawan.dashboard') }}" :active="request()->routeIs('karyawan.dashboard')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
    </svg>
    <span>{{ __('ui.dashboard') }}</span>
</x-nav-link>

<x-nav-link href="{{ route('karyawan.attendance.index') }}" :active="request()->routeIs('karyawan.attendance.*')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
    </svg>
    <span>{{ __('ui.my_attendance') }}</span>
</x-nav-link>

<x-nav-link href="{{ route('karyawan.leaves.index') }}" :active="request()->routeIs('karyawan.leaves.*')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
    </svg>
    <span>{{ __('ui.my_leave_requests') }}</span>
</x-nav-link>

<div class="mt-4 mb-2 border-t border-gray-200 pt-4">
    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('internalapp.dashboard') }}</p>
</div>

<x-nav-link href="{{ route('internal.dashboard') }}" :active="request()->routeIs('internal.dashboard')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
    </svg>
    <span>{{ __('internalapp.dashboard') }}</span>
</x-nav-link>

<div class="mt-4 mb-2 border-t border-gray-200 pt-4">
    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('internalapp.activities') }}</p>
</div>

<x-nav-link href="{{ route('internal.activities.index') }}" :active="request()->routeIs('internal.activities.*')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
    </svg>
    <span>{{ __('internalapp.my_activities') }}</span>
</x-nav-link>

<div class="mt-4 mb-2 border-t border-gray-200 pt-4">
    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('internalapp.projects') }}</p>
</div>

<x-nav-link href="{{ route('internal.projects.index') }}" :active="request()->routeIs('internal.projects.index', 'internal.projects.show', 'internal.projects.create', 'internal.projects.edit')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
    </svg>
    <span>{{ __('internalapp.project') }}</span>
</x-nav-link>

<x-nav-link href="{{ route('internal.projects.board') }}" :active="request()->routeIs('internal.projects.board')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h4v12H4V6zm6 0h4v8h-4V6zm6 0h4v10h-4V6z"></path>
    </svg>
    <span>{{ __('internalapp.kanban') }}</span>
</x-nav-link>

<x-nav-link href="{{ route('internal.projects.gantt') }}" :active="request()->routeIs('internal.projects.gantt')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
    </svg>
    <span>{{ __('internalapp.gantt') }}</span>
</x-nav-link>

<x-nav-link href="{{ route('internal.projects.guide') }}" :active="request()->routeIs('internal.projects.guide')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
    </svg>
    <span>{{ __('internalapp.project_guide') }}</span>
</x-nav-link>

