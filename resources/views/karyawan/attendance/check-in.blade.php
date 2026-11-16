<x-app-layout>
    <x-slot name="header">{{ __('ui.check_in') }}</x-slot>

    <x-attendance-camera
        :action="route('karyawan.attendance.check-in.submit')"
        accent="green"
        :label="__('ui.check_in_badge')"
    />
</x-app-layout>
