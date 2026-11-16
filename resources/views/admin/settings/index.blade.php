<x-app-layout>
    <x-slot name="header">{{ __('ui.settings') }}</x-slot>
    <div class="max-w-4xl">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.system_settings') }}</h2>

        <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- Work Hours Settings -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.work_hours') }}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.work_start_time') }}</label>
                        <input type="time" name="work_start_time" value="{{ $settings['work_start_time']->value ?? '08:00' }}" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.work_end_time') }}</label>
                        <input type="time" name="work_end_time" value="{{ $settings['work_end_time']->value ?? '17:00' }}" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.late_tolerance_minutes') }}</label>
                        <input type="number" name="late_tolerance" value="{{ $settings['late_tolerance']->value ?? 15 }}" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.timezone') }}</label>
                        <input type="text" name="timezone" list="timezone-options"
                               value="{{ $settings['timezone']->value ?? 'Asia/Jakarta' }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <datalist id="timezone-options">
                            <option value="Asia/Jakarta">
                            <option value="Asia/Makassar">
                            <option value="Asia/Jayapura">
                            <option value="UTC">
                        </datalist>
                        <p class="mt-1 text-sm text-gray-500">{{ __('ui.timezone_help') }}</p>
                        @error('timezone')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Leave Settings -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.leave_settings') }}</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.annual_leave_quota') }}</label>
                        <input type="number" name="annual_leave_quota" value="{{ $settings['annual_leave_quota']->value ?? 12 }}"
                               min="1" max="365"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <p class="mt-1 text-sm text-gray-500">{{ __('ui.annual_leave_quota_help') }}</p>
                        @error('annual_leave_quota')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- GPS & Location Settings -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.gps_location_settings') }}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.latitude') }}</label>
                        <input type="number" name="latitude" step="any" min="-90" max="90"
                               value="{{ $office->latitude ?? '-6.200000' }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.longitude') }}</label>
                        <input type="number" name="longitude" step="any" min="-180" max="180"
                               value="{{ $office->longitude ?? '106.816666' }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.radius_meters') }}</label>
                        <input type="number" name="radius" value="{{ $office->radius ?? 100 }}" 
                               min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                        <p class="mt-1 text-sm text-gray-500">{{ __('ui.radius_help') }}</p>
                    </div>

                    <p class="text-sm text-gray-500 md:col-span-2">
                        {{ __('ui.maps_help') }}
                    </p>
                </div>
            </div>

            <!-- Face Recognition Settings -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.face_recognition') }}</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="flex items-center">
                            <input type="hidden" name="face_recognition_enabled" value="0">
                            <input type="checkbox" name="face_recognition_enabled" value="1" 
                                   {{ ($settings['face_recognition_enabled']->value ?? false) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ __('ui.enable_face_recognition') }}</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.recognition_threshold') }}</label>
                        <input type="number" name="face_recognition_threshold" value="{{ $settings['face_recognition_threshold']->value ?? 80 }}" 
                               min="0" max="100"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                </div>
            </div>

            <!-- Company Info -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('ui.company_information') }}</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.company_name') }}</label>
                        <input type="text" name="company_name" value="{{ $settings['company_name']->value ?? '' }}" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.company_address') }}</label>
                        <textarea name="company_address" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>{{ $settings['company_address']->value ?? '' }}</textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('ui.company_logo') }}</label>

                        @if($settings['company_logo']->value ?? null)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($settings['company_logo']->value) }}"
                                 alt="{{ __('ui.company_logo') }}"
                                 class="h-16 w-auto max-w-full object-contain mb-3 rounded border border-gray-200 bg-white p-2">
                        @endif

                        <input type="file" name="company_logo" accept="image/png,image/jpeg,image/webp"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <p class="mt-1 text-sm text-gray-500">{{ __('ui.company_logo_help') }}</p>
                        @error('company_logo')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    {{ __('ui.save_settings') }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
