<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('ui.attendance_report') }}</title>
    <style>
        @page {
            margin: 18px 16px 22px 16px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
            color: #000;
        }

        .title {
            width: 100%;
            padding: 4px 0;
            border: 1px solid #000;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        .period {
            margin: 3px 0 5px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.report thead {
            display: table-header-group;
        }

        table.report tr {
            page-break-inside: avoid;
        }

        table.report th,
        table.report td {
            padding: 2px 1px;
            border: 1px solid #000;
            font-size: 6.5px;
            line-height: 1.15;
            vertical-align: middle;
        }

        table.report th {
            background-color: #44546A;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            word-wrap: break-word;
        }

        table.report td {
            text-align: center;
            word-wrap: break-word;
        }

        table.report td.text-left {
            text-align: left;
        }

        table.report td.fill-c {
            background-color: #FFC000;
        }

        table.report td.fill-d {
            background-color: #FFFF66;
        }

        table.report td.fill-h {
            background-color: #9DC3E6;
        }

        .empty-row {
            text-align: center;
            font-style: italic;
        }

        .notes {
            float: right;
            width: 30%;
            margin-top: 5px;
            font-size: 6.5px;
            line-height: 1.25;
        }

        .notes p {
            margin: 0 0 2px;
            font-weight: bold;
        }

        .clearfix {
            clear: both;
        }
    </style>
</head>
<body>
    @php
        $title = \App\Exports\AttendanceReportExport::titleText();
        $period = \App\Exports\AttendanceReportExport::periodText($report['start_date'], $report['end_date']);
        $headers = \App\Exports\AttendanceReportExport::HEADERS;
        $notes = \App\Exports\AttendanceReportExport::NOTES;
        $widths = [4.06, 8.70, 3.48, 2.61, 4.64, 4.64, 4.64, 2.90, 2.90, 2.90, 3.48, 4.35, 3.77, 5.22, 6.09, 4.64, 4.93, 11.02, 5.00, 4.00, 6.00];
        $headerColors = [8 => '#FFC000', 9 => '#70AD47', 10 => '#FFC000'];
    @endphp

    <div class="title">{{ $title }}</div>
    <div class="period">{{ $period }}</div>

    <table class="report">
        <colgroup>
            @foreach ($widths as $width)
                <col style="width: {{ $width }}%;">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                @foreach ($headers as $index => $header)
                    <th @if (isset($headerColors[$index])) style="background-color: {{ $headerColors[$index] }};" @endif>{{ $header }}</th>
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

                    $cellClasses = [2 => 'fill-c', 3 => 'fill-d', 7 => 'fill-h'];
                @endphp
                <tr>
                    @foreach ($cells as $cellIndex => $value)
                        <td class="{{ ($cellIndex === 1 || $cellIndex === 17) ? 'text-left' : '' }} {{ $cellClasses[$cellIndex] ?? '' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="empty-row">{{ __('ui.no_attendance_period') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="notes">
        @foreach ($notes as $note)
            <p>{{ $note }}</p>
        @endforeach
    </div>
    <div class="clearfix"></div>
</body>
</html>
