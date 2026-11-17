<x-app-layout>
    <x-slot name="header">{{ __('ui.attendance') }}</x-slot>
    <div class="max-w-4xl mx-auto">
        <!-- Today's Status Card -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-gray-900">{{ __('ui.todays_attendance') }}</h2>
                <span class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                    {{ __('ui.work_mode') }}: {{ auth()->user()->work_mode }}
                </span>
            </div>

            @if($todayAttendance)
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.check_in') }}</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $todayAttendance->check_in ? \Carbon\Carbon::parse($todayAttendance->check_in)->format('H:i') : '-' }}
                        </p>
                        @if($todayAttendance->late_duration > 0)
                            <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                                {{ $todayAttendance->lateDurationLabel() }}
                            </span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">{{ __('ui.check_out') }}</p>
                        <p class="text-2xl font-bold text-gray-900">
                            {{ $todayAttendance->check_out ? \Carbon\Carbon::parse($todayAttendance->check_out)->format('H:i') : '-' }}
                        </p>
                        @if($todayAttendance->work_duration)
                            <p class="text-sm text-gray-500 mt-2">
                                {{ __('ui.work_duration_format', ['hours' => floor($todayAttendance->work_duration / 60), 'minutes' => $todayAttendance->work_duration % 60]) }}
                            </p>
                        @endif
                    </div>
                </div>

                @if($todayAttendance->status !== 'valid')
                    <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-3">
                        <span class="inline-flex items-center rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700">
                            {{ $todayAttendance->statusLabel() }}
                        </span>
                        @if($todayAttendance->attendanceReason)
                            <span class="ml-2 text-sm text-gray-600">{{ $todayAttendance->attendanceReason->name }}</span>
                        @endif
                        @if($todayAttendance->verification_notes)
                            <p class="mt-2 text-xs text-gray-500">{{ $todayAttendance->verification_notes }}</p>
                        @endif
                    </div>
                @endif
            @else
                <p class="text-gray-500">{{ __('ui.no_attendance_today') }}</p>
            @endif

            <!-- Action Buttons -->
            <div class="mt-6 space-y-4">
                <div class="flex gap-4">
                    @if($canCheckIn)
                        <a href="{{ route('karyawan.attendance.check-in') }}"
                           class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-center font-semibold rounded-lg transition">
                            {{ __('ui.check_in') }}
                        </a>
                    @else
                        <span aria-disabled="true"
                              class="flex-1 px-4 py-3 bg-green-600/50 text-white/80 text-center font-semibold rounded-lg cursor-not-allowed">
                            {{ __('ui.check_in') }}
                        </span>
                    @endif

                    @if($canCheckOut)
                        <a href="{{ route('karyawan.attendance.check-out') }}"
                           class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white text-center font-semibold rounded-lg transition">
                            {{ __('ui.check_out') }}
                        </a>
                    @else
                        <span aria-disabled="true"
                              class="flex-1 px-4 py-3 bg-red-600/50 text-white/80 text-center font-semibold rounded-lg cursor-not-allowed">
                            {{ __('ui.check_out') }}
                        </span>
                    @endif
                </div>

                <a href="{{ route('karyawan.attendance.history') }}"
                   class="block px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white text-center font-semibold rounded-lg transition">
                    {{ __('ui.view_history') }}
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
