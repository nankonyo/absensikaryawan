<x-app-layout>
    <x-slot name="header">{{ __('ui.attendance_details') }}</x-slot>

    <x-attendance-detail
        :attendance="$attendance"
        :back-url="route('admin.attendances.index')"
        :back-label="__('ui.back_to_attendances')"
    />
</x-app-layout>
