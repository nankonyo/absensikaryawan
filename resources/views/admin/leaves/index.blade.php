<x-app-layout>
    <x-slot name="header">{{ __('ui.leave_approval') }}</x-slot>
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.leave_approval') }}</h2>

    <form method="GET" action="{{ route('admin.leaves.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2">
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ __('ui.search_leaves') }}"
            class="flex-1 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
        >
        <select name="status" onchange="this.form.requestSubmit()"
                class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
            <option value="">{{ __('ui.all_status') }}</option>
            <option value="pending" @selected($status === 'pending')>{{ __('ui.statuses.pending') }}</option>
            <option value="approved" @selected($status === 'approved')>{{ __('ui.statuses.approved') }}</option>
            <option value="rejected" @selected($status === 'rejected')>{{ __('ui.statuses.rejected') }}</option>
        </select>
        <select name="leave_type_id" onchange="this.form.requestSubmit()"
                class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
            <option value="">{{ __('ui.all_leave_types') }}</option>
            @forelse($leaveTypes ?? [] as $leaveType)
                <option value="{{ $leaveType->id }}" @selected((int) $leaveTypeId === $leaveType->id)>{{ $leaveType->name }}</option>
            @empty
                <option value="" disabled>{{ __('ui.no_leave_types_available') }}</option>
            @endforelse
        </select>
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.search') }}
        </button>
        @if($search !== '' || $status || $leaveTypeId)
            <a href="{{ route('admin.leaves.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition text-center">
                {{ __('ui.reset') }}
            </a>
        @endif
        <label class="flex items-center gap-2 text-sm text-gray-600">
            {{ __('ui.per_page') }}
            <select name="per_page" onchange="this.form.requestSubmit()"
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
            __('ui.period'),
            __('ui.days_label'),
            __('ui.type'),
            __('ui.status'),
            __('ui.actions'),
        ]"
        :empty="__('ui.no_leave_requests')"
    >
        @foreach($leaves as $leave)
            <tr>
                <td data-label="{{ __('ui.employee') }}" class="px-6 py-4 whitespace-nowrap">
                    <x-employee-cell :user="$leave->user" />
                </td>
                <td data-label="{{ __('ui.period') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }}
                </td>
                <td data-label="{{ __('ui.days_label') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ __('ui.days', ['count' => $leave->days]) }}
                </td>
                <td data-label="{{ __('ui.type') }}" class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ $leave->type_label }}
                    </span>
                </td>
                <td data-label="{{ __('ui.status') }}" class="px-6 py-4 whitespace-nowrap">
                    @if($leave->status === 'pending')
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.pending') }}</span>
                    @elseif($leave->status === 'approved')
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.approved') }}</span>
                    @else
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.rejected') }}</span>
                    @endif
                </td>
                <td data-label="{{ __('ui.actions') }}" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <x-action-menu :height="72">
                        <a href="{{ route('admin.leaves.show', $leave) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('ui.view') }}
                        </a>
                    </x-action-menu>
                </td>
            </tr>
        @endforeach
    </x-table-view>

    <div class="mt-6">
        {{ $leaves->links() }}
    </div>
</x-app-layout>
