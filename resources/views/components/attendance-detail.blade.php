@props(['attendance', 'backUrl', 'backLabel'])

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ $backUrl }}" class="text-blue-600 hover:underline">{{ $backLabel }}</a>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('ui.attendance_details') }}</h2>

        <!-- Employee Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 pb-6 border-b border-gray-200">
            <div>
                <p class="text-sm text-gray-500">{{ __('ui.employee') }}</p>
                <p class="text-gray-900 font-medium">{{ $attendance->user->name }}</p>
                <p class="text-xs text-gray-500">{{ $attendance->user->employee_id }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">{{ __('ui.position') }}</p>
                <p class="text-gray-900 font-medium">{{ $attendance->user->position }}</p>
            </div>
        </div>

        <!-- Attendance Info -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <p class="text-sm text-gray-500">{{ __('ui.date') }}</p>
                <p class="text-gray-900 font-medium">{{ $attendance->date->format('d/m/Y') }}</p>
            </div>

            <div>
                <p class="text-sm text-gray-500">{{ __('ui.status') }}</p>
                @if($attendance->status !== 'valid')
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ $attendance->statusLabel() }}
                    </span>
                @elseif($attendance->late_duration > 0)
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ $attendance->lateDurationLabel() }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                        {{ __('ui.on_time') }}
                    </span>
                @endif
            </div>

            @if($attendance->attendanceReason)
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.attendance_reason') }}</p>
                    <p class="text-gray-900 font-medium">{{ $attendance->attendanceReason->name }}</p>
                    @if($attendance->notes)
                        <p class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ $attendance->notes }}</p>
                    @endif
                </div>
            @endif

            @if($attendance->verified_at)
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.verified_by') }}</p>
                    <p class="text-gray-900 font-medium">{{ $attendance->verifier?->name ?? '-' }}</p>
                    <p class="text-xs text-gray-500">{{ $attendance->verified_at->format('d/m/Y H:i') }}</p>
                </div>
            @endif

            @if($attendance->verification_notes)
                <div>
                    <p class="text-sm text-gray-500">{{ __('ui.notes') }}</p>
                    <p class="text-gray-900">{{ $attendance->verification_notes }}</p>
                </div>
            @endif

            <div>
                <p class="text-sm text-gray-500">{{ __('ui.check_in') }}</p>
                <p class="text-gray-900 font-medium">
                    {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') : '-' }}
                </p>
            </div>

            <div>
                <p class="text-sm text-gray-500">{{ __('ui.check_out') }}</p>
                <p class="text-gray-900 font-medium">
                    {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') : '-' }}
                </p>
            </div>
        </div>

        @if($attendance->check_in_photo || ($attendance->check_in_latitude && $attendance->check_in_longitude))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-500 mb-2">{{ __('ui.check_in_selfie') }}</p>
                    @if($attendance->check_in_photo)
                        <img src="{{ Storage::disk('public')->url($attendance->check_in_photo) }}" alt="{{ __('ui.check_in_selfie') }}"
                             class="w-full h-64 object-cover rounded-lg">
                    @else
                        <div class="w-full h-64 rounded-lg bg-gray-100 flex items-center justify-center text-sm text-gray-500">
                            {{ __('ui.empty') }}
                        </div>
                    @endif
                </div>

                @if($attendance->check_in_latitude && $attendance->check_in_longitude)
                    <x-location-map :latitude="$attendance->check_in_latitude"
                                    :longitude="$attendance->check_in_longitude"
                                    :label="__('ui.check_in_location')" />
                @endif
            </div>
        @endif

        @if($attendance->check_out_photo || ($attendance->check_out_latitude && $attendance->check_out_longitude))
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <p class="text-sm text-gray-500 mb-2">{{ __('ui.check_out_selfie') }}</p>
                    @if($attendance->check_out_photo)
                        <img src="{{ Storage::disk('public')->url($attendance->check_out_photo) }}" alt="{{ __('ui.check_out_selfie') }}"
                             class="w-full h-64 object-cover rounded-lg">
                    @else
                        <div class="w-full h-64 rounded-lg bg-gray-100 flex items-center justify-center text-sm text-gray-500">
                            {{ __('ui.empty') }}
                        </div>
                    @endif
                </div>

                @if($attendance->check_out_latitude && $attendance->check_out_longitude)
                    <x-location-map :latitude="$attendance->check_out_latitude"
                                    :longitude="$attendance->check_out_longitude"
                                    :label="__('ui.check_out_location')" />
                @endif
            </div>
        @endif
    </div>
</div>
