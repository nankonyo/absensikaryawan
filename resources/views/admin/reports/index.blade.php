<x-app-layout>
    <x-slot name="header">{{ __('ui.reports') }}</x-slot>

    @php
        $headers = \App\Exports\AttendanceReportExport::HEADERS;
        $notes = \App\Exports\AttendanceReportExport::NOTES;
    @endphp

    <div class="space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">{{ __('ui.attendance_report') }}</h2>
            <p class="text-sm text-gray-500">{{ __('ui.attendance_report_desc') }}</p>
        </div>

        <div class="bg-white rounded-lg shadow p-4 sm:p-6" x-data="{ type: @js($filterType) }">
            <form action="{{ route('admin.reports.index') }}" method="GET"
                  class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
                <div class="lg:col-span-3">
                    <label for="filter_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.filter_type') }}</label>
                    <select id="filter_type" name="filter_type" x-model="type"
                            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                        <option value="month">{{ __('ui.per_month') }}</option>
                        <option value="range">{{ __('ui.between_dates') }}</option>
                        <option value="semester">{{ __('ui.per_semester') }}</option>
                        <option value="year">{{ __('ui.per_year') }}</option>
                    </select>
                </div>

                <div class="lg:col-span-3" x-show="type === 'month'" x-cloak>
                    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.month') }}</label>
                    <input id="month" type="month" name="month" value="{{ $month }}" :disabled="type !== 'month'"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                </div>

                <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="type === 'semester'" x-cloak>
                    <div>
                        <label for="semester_year" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.year') }}</label>
                        <input id="semester_year" type="number" min="2000" max="2100" name="year" value="{{ $year }}" :disabled="type !== 'semester'"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.semester') }}</label>
                        <select id="semester" name="semester" :disabled="type !== 'semester'"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                            <option value="1" @selected($semester === 1)>{{ __('ui.semester_1') }}</option>
                            <option value="2" @selected($semester === 2)>{{ __('ui.semester_2') }}</option>
                        </select>
                    </div>
                </div>

                <div class="lg:col-span-3" x-show="type === 'year'" x-cloak>
                    <label for="report_year" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.year') }}</label>
                    <input id="report_year" type="number" min="2000" max="2100" name="year" value="{{ $year }}" :disabled="type !== 'year'"
                           class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                </div>

                <div class="lg:col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="type === 'range'" x-cloak>
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.start_date') }}</label>
                        <input id="start_date" type="date" name="start_date" value="{{ $startDate }}" :disabled="type !== 'range'"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">{{ __('ui.end_date') }}</label>
                        <input id="end_date" type="date" name="end_date" value="{{ $endDate }}" :disabled="type !== 'range'"
                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg">
                    </div>
                </div>

                <div class="lg:col-span-12 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-1">
                    <p class="text-xs text-gray-500">
                        {{ __('ui.date_range') }}: {{ $startDate }} s.d. {{ $endDate }}
                    </p>
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 12h12M10 17h4"></path>
                        </svg>
                        {{ __('ui.show_report') }}
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ __('ui.attendance_report') }}</h3>
                    <p class="text-xs text-gray-500">{{ $startDate }} s.d. {{ $endDate }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.reports.attendance.download', ['start_date' => $startDate, 'end_date' => $endDate, 'format' => 'pdf']) }}"
                       data-no-spa
                       class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-blue-600 text-blue-700 hover:bg-blue-50 text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ __('ui.download_pdf') }}
                    </a>
                    <a href="{{ route('admin.reports.attendance.download', ['start_date' => $startDate, 'end_date' => $endDate, 'format' => 'excel']) }}"
                       data-no-spa
                       class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ __('ui.download_excel') }}
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-[11px] leading-tight">
                    <thead>
                        <tr class="bg-[#44546A] text-white">
                            @foreach ($headers as $index => $header)
                                <th class="px-2 py-2 border border-gray-300 font-bold text-center align-middle whitespace-nowrap
                                    @if ($index === 8) bg-[#FFC000] @elseif ($index === 9) bg-[#70AD47] @elseif ($index === 10) bg-[#FFC000] @endif">
                                    {{ $header }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($report['rows'] as $index => $row)
                            @php
                                $riil = $row['normal_work_days']
                                    - $row['absent']
                                    - $row['permit']
                                    - $row['sick_leave']
                                    - $row['collective_leave']
                                    - $row['leave']
                                    - $row['national_holiday']
                                    - $row['company_holiday'];

                                $cells = [
                                    $row['employee_id'] ?: $index + 1,
                                    mb_strtoupper($row['name']),
                                    $row['normal_work_days'],
                                    $riil,
                                    $row['absent'],
                                    $row['late_hours'],
                                    $row['duty_city'],
                                    $row['wfh'],
                                    $row['permit'],
                                    $row['leave'],
                                    $row['sick_leave'],
                                    $row['compensation_hours'],
                                    $row['collective_leave'],
                                    $row['company_holiday'],
                                    $row['national_holiday'],
                                    $row['training'],
                                    $row['duty_out_of_town'],
                                    '',
                                    $row['present_days'],
                                    $row['attendance_percentage'].'%',
                                    $row['attendance_grade'],
                                ];

                                $cellClasses = [2 => 'bg-[#FFC000]', 3 => 'bg-[#FFFF66]', 7 => 'bg-[#9DC3E6]'];
                            @endphp

                            <tr class="hover:bg-gray-50">
                                @foreach ($cells as $cellIndex => $value)
                                    <td class="px-2 py-1.5 border border-gray-300 text-center align-middle whitespace-nowrap {{ $cellClasses[$cellIndex] ?? '' }} {{ in_array($cellIndex, [1, 17], true) ? 'text-left' : '' }}">
                                        {{ $value }}
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="px-4 py-6 text-center text-sm text-gray-500">
                                    {{ __('ui.no_attendance_period') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td colspan="2" class="px-2 py-2 border border-gray-300 text-left">TOTAL</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['normal_work_days'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['actual_work_days'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['absent'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['late_hours'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['duty_city'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['wfh'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['permit'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['leave'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['sick_leave'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['compensation_hours'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['collective_leave'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['company_holiday'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['national_holiday'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['training'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['duty_out_of_town'] }}</td>
                            <td class="px-2 py-2 border border-gray-300"></td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['totals']['present_days'] }}</td>
                            <td class="px-2 py-2 border border-gray-300 text-center">{{ $summary['attendance_percentage'] }}%</td>
                            <td class="px-2 py-2 border border-gray-300 text-center whitespace-nowrap">{{ $summary['attendance_grade'] }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="border-t border-gray-200 px-4 sm:px-6 py-4">
                <div class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-900">
                    <span class="font-semibold">Rekap Kehadiran:</span>
                    Total Hari Hadir {{ $summary['total_present_days'] }} / Hari Kerja {{ $summary['total_normal_work_days'] }}
                    = <span class="font-bold">{{ $summary['attendance_percentage'] }}%</span>
                    — Penilaian: <span class="font-bold">{{ $summary['attendance_grade'] }}</span>
                </div>
                <div class="ml-auto max-w-xl space-y-1 text-[11px] font-bold text-gray-700">
                    @foreach ($notes as $note)
                        <p>{{ $note }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
