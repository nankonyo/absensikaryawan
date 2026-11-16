<x-app-layout>
    <x-slot name="header">{{ __('ui.user_management') }}</x-slot>
    <div class="mb-6 flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.users') }}</h2>
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.add_new_user') }}
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-4 flex flex-col sm:flex-row gap-2">
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="{{ __('ui.search_users') }}"
            class="flex-1 rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500"
        >
        <select name="department_id" onchange="this.form.submit()"
                class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
            <option value="">{{ __('ui.all_departments') }}</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" @selected((string) $departmentId === (string) $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        @if($departmentId)
            <select name="unit_id" onchange="this.form.submit()"
                    class="rounded-lg border-gray-300 focus:ring-2 focus:ring-blue-500">
                <option value="">{{ __('ui.all_units') }}</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}" @selected((string) $unitId === (string) $unit->id)>
                        {{ $unit->name }}
                    </option>
                @endforeach
            </select>
        @endif
        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
            {{ __('ui.search') }}
        </button>
        @if($search !== '' || $departmentId || $unitId)
            <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg transition text-center">
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
            __('ui.name'),
            __('ui.email'),
            __('ui.role'),
            __('ui.department'),
            __('ui.position'),
            __('ui.actions'),
        ]"
        :empty="__('ui.no_users_found')"
    >
        @foreach($users as $user)
            <tr>
                <td data-label="{{ __('ui.name') }}" class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-10 h-10 rounded-full object-cover">
                        @else
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold">
                                {{ substr($user->name, 0, 1) }}
                            </div>
                        @endif
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->employee_id }}</p>
                        </div>
                    </div>
                </td>
                <td data-label="{{ __('ui.email') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $user->email }}
                </td>
                <td data-label="{{ __('ui.role') }}" class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ __('ui.roles.'.$user->role->name) }}
                    </span>
                </td>
                <td data-label="{{ __('ui.department') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $user->department?->name ?? __('ui.empty') }}
                    @if($user->unit)
                        <span class="block text-xs">{{ $user->unit->name }}</span>
                    @endif
                </td>
                <td data-label="{{ __('ui.position') }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ $user->positionMaster?->name ?? $user->position ?? __('ui.empty') }}
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
                                const menuHeight = 132;

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
                            <a href="{{ route('admin.users.show', $user) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ __('ui.view') }}
                            </a>
                            <a href="{{ route('admin.users.edit', $user) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                {{ __('ui.edit') }}
                            </a>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-gray-100" onclick="return confirm('{{ __('ui.confirm_title') }}')">
                                        {{ __('ui.delete') }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table-view>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</x-app-layout>
