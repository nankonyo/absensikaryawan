<x-app-layout>
    <x-slot name="header">{{ __('ui.user_details') }}</x-slot>
    <div class="max-w-4xl">
        <div class="mb-6 flex items-center justify-between">
            <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_users') }}</a>
            <div class="flex gap-2">
                <a href="{{ route('admin.users.edit', $user) }}" 
                   class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition">
                    {{ __('ui.edit_user') }}
                </a>
                @if($user->id !== auth()->id())
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                onclick="return confirm('{{ __('ui.confirm_delete_user') }}')"
                                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                            {{ __('ui.delete_user') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- User Info Card -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center gap-6 mb-6">
                <div class="w-24 h-24 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-3xl font-bold">
                    {{ substr($user->name, 0, 1) }}
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
                    <p class="text-gray-600">{{ $user->position }}</p>
                    <span class="inline-flex items-center mt-2 rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ __('ui.roles.'.$user->role->name) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.employee_id') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->employee_id }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.email') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->email }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.department') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->department?->name ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.unit') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->unit?->name ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.branch') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->branch?->name ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.position') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->positionMaster?->name ?? $user->position ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.phone') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->phone ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.birth_place') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->birth_place ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.birth_date') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->birth_date?->format('d/m/Y') ?? __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.gender') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->gender ? __('ui.'.$user->gender) : __('ui.empty') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.employment_status') }}</p>
                    <p class="text-gray-900 font-medium">
                        {{ $user->isContract() ? __('ui.employment_contract') : __('ui.employment_permanent') }}
                    </p>
                </div>
                @if($user->isContract())
                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.contract_start_date') }}</p>
                        <p class="text-gray-900 font-medium">{{ $user->contract_start_date?->format('d/m/Y') ?? __('ui.empty') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.contract_end_date') }}</p>
                        <p class="text-gray-900 font-medium">{{ $user->contract_end_date?->format('d/m/Y') ?? __('ui.empty') }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-sm text-gray-500">{{ $user->isContract() ? __('ui.contract_document') : __('ui.permanent_document') }}</p>
                    @if($user->contract_document_url)
                        <a href="{{ $user->contract_document_url }}" target="_blank" class="text-blue-600 font-medium hover:underline">
                            {{ __('ui.view_contract_document') }}
                        </a>
                    @else
                        <p class="text-gray-900 font-medium">{{ __('ui.empty') }}</p>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.supervisor') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->supervisor ? $user->supervisor->name : __('ui.none') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.joined') }}</p>
                    <p class="text-gray-900 font-medium">{{ $user->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
        </div>

        @if($activeAttributes->isNotEmpty())
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.additional_attributes') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($activeAttributes as $attr)
                        @php
                            $value = $user->attributeValues->where('user_attribute_id', $attr->id)->first()?->value;
                        @endphp
                        <div>
                            <p class="text-sm text-gray-500">{{ $attr->label }}</p>
                            <p class="text-gray-900 font-medium">{{ $value ?? __('ui.empty') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Subordinates -->
        @if($user->subordinates->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.team_members', ['count' => $user->subordinates->count()]) }}</h3>
                <div class="space-y-3">
                    @foreach($user->subordinates as $subordinate)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                    {{ substr($subordinate->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $subordinate->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $subordinate->position }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.users.show', $subordinate) }}" 
                               class="text-blue-600 hover:underline text-sm">
                                {{ __('ui.view') }}
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
