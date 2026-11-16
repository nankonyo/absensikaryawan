<x-app-layout>
    <x-slot name="header">{{ __('ui.units') }}</x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.units') }}</h2>
        <p class="text-sm text-gray-500">{{ __('ui.master_data') }}</p>
    </div>

    <form method="GET" action="{{ route('admin.units.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2">
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ __('ui.search_units') }}"
            class="flex-1 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
        >
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.search') }}
        </button>
        @if($search !== '')
            <a href="{{ route('admin.units.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition text-center">
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

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">
            {{ $editing ? __('ui.edit') : __('ui.create') }}
        </h3>

        <form action="{{ $editing ? route('admin.units.update', $editing) : route('admin.units.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @csrf
            @if($editing)
                @method('PUT')
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.department') }} *</label>
                <select name="department_id" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">{{ __('ui.select_department') }}</option>
                    @foreach($departments as $department)
                        <option value="{{ $department->id }}" @selected(old('department_id', $editing?->department_id) == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>
                @error('department_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.name') }} *</label>
                <input type="text" name="name" value="{{ old('name', $editing?->name) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.description') }}</label>
                <input type="text" name="description" value="{{ old('description', $editing?->description) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                @error('description')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    {{ $editing ? __('ui.update') : __('ui.save') }}
                </button>
                @if($editing)
                    <a href="{{ route('admin.units.index') }}" class="px-4 py-2 bg-gray-300 text-gray-800 rounded-lg">
                        {{ __('ui.cancel') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    <x-table-view
        :headers="[
            __('ui.department'),
            __('ui.name'),
            __('ui.employees'),
            __('ui.actions'),
        ]"
        :empty="__('ui.no_data')"
    >
        @foreach($units as $unit)
            <tr>
                <td data-label="{{ __('ui.department') }}" class="px-6 py-4 text-sm text-gray-500">{{ $unit->department?->name }}</td>
                <td data-label="{{ __('ui.name') }}" class="px-6 py-4 text-sm font-medium text-gray-900">{{ $unit->name }}</td>
                <td data-label="{{ __('ui.employees') }}" class="px-6 py-4 text-sm text-gray-500">{{ $unit->users_count }}</td>
                <td data-label="{{ __('ui.actions') }}" class="px-6 py-4 text-sm font-medium">
                    <x-action-menu>
                        <a href="{{ route('admin.units.index', ['edit' => $unit->id]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            {{ __('ui.edit') }}
                        </a>
                        <form action="{{ route('admin.units.destroy', $unit) }}" method="POST">
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
        {{ $units->links() }}
    </div>
</x-app-layout>
