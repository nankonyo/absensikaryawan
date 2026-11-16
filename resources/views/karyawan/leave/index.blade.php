<x-app-layout>
    <x-slot name="header">{{ __('ui.my_leave_requests') }}</x-slot>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.my_leave_requests') }}</h2>
        <a href="{{ route('karyawan.leaves.create') }}" 
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.request_leave') }}
        </a>
    </div>

    <x-table-view
        :headers="[
            __('ui.period'),
            __('ui.days_label'),
            __('ui.type'),
            __('ui.status'),
            __('ui.actions'),
        ]"
        :empty="__('ui.no_leave_requests_yet')"
    >
        @foreach($leaves as $leave)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ $leave->start_date->format('d/m/Y') }} - {{ $leave->end_date->format('d/m/Y') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ __('ui.days', ['count' => $leave->days]) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ $leave->type_label }}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @if($leave->status === 'pending')
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.pending') }}</span>
                    @elseif($leave->status === 'approved')
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.approved') }}</span>
                    @else
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.statuses.rejected') }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="{{ route('karyawan.leaves.show', $leave) }}" class="text-blue-600 hover:underline">{{ __('ui.view') }}</a>
                </td>
            </tr>
        @endforeach

    </x-table-view>

    <div class="mt-6">
        {{ $leaves->links() }}
    </div>
</x-app-layout>
