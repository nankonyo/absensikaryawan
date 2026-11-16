<x-app-layout>
    <x-slot name="header">{{ __('ui.dashboard') }}</x-slot>
    <!-- Dashboard Content -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Today's Attendance Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500">{{ __('ui.today_status') }}</h3>
                @if($todayAttendance && $todayAttendance->check_in)
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ __('ui.checked_in') }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ __('ui.not_yet') }}
                    </span>
                @endif
            </div>
            @if($todayAttendance && $todayAttendance->check_in)
                <p class="text-2xl font-bold text-gray-900">{{ \Carbon\Carbon::parse($todayAttendance->check_in)->format('H:i') }}</p>
                <p class="text-sm text-gray-500 mt-1">{{ __('ui.check_in_time') }}</p>
            @else
                <a href="{{ route('karyawan.attendance.check-in') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    {{ __('ui.check_in_now') }}
                </a>
            @endif
        </div>

        <!-- Monthly Attendance -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">{{ __('ui.this_month') }}</h3>
            <p class="text-2xl font-bold text-gray-900">{{ __('ui.days', ['count' => $monthlyStats['total_days']]) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ __('ui.attendance') }}</p>
        </div>

        <!-- Late Count -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">{{ __('ui.late_this_month') }}</h3>
            <p class="text-2xl font-bold text-orange-600">{{ __('ui.times', ['count' => $monthlyStats['late_count']]) }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ __('ui.tardiness') }}</p>
        </div>

        <!-- Pending Requests -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-4">{{ __('ui.statuses.pending') }}</h3>
            <p class="text-2xl font-bold text-gray-900">{{ $pendingLeaves }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ __('ui.requests') }}</p>
        </div>
    </div>

    <!-- Recent Attendance -->
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.recent_attendance') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-view">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.date') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.check_in') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.check_out') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentAttendances as $attendance)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $attendance->date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($attendance->late_duration > 0)
                                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                        {{ $attendance->lateDurationLabel() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                        {{ __('ui.on_time') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                {{ __('ui.no_attendance_records_yet') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
