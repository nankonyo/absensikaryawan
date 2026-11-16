<x-app-layout>
    <x-slot name="header">{{ __('ui.attendance_verification') }}</x-slot>
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.attendance_verification') }}</h2>

    <div class="mb-6 flex flex-wrap gap-4">
        @foreach(['pending', 'valid', 'invalid', 'all'] as $option)
            <a href="{{ route($routePrefix.'.index', ['status' => $option]) }}"
               class="px-4 py-2 rounded-lg {{ $status === $option ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
                {{ $option === 'all' ? __('ui.all') : __('ui.statuses.'.$option) }}
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">
                {{ __('ui.attendance_verification_count', ['count' => $attendances->total()]) }}
            </h3>
        </div>

        <x-table-view
            :headers="[
                __('ui.employee'),
                __('ui.date'),
                __('ui.attendance_reason'),
                __('ui.check_in'),
                __('ui.check_out'),
                __('ui.location'),
                __('ui.status'),
                __('ui.actions'),
            ]"
            :empty="__('ui.no_attendance_verifications')"
        >
            @foreach($attendances as $attendance)
                <tr>
                    <td data-label="{{ __('ui.employee') }}" class="px-6 py-4 whitespace-nowrap">
                        <x-employee-cell :user="$attendance->user" />
                    </td>
                    <td data-label="{{ __('ui.date') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $attendance->date->format('d/m/Y') }}
                    </td>
                    <td data-label="{{ __('ui.attendance_reason') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        {{ $attendance->attendanceReason?->name ?? '-' }}
                        @if($attendance->notes)
                            <p class="mt-1 text-xs whitespace-pre-line text-gray-500">{{ $attendance->notes }}</p>
                        @endif
                    </td>
                    <td data-label="{{ __('ui.check_in') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}
                    </td>
                    <td data-label="{{ __('ui.check_out') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}
                    </td>
                    <td data-label="{{ __('ui.location') }}" class="px-6 py-4 text-sm text-gray-500">
                        {{ $attendance->check_in_address ?: ($attendance->check_in_latitude && $attendance->check_in_longitude ? $attendance->check_in_latitude.', '.$attendance->check_in_longitude : '-') }}
                    </td>
                    <td data-label="{{ __('ui.status') }}" class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                            {{ $attendance->statusLabel() }}
                        </span>
                    </td>
                    <td data-label="{{ __('ui.actions') }}" class="px-6 py-4 text-sm font-medium">
                        @if($attendance->isPending())
                            <form method="POST" action="{{ route($routePrefix.'.approve', $attendance) }}" class="flex min-w-56 flex-col gap-2">
                                @csrf
                                <input type="text" name="notes" value="{{ old('notes') }}"
                                       placeholder="{{ __('ui.verification_notes_help') }}"
                                       class="w-full rounded-lg border-gray-300 text-sm focus:ring-2 focus:ring-blue-500">
                                <div class="flex gap-2">
                                    <button type="submit" class="rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white hover:bg-green-700">
                                        {{ __('ui.approve') }}
                                    </button>
                                    <button type="submit" formaction="{{ route($routePrefix.'.reject', $attendance) }}"
                                            class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                        {{ __('ui.reject') }}
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="space-y-1 text-gray-500">
                                <p>{{ $attendance->verifier?->name ?? '-' }}</p>
                                <p class="text-xs">{{ $attendance->verification_notes }}</p>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-table-view>
    </div>

    <div class="mt-6">
        {{ $attendances->links() }}
    </div>
</x-app-layout>
