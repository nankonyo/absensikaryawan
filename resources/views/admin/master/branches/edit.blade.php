<x-app-layout>
    <x-slot name="header">{{ __('ui.edit_branch') }}</x-slot>
    <div class="max-w-3xl">
        <div class="mb-6">
            <a href="{{ route('admin.branches.index') }}" class="text-blue-600 hover:underline">{{ __('ui.back_to_branches') }}</a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.edit_branch') }}</h2>

            <form action="{{ route('admin.branches.update', $branch) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.name') }} *</label>
                        <input type="text" name="name" value="{{ old('name', $branch->name) }}" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('name')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.code') }}</label>
                        <input type="text" name="code" value="{{ old('code', $branch->code) }}"
                               placeholder="PUSAT"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        @error('code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.address') }}</label>
                        <textarea name="address" rows="2"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">{{ old('address', $branch->address) }}</textarea>
                        @error('address')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="md:col-span-2 flex items-center gap-2">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active))
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            {{ __('ui.is_active') }}
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-4">
                    <a href="{{ route('admin.branches.index') }}"
                       class="px-6 py-2 bg-gray-300 hover:bg-gray-400 text-gray-800 rounded-lg transition">
                        {{ __('ui.cancel') }}
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        {{ __('ui.update_branch') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
