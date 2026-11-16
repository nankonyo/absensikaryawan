<x-app-layout>
    <x-slot name="header">{{ __('ui.attendances') }}</x-slot>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.attendance_records') }}</h2>
    </div>

    <form method="GET" action="{{ route('admin.attendances.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2">
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ __('ui.search_attendances') }}"
            class="flex-1 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
        >
        <input
            type="month"
            name="month"
            value="{{ $month }}"
            class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
        >
        <select name="status" onchange="this.form.submit()"
                class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
            <option value="">{{ __('ui.all_status') }}</option>
            <option value="valid" @selected($status === 'valid')>{{ __('ui.statuses.valid') }}</option>
            <option value="pending" @selected($status === 'pending')>{{ __('ui.statuses.pending') }}</option>
            <option value="invalid" @selected($status === 'invalid')>{{ __('ui.statuses.invalid') }}</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.filter') }}
        </button>
        @if($search !== '' || $status || $month !== now()->format('Y-m'))
            <a href="{{ route('admin.attendances.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition text-center">
                {{ __('ui.reset') }}
            </a>
        @endif
        <label class="flex items-center gap-2 text-sm text-gray-600">
            {{ __('ui.per_page') }}
            <select name="per_page" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                @foreach([5, 10, 15, 25, 50, 100] as $option)
                    <option value="{{ $option }}" @selected((int) $perPage === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
    </form>

    <x-table-view
        :headers="[
            __('ui.employee'),
            __('ui.date'),
            __('ui.check_in'),
            __('ui.check_out'),
            __('ui.status'),
            __('ui.actions'),
        ]"
        :empty="__('ui.no_attendance_records')"
    >
        @foreach($attendances as $attendance)
            <tr>
                <td data-label="{{ __('ui.employee') }}" class="px-6 py-4 whitespace-nowrap">
                    <x-employee-cell :user="$attendance->user" />
                </td>
                <td data-label="{{ __('ui.date') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $attendance->date->format('d/m/Y') }}
                </td>
                <td data-label="{{ __('ui.check_in') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}
                </td>
                <td data-label="{{ __('ui.check_out') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}
                </td>
                <td data-label="{{ __('ui.status') }}" class="px-6 py-4 whitespace-nowrap">
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
                <td data-label="{{ __('ui.actions') }}" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <div class="relative inline-block text-left" x-data="{ open: false, top: 0, left: 0 }">
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                            aria-label="{{ __('ui.actions') }}"
                            :aria-expanded="open"
                            @click="
                                const rect = $el.getBoundingClientRect();
                                const menuHeight = 72;

                                open = ! open;
                                top = rect.bottom + menuHeight > window.innerHeight
                                    ? rect.top - menuHeight - 4
                                    : rect.bottom + 4;
                                left = Math.max(8, Math.min(rect.right - 192, window.innerWidth - 200));
                            "
                            @keydown.escape.window="open = false"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                            </svg>
                        </button>

                        <div
                            x-show="open"
                            x-transition
                            @click.outside="open = false"
                            :style="`top: ${top}px; left: ${left}px;`"
                            class="fixed z-50 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
                            style="display: none;"
                        >
                            <a href="{{ route('admin.attendances.show', $attendance) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ __('ui.view') }}
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table-view>

    <div class="mt-6">
        {{ $attendances->links() }}
    </div>
</x-app-layout>
