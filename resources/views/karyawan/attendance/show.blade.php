<x-app-layout>
    <x-slot name="header">{{ __('ui.attendance_details') }}</x-slot>

    <x-attendance-detail
        :attendance="$attendance"
        :back-url="route('karyawan.attendance.history')"
        :back-label="__('ui.back_to_attendance_history')"
    />
</x-app-layout>
