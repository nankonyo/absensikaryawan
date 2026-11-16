<x-app-layout>
    <x-slot name="header">{{ __('ui.leave_approval') }}</x-slot>
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.leave_approval') }}</h2>

    <div class="mb-6 flex flex-wrap gap-4">
        <a href="{{ route('atasan.approvals.index', ['status' => 'all']) }}"
           class="px-4 py-2 rounded-lg {{ $status === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('ui.all') }}
        </a>
        <a href="{{ route('atasan.approvals.index', ['status' => 'pending']) }}"
           class="px-4 py-2 rounded-lg {{ $status === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('ui.statuses.pending') }}
        </a>
        <a href="{{ route('atasan.approvals.index', ['status' => 'approved']) }}"
           class="px-4 py-2 rounded-lg {{ $status === 'approved' ? 'bg-green-600 text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('ui.statuses.approved') }}
        </a>
        <a href="{{ route('atasan.approvals.index', ['status' => 'rejected']) }}"
           class="px-4 py-2 rounded-lg {{ $status === 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('ui.statuses.rejected') }}
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.leave_requests_count', ['count' => $leaves->total()]) }}</h3>
        </div>
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
                        <div class="text-sm font-medium text-gray-900">{{ $leave->user->name }}</div>
                        <div class="text-xs text-gray-500">{{ $leave->user->employee_id }}</div>
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
                        <a href="{{ route('atasan.approvals.show', $leave) }}"
                           class="text-blue-600 hover:underline">
                            {{ $leave->status === 'pending' ? __('ui.review') : __('ui.view') }}
                        </a>
                    </td>
                </tr>
            @endforeach
        </x-table-view>
    </div>

    <div class="mt-6">
        {{ $leaves->links() }}
    </div>
</x-app-layout>
