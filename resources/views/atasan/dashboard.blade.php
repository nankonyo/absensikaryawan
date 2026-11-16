<x-app-layout>
    <x-slot name="header">{{ __('ui.supervisor_dashboard') }}</x-slot>
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Team -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('ui.my_team') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $teamStats['total_team'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ __('ui.employees') }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today Checked In -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('ui.checked_in_today') }}</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $teamStats['checked_in'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ __('ui.of_total', ['count' => $teamStats['total_team']]) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Late Today -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">{{ __('ui.late_today') }}</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2">{{ $teamStats['late'] }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ __('ui.employees') }}</p>
                </div>
                <div class="p-3 bg-orange-100 rounded-full">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members grouped by department -->
    @include('atasan.partials.team-members', ['groupedTeam' => $groupedTeam])

    <!-- Today's Team Attendance -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.todays_team_attendance') }}</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full table-view">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.employee') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.check_in') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.check_out') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('ui.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($todayAttendances as $attendance)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                        {{ substr($attendance->user->name, 0, 1) }}
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $attendance->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $attendance->user->position }}</p>
                                    </div>
                                </div>
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
                        @foreach($subordinates as $subordinate)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-semibold">
                                            {{ substr($subordinate->name, 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $subordinate->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $subordinate->position }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                        {{ __('ui.statuses.absent') }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @unless(auth()->user()->canVerifyLeaves())
        <!-- Pending Approvals Section -->
        <div class="grid grid-cols-1 gap-6">
        <!-- Pending Leave Requests -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.pending_leave') }}</h3>
                <a href="{{ route('atasan.approvals.index') }}" class="text-sm text-blue-600 hover:underline">{{ __('ui.view_all') }}</a>
            </div>
            <div class="p-6 space-y-4">
                @forelse($recentLeaves as $leave)
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $leave->user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }} • {{ __('ui.days', ['count' => $leave->days]) }}</p>
                            <p class="text-xs text-gray-600 mt-1">{{ $leave->type_label }}</p>
                        </div>
                        <a href="{{ route('atasan.approvals.show', $leave) }}" class="px-3 py-1 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded">
                            {{ __('ui.review') }}
                        </a>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">{{ __('ui.no_pending_leave_requests') }}</p>
                @endforelse
            </div>
        </div>
        </div>
    @endunless
</x-app-layout>
