<x-app-layout>
    <x-slot name="header">{{ __('ui.check_out') }}</x-slot>

    <x-attendance-camera
        :action="route('karyawan.attendance.check-out.submit')"
        accent="red"
        :label="__('ui.check_out_badge')"
    />
</x-app-layout>
