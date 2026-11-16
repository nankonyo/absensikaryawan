<x-app-layout>
    <x-slot name="header">{{ __('ui.admin_dashboard') }}</x-slot>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('ui.total_employees') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalKaryawan }}</p>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ __('ui.employment_permanent') }}: {{ $permanentEmployees }}
                    </p>
                    <p class="text-sm text-gray-600">
                        {{ __('ui.employment_contract') }}: {{ $contractEmployees }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Attendance -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('ui.todays_attendance') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $todayAttendances }}</p>
                    <p class="text-sm text-orange-600 mt-1">{{ __('ui.late_count', ['count' => $todayLate]) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('ui.pending_approvals') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $pendingLeaves }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ __('ui.pending_leave_requests') }}</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Attendance Chart -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.last_7_days_attendance') }}</h3>
            <div class="space-y-3">
                @foreach($last7Days as $day)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-gray-600">{{ $day['date'] }}</span>
                            <span class="text-gray-900 font-medium">{{ $day['count'] }} / {{ $totalKaryawan }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $totalKaryawan > 0 ? ($day['count'] / $totalKaryawan * 100) : 0 }}%"></div>
                        </div>
                        @if($day['late'] > 0)
                            <p class="text-xs text-orange-600 mt-1">{{ __('ui.late_count', ['count' => $day['late']]) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Attendance -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.recent_check_ins') }}</h3>
            <div class="space-y-3">
                @forelse($recentAttendances as $attendance)
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 last:border-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                {{ substr($attendance->user->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $attendance->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $attendance->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}
                            </p>
                            @if($attendance->late_duration > 0)
                                <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                    {{ __('ui.statuses.late') }}
                                </span>
                            @else
                                <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                    {{ __('ui.on_time') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">{{ __('ui.no_recent_attendance') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="{{ route('admin.users.create') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">{{ __('ui.add_new_employee') }}</h4>
                    <p class="text-sm text-gray-500">{{ __('ui.create_new_user_account') }}</p>
                </div>
            </div>
        </a>

        <a href="{{ route('admin.reports.index') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900">{{ __('ui.generate_reports') }}</h4>
                    <p class="text-sm text-gray-500">{{ __('ui.export_attendance_reports') }}</p>
                </div>
            </div>
        </a>
    </div>
</x-app-layout>
