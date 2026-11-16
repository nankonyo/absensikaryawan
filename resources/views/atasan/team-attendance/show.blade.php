<x-app-layout>
    <x-slot name="header">{{ __('ui.employee_attendance_history') }}</x-slot>
    <div class="max-w-5xl mx-auto">
        <div class="mb-6">
            <a href="{{ route($routePrefix.'.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_team_attendance') }}</a>
        </div>

        <!-- Employee Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-2xl font-bold">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                    <p class="text-gray-600">{{ $user->position }}</p>
                    <p class="text-sm text-gray-500">{{ $user->employee_id }}</p>
                </div>
            </div>
        </div>

        <!-- Attendance History -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.attendance_history') }}</h3>
            </div>
            <x-table-view
                :headers="[
                    __('ui.date'),
                    __('ui.check_in'),
                    __('ui.check_out'),
                    __('ui.status'),
                ]"
                :empty="__('ui.no_attendance_records')"
            >
                @foreach($attendances as $attendance)
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
                            @if($attendance->status !== 'valid')
                                <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                    {{ $attendance->statusLabel() }}
                                </span>
                            @elseif($attendance->late_duration > 0)
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
                @endforeach

            </x-table-view>
        </div>

        <div class="mt-6">
            {{ $attendances->links() }}
        </div>
    </div>
</x-app-layout>
