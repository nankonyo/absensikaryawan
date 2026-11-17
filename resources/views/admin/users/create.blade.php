<x-app-layout>
    <x-slot name="header">{{ __('ui.create_user') }}</x-slot>
    <div class="max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.users.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_users') }}</a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.create_user') }}</h2>

            @php
                $picker = $picker + [
                    'department' => (string) old('department_id'),
                    'unit' => (string) old('unit_id'),
                    'position' => (string) old('position_id'),
                    'role' => (string) old('role_id'),
                    'supervisor' => (string) old('supervisor_id'),
                ];
            @endphp

            <form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" x-data="userForm(@js($picker))">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Avatar (face reference) -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.avatar_face_reference') }}</label>
                        <input type="file" name="avatar" accept="image/*"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <p class="text-xs text-gray-500 mt-1">{{ __('ui.avatar_help') }}</p>
                        @error('avatar')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.full_name_required') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.email') }} *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Employee ID: entered manually -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.employee_id') }} *</label>
                        <input type="text" name="employee_id" value="{{ old('employee_id') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('employee_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.password_required') }}</label>
                        <input type="password" name="password" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('phone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Place of birth -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.birth_place') }}</label>
                        <input type="text" name="birth_place" value="{{ old('birth_place') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('birth_place')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date of birth -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.birth_date') }}</label>
                        <input type="date" name="birth_date" value="{{ old('birth_date') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('birth_date')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Gender -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.gender') }}</label>
                        <select name="gender" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('ui.none') }}</option>
                            <option value="male" @selected(old('gender') === 'male')>{{ __('ui.male') }}</option>
                            <option value="female" @selected(old('gender') === 'female')>{{ __('ui.female') }}</option>
                        </select>
                        @error('gender')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Role: decides which organization fields open -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.role_required') }}</label>
                        <select name="role_id" required x-model="role" @change="roleChanged()"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('ui.select_role') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                    {{ __('ui.roles.'.$role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @include('admin.users.partials.org-fields')

                    @include('admin.users.partials.work-mode-fields')

                    <!-- Branch -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.branch') }}</label>
                        <select name="branch_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <option value="">{{ __('ui.none') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @include('admin.users.partials.contract-fields', ['user' => null])

                    <!-- Supervisor: higher-level users in the selected branch -->
                    <div class="md:col-span-2" x-show="!isTopLevel">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <span x-text="supervisorRequired ? '{{ __('ui.supervisor_required') }}' : '{{ __('ui.supervisor_optional') }}'"></span>
                        </label>
                        <input type="hidden" name="supervisor_id" :value="supervisor">
                        <div x-data="{ open: false }" @click.outside="open = false" class="relative">
                            <!-- Trigger -->
                            <button type="button" @click="open = !open"
                                    class="w-full flex items-center gap-3 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-left transition">
                                <img x-show="selectedSupervisor?.avatar_url" :src="selectedSupervisor?.avatar_url || ''" :alt="selectedSupervisor?.name"
                                     class="w-10 h-10 rounded-full object-cover border border-gray-200 shrink-0">
                                <div x-show="selectedSupervisor && !selectedSupervisor.avatar_url"
                                     class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold shrink-0"
                                     x-text="selectedSupervisor ? selectedSupervisor.name.charAt(0).toUpperCase() : ''"></div>
                                <div x-show="!selectedSupervisor"
                                     class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900" x-text="selectedSupervisor ? selectedSupervisor.name : '{{ __('ui.no_supervisor') }}'"></p>
                                    <p class="text-xs text-gray-500" x-show="selectedSupervisor" x-text="selectedSupervisor?.employee_id"></p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform duration-200" :class="open ? 'rotate-180' : ''"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown panel -->
                            <div x-show="open" x-transition x-cloak
                                 class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
                                <!-- No supervisor option -->
                                <button type="button" @click="supervisor = ''; open = false"
                                        class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 text-left border-b border-gray-100">
                                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 shrink-0">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                    <span class="text-sm text-gray-500">{{ __('ui.no_supervisor') }}</span>
                                </button>
                                <!-- Supervisor options -->
                                <template x-for="option in availableSupervisors" :key="option.id">
                                    <button type="button"
                                            @click="supervisor = String(option.id); open = false"
                                            :class="String(option.id) === String(supervisor) ? 'bg-blue-50' : 'hover:bg-gray-50'"
                                            class="w-full flex items-center gap-3 px-4 py-3 text-left">
                                        <img x-show="option.avatar_url" :src="option.avatar_url || ''" :alt="option.name"
                                             class="w-10 h-10 rounded-full object-cover border border-gray-200 shrink-0">
                                        <div x-show="!option.avatar_url"
                                             class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-semibold shrink-0"
                                             x-text="option.name.charAt(0).toUpperCase()"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900" x-text="option.name"></p>
                                            <p class="text-xs text-gray-500" x-text="option.employee_id"></p>
                                            <p class="text-xs text-gray-400" x-text="[option.department_name, option.position_name].filter(Boolean).join(' \u2022 ')"></p>
                                        </div>
                                        <svg x-show="String(option.id) === String(supervisor)" class="w-5 h-5 text-blue-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </button>
                                </template>
                            </div>
                        </div>
                        @error('supervisor_id')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @include('admin.users.partials.attribute-fields', ['user' => null])
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <a href="{{ route('admin.users.index') }}" 
                       class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                        {{ __('ui.cancel') }}
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        {{ __('ui.create_user') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
