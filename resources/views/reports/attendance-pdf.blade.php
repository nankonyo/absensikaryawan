<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('ui.attendance_report') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .meta {
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .status-present { color: green; }
        .status-late { color: orange; }
        .status-absent { color: red; }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>{{ __('ui.attendance_report') }}</p>
    </div>

    <div class="meta">
        <strong>{{ __('ui.period') }}:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}<br>
        <strong>{{ __('ui.generated') }}:</strong> {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('ui.date') }}</th>
                <th>{{ __('ui.employee') }}</th>
                <th>{{ __('ui.check_in') }}</th>
                <th>{{ __('ui.check_out') }}</th>
                <th>{{ __('ui.status') }}</th>
                <th>{{ __('ui.statuses.late') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($attendances as $attendance)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($attendance->date)->format('d/m/Y') }}</td>
                    <td>{{ $attendance->user->name }}</td>
                    <td>{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '-' }}</td>
                    <td>{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '-' }}</td>
                    <td>
                        @if($attendance->status === 'present')
                            <span class="status-present">{{ __('ui.statuses.present') }}</span>
                        @elseif($attendance->status === 'late')
                            <span class="status-late">{{ __('ui.statuses.late') }}</span>
                        @else
                            <span class="status-absent">{{ __('ui.statuses.'.$attendance->status) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($attendance->late_duration > 0)
                            {{ $attendance->lateDurationLabel() }}
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center;">{{ __('ui.no_attendance_period') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        {{ __('ui.generated_by_system', ['app' => config('app.name')]) }}
    </div>
</body>
</html>
