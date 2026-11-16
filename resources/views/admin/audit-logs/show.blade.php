<x-app-layout>
    <x-slot name="header">{{ __('ui.audit_log_details') }}</x-slot>
    <div class="max-w-4xl mx-auto">
        <div class="mb-6">
            <a href="{{ route('admin.audit-logs.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_audit_logs') }}</a>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.audit_log_details') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.time') }}</p>
                    <p class="text-gray-900 font-medium">{{ $auditLog->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.users') }}</p>
                    <p class="text-gray-900 font-medium">{{ $auditLog->user?->name ?? __('ui.system') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.activity') }}</p>
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ __('ui.audit_actions.'.$auditLog->action) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.model') }}</p>
                    <p class="text-gray-900 font-medium">
                        {{ $auditLog->model ? __('ui.audit_models.'.$auditLog->model) : __('ui.empty') }}
                        @if($auditLog->model_id)
                            <span class="text-gray-500">#{{ $auditLog->model_id }}</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.ip_address') }}</p>
                    <p class="text-gray-900 font-medium">{{ $auditLog->ip_address ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.user_agent') }}</p>
                    <p class="text-gray-900 font-medium break-all">{{ $auditLog->user_agent ?? __('ui.empty') }}</p>
                </div>
            </div>
        </div>

        @foreach(['old_values' => __('ui.old_values'), 'new_values' => __('ui.new_values')] as $field => $label)
            @if($auditLog->{$field})
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $label }}</h3>
                    <div class="space-y-2">
                        @foreach($auditLog->{$field} as $key => $value)
                            <div class="flex justify-between gap-4 border-b border-gray-100 pb-2 text-sm">
                                <span class="text-gray-500">{{ $key }}</span>
                                <span class="text-gray-900 text-right break-all">{{ is_array($value) ? json_encode($value) : $value }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</x-app-layout>
