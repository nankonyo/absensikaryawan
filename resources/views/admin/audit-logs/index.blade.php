<x-app-layout>
    <x-slot name="header">{{ __('ui.audit_logs') }}</x-slot>
    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.audit_logs') }}</h2>

    <!-- Filters -->
    <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2">
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ __('ui.search_audit_logs') }}"
            class="flex-1 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
        >
        <select name="action" onchange="this.form.submit()"
                class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
            <option value="">{{ __('ui.all_actions') }}</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ __('ui.audit_actions.'.$action) }}</option>
            @endforeach
        </select>
        <input type="date" name="start_date" value="{{ request('start_date') }}" aria-label="{{ __('ui.start_date') }}"
               class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
        <input type="date" name="end_date" value="{{ request('end_date') }}" aria-label="{{ __('ui.end_date') }}"
               class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.filter') }}
        </button>
        @if($search !== '' || request()->filled('action') || request()->filled('start_date') || request()->filled('end_date'))
            <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition text-center">
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

    <!-- Logs Table -->
    <x-table-view
        :headers="[
            __('ui.time'),
            __('ui.users'),
            __('ui.activity'),
            __('ui.model'),
            __('ui.ip_address'),
            __('ui.actions'),
        ]"
        :empty="__('ui.no_audit_logs')"
    >
        @foreach($auditLogs as $log)
            <tr>
                <td data-label="{{ __('ui.time') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $log->created_at->format('d/m/Y H:i') }}
                </td>
                <td data-label="{{ __('ui.users') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ $log->user ? $log->user->name : __('ui.system') }}
                </td>
                <td data-label="{{ __('ui.activity') }}" class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ __('ui.audit_actions.'.$log->action) }}
                    </span>
                </td>
                <td data-label="{{ __('ui.model') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $log->model ? __('ui.audit_models.'.$log->model) : __('ui.empty') }}
                </td>
                <td data-label="{{ __('ui.ip_address') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $log->ip_address }}
                </td>
                <td data-label="{{ __('ui.actions') }}" class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <x-action-menu :height="72">
                        <a href="{{ route('admin.audit-logs.show', $log) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('ui.view') }}
                        </a>
                    </x-action-menu>
                </td>
            </tr>
        @endforeach
    </x-table-view>

    <div class="mt-6">
        {{ $auditLogs->links() }}
    </div>
</x-app-layout>
