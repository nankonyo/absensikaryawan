<x-app-layout>
    <x-slot name="header">{{ __('ui.create_attribute') }}</x-slot>
    <div class="max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.user-attributes.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_user_attributes') }}</a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.create_attribute') }}</h2>

            <form action="{{ route('admin.user-attributes.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Label -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.attribute_label') }} *</label>
                        <input type="text" name="label" value="{{ old('label') }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('label')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.attribute_name') }} *</label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               placeholder="npwp"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.attribute_type') }} *</label>
                        <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            @foreach(['text', 'date', 'textarea', 'select'] as $type)
                                <option value="{{ $type }}" @selected(old('type') === $type)>{{ __("ui.attr_type_{$type}") }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.sort_order') }}</label>
                        <input type="number" name="sort_order" min="0" value="{{ old('sort_order', 0) }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('sort_order')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Options -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.attribute_options') }}</label>
                        <input type="text" name="options" value="{{ old('options') }}"
                               placeholder="Mandiri, BRI, BCA"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">{{ __('ui.attribute_options_help') }}</p>
                    </div>

                    <!-- Is Active -->
                    <div class="md:col-span-2 flex items-center gap-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ __('ui.is_active') }}
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <a href="{{ route('admin.user-attributes.index') }}"
                       class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                        {{ __('ui.cancel') }}
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        {{ __('ui.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
