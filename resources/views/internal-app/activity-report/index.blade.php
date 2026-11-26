@extends('internal-app.layouts.app')
@section('title', 'Laporan Activity')
@section('subtitle', 'Format laporan mengikuti ACTIVITY DIVISI PROJECT RAJA SAKTI TELEMATIKA 2026')

@section('body')
@php
    $exportQuery = [
        'mode' => $meta['mode'],
        'user_id' => $meta['user_id'],
        'divisi' => $meta['team'],
        'period' => $meta['period'],
        'month' => $meta['month'],
        'year' => $meta['year'],
        'week' => $meta['from'],
    ];
@endphp
<div x-data="{ mode: '{{ $meta['mode'] }}', period: '{{ $meta['period'] }}' }" class="space-y-5">
    <form method="get" class="ia-card flex flex-wrap items-end gap-2 p-4">
        <div>
            <label class="text-[11px] font-semibold text-slate-500">Jenis Laporan</label>
            <select name="mode" x-model="mode" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="employee">Per Karyawan</option>
                <option value="division">Per Divisi</option>
            </select>
        </div>
        <div x-show="mode === 'employee'">
            <label class="text-[11px] font-semibold text-slate-500">Karyawan</label>
            <select name="user_id" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'users']) }}" class="w-56 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                @foreach($users as $u)
                <option value="{{ $u->id }}" @selected((int)request('user_id') === (int)$u->id)>{{ $u->nama }}</option>
                @endforeach
            </select>
        </div>
        <div x-show="mode === 'division'" x-cloak>
            <label class="text-[11px] font-semibold text-slate-500">Divisi</label>
            <select name="divisi" class="w-56 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                @foreach($divisions as $d)
                <option value="{{ $d }}" @selected($meta['team'] === $d)>{{ $d }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-semibold text-slate-500">Periode</label>
            <select name="period" x-model="period" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="yearly">Tahunan</option>
                <option value="monthly">Bulanan</option>
                <option value="weekly">Mingguan</option>
            </select>
        </div>
        <div x-show="period === 'yearly'" x-cloak>
            <label class="text-[11px] font-semibold text-slate-500">Tahun</label>
            <input type="number" name="year" min="2000" max="2100" value="{{ $meta['year'] }}" class="w-28 rounded-lg border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div x-show="period === 'monthly'" x-cloak>
            <label class="text-[11px] font-semibold text-slate-500">Bulan</label>
            <input type="month" name="month" value="{{ $meta['month'] }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
        </div>
        <div x-show="period === 'weekly'" x-cloak>
            <label class="text-[11px] font-semibold text-slate-500">Minggu (pilih tanggal)</label>
            <input type="date" name="week" value="{{ $meta['from'] }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
        </div>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Tampilkan</button>
        <a href="{{ route('internal.activity-report.export', $exportQuery) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-file-excel mr-1"></i>Export Excel</a>
    </form>

    <div class="ia-card p-4">
        <div class="grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><span class="text-xs font-semibold text-slate-500">Name</span><p class="font-semibold text-slate-800">{{ $meta['name'] }}</p></div>
            <div><span class="text-xs font-semibold text-slate-500">Team</span><p class="font-semibold text-slate-800">{{ $meta['team'] }}</p></div>
            <div><span class="text-xs font-semibold text-slate-500">Periode</span><p class="font-semibold text-slate-800">{{ $meta['period_label'] }}</p></div>
            <div><span class="text-xs font-semibold text-slate-500">Done</span><p class="font-semibold text-emerald-600">{{ $meta['done'] }} / {{ count($rows) }}</p></div>
        </div>
    </div>

    <div class="ia-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-blue-50 uppercase text-slate-600">
                    <tr>
                        <th class="px-3 py-3">No.</th>
                        <th class="px-3 py-3">Task Activity</th>
                        <th class="px-3 py-3">Activity</th>
                        <th class="px-3 py-3">Phase</th>
                        <th class="px-3 py-3">Assignee</th>
                        <th class="px-3 py-3">Priority</th>
                        <th class="px-3 py-3">Due Date</th>
                        <th class="px-3 py-3">Days Left</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Done</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $i => $r)
                    <tr>
                        <td class="px-3 py-2 text-center">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 font-medium text-slate-700">{{ $r['task_activity'] }}</td>
                        <td class="px-3 py-2">{{ $r['activity'] }}</td>
                        <td class="px-3 py-2">{{ $r['phase'] }}</td>
                        <td class="px-3 py-2">{{ $r['assignee'] }}</td>
                        <td class="px-3 py-2">{{ ucwords(str_replace('_', ' ', $r['priority'])) }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $r['due_date'] }}</td>
                        <td class="px-3 py-2 whitespace-nowrap">{{ $r['days_left'] }}</td>
                        <td class="px-3 py-2">{{ ucwords(str_replace('_', ' ', $r['status'])) }}</td>
                        <td class="px-3 py-2 text-center">{{ $r['done'] ? '✓' : '0' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="px-3 py-10 text-center text-slate-400">Tidak ada aktivitas pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
