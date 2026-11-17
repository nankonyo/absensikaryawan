<x-app-layout>
    <x-slot name="header">{{ __('ui.leave_type_master') }}</x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.leave_type_master') }}</h2>
        <p class="text-sm text-gray-500">{{ __('ui.master_data') }}</p>
    </div>

    <form method="GET" action="{{ route('admin.leave-types.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2">
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ __('ui.search_leave_types') }}"
            class="flex-1 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
        >
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.search') }}
        </button>
        @if($search !== '')
            <a href="{{ route('admin.leave-types.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition text-center">
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

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $editing ? __('ui.edit') : __('ui.create') }}
        </h3>

        <form action="{{ $editing ? route('admin.leave-types.update', $editing) : route('admin.leave-types.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @csrf
            @if($editing)
                @method('PUT')
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.name') }} *</label>
                <input type="text" name="name" value="{{ old('name', $editing?->name) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.leave_category') }} *</label>
                <select name="category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    @foreach(['sakit', 'izin', 'cuti'] as $category)
                        <option value="{{ $category }}" @selected(old('category', $editing?->category ?? 'cuti') === $category)>
                            {{ __('ui.leave_categories')[$category] }}
                        </option>
                    @endforeach
                </select>
                @error('category')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="md:col-span-3 flex flex-wrap items-center gap-6">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="requires_attachment" value="1" @checked(old('requires_attachment', $editing?->requires_attachment ?? false))
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    {{ __('ui.requires_attachment') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="requires_reason" value="1" @checked(old('requires_reason', $editing?->requires_reason ?? true))
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    {{ __('ui.requires_reason') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="deducts_quota" value="1" @checked(old('deducts_quota', $editing?->deducts_quota ?? false))
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    {{ __('ui.deducts_quota') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editing?->is_active ?? true))
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    {{ __('ui.is_active') }}
                </label>
            </div>

            <div class="md:col-span-3 flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    {{ $editing ? __('ui.update') : __('ui.save') }}
                </button>
                @if($editing)
                    <a href="{{ route('admin.leave-types.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg">
                        {{ __('ui.cancel') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    <x-table-view
        :headers="[
            __('ui.name'),
            __('ui.leave_category'),
            __('ui.leave_type_rules'),
            __('ui.is_active'),
            __('ui.actions'),
        ]"
        :empty="__('ui.no_data')"
    >
        @foreach($leaveTypes as $leaveType)
            <tr>
                <td data-label="{{ __('ui.name') }}" class="px-6 py-4 text-sm font-medium text-gray-900">{{ $leaveType->name }}</td>
                <td data-label="{{ __('ui.leave_category') }}" class="px-6 py-4 text-sm text-gray-700">
                    {{ __('ui.leave_categories')[$leaveType->category] }}
                </td>
                <td data-label="{{ __('ui.leave_type_rules') }}" class="px-6 py-4 text-sm">
                    <div class="flex flex-wrap gap-1">
                        @if($leaveType->requires_attachment)
                            <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.requires_attachment') }}</span>
                        @endif
                        @if($leaveType->requires_reason)
                            <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.requires_reason') }}</span>
                        @endif
                        @if($leaveType->deducts_quota)
                            <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                {{ __('ui.deducts_quota') }}
                            </span>
                        @endif
                        @unless($leaveType->requires_attachment || $leaveType->requires_reason || $leaveType->deducts_quota)
                            <span class="text-xs text-gray-500">{{ __('ui.leave_type_no_rules') }}</span>
                        @endunless
                    </div>
                </td>
                <td data-label="{{ __('ui.is_active') }}" class="px-6 py-4 text-sm">
                    @if($leaveType->is_active)
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.active') }}</span>
                    @else
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">{{ __('ui.inactive') }}</span>
                    @endif
                </td>
                <td data-label="{{ __('ui.actions') }}" class="px-6 py-4 text-sm font-medium">
                    <x-action-menu>
                        <a href="{{ route('admin.leave-types.index', array_merge(request()->query(), ['edit' => $leaveType->id])) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('ui.edit') }}
                        </a>
                        <form action="{{ route('admin.leave-types.destroy', $leaveType) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100" onclick="return confirm('{{ __('ui.confirm_title') }}')">
                                {{ __('ui.delete') }}
                            </button>
                        </form>
                    </x-action-menu>
                </td>
            </tr>
        @endforeach
    </x-table-view>

    <div class="mt-6">
        {{ $leaveTypes->links() }}
    </div>
</x-app-layout>
