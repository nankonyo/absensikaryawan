<x-nav-link href="{{ route('atasan.dashboard') }}" :active="request()->routeIs('atasan.dashboard')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
    </svg>
    <span>{{ __('ui.dashboard') }}</span>
</x-nav-link>

@unless(auth()->user()->canVerifyLeaves())
    <x-nav-link href="{{ route('atasan.approvals.index') }}" :active="request()->routeIs('atasan.approvals.*')">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <span>{{ __('ui.leave_approval') }}</span>
    </x-nav-link>
@endunless

@if(auth()->user()->canVerifyLeaves())
    <x-nav-link href="{{ route('verifikasi.leaves.index') }}" :active="request()->routeIs('verifikasi.leaves.*')">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
        </svg>
        <span>{{ __('ui.verify_leave') }}</span>
    </x-nav-link>
@endif

@if(auth()->user()->canManageUsers())
    <x-nav-link href="{{ route('admin.users.index') }}" :active="request()->routeIs('admin.users.*')">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
        </svg>
        <span>{{ __('ui.user_management') }}</span>
    </x-nav-link>
@endif

<x-nav-link href="{{ route('atasan.team-attendance.index') }}" :active="request()->routeIs('atasan.team-attendance.*')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
    </svg>
    <span>{{ __('ui.team_attendance') }}</span>
</x-nav-link>

<x-nav-link href="{{ route('atasan.attendance-verifications.index') }}" :active="request()->routeIs('atasan.attendance-verifications.*')">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
    </svg>
    <span>{{ __('ui.attendance_verification') }}</span>
</x-nav-link>

<div class="mt-4 mb-2 border-t border-gray-200 pt-4">
    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('ui.me') }}</p>
</div>

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
