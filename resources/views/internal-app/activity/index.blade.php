@extends('internal-app.layouts.app')
@section('title', 'Aktivitas Karyawan')
@section('subtitle', 'Laporan aktivitas project management per karyawan')

@section('body')
@php $phases = ['Planning','System Analysis','Design','Programming','Testing','Implementation','Maintenance','Reporting','Meeting','Marketing','Training']; @endphp
<div x-data="{ add: false }" class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="ia-card p-4">
            <p class="text-xs font-semibold text-slate-400">Total Aktivitas</p>
            <p class="mt-1 text-2xl font-black text-slate-800">{{ number_format($items->total()) }}</p>
        </div>
        <div class="ia-card p-4">
            <p class="text-xs font-semibold text-slate-400">Karyawan Aktif</p>
            <p class="mt-1 text-2xl font-black text-blue-600">{{ $summary->count() }}</p>
        </div>
        <div class="ia-card p-4 flex flex-col justify-center gap-2">
            <button @click="add = true" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Input Aktivitas</button>
            <a href="{{ route('internal.activities.export', $filters) }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-center text-sm font-semibold text-white"><i class="fa-solid fa-file-excel mr-1"></i>Export Laporan</a>
        </div>
    </div>

    <form method="get" class="ia-card flex flex-wrap items-end gap-2 p-4">
        <div><label class="text-[11px] font-semibold text-slate-500">Cari</label><input name="q" value="{{ $filters['q'] }}" placeholder="Deskripsi / project / user" class="w-56 rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
        <div><label class="text-[11px] font-semibold text-slate-500">Karyawan</label>
            <select name="user_id" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'users']) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach($users as $u)<option value="{{ $u->id }}" @selected($filters['userId']==$u->id)>{{ $u->nama }}</option>@endforeach
            </select>
        </div>
        <div><label class="text-[11px] font-semibold text-slate-500">Project</label>
            <select name="project_id" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'projects']) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Semua</option>
                @foreach($projects as $p)<option value="{{ $p->id }}" @selected($filters['projectId']==$p->id)>{{ $p->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="text-[11px] font-semibold text-slate-500">Dari</label><input type="date" name="from" value="{{ $filters['from'] }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
        <div><label class="text-[11px] font-semibold text-slate-500">Sampai</label><input type="date" name="to" value="{{ $filters['to'] }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm"></div>
        <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Terapkan</button>
    </form>

    <div class="ia-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3">Karyawan</th><th class="px-4 py-3">Project</th><th class="px-4 py-3">Tipe</th><th class="px-4 py-3">Aksi</th><th class="px-4 py-3">Deskripsi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $a)
                    <tr>
                        <td class="px-4 py-2 text-xs whitespace-nowrap">{{ $a->activity_date }}</td>
                        <td class="px-4 py-2 font-medium">{{ $a->nama }}</td>
                        <td class="px-4 py-2 text-xs">{{ $a->project_name ?: '-' }}</td>
                        <td class="px-4 py-2 text-xs"><span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $a->type }}</span></td>
                        <td class="px-4 py-2 text-xs">{{ $a->action }}</td>
                        <td class="px-4 py-2 text-xs text-slate-600">{{ $a->description }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada aktivitas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>

    <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="add=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Input Aktivitas Karyawan</h3><button @click="add=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return saveActivityAdmin(event)">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-slate-500">Karyawan</label>
                    <select name="user_id" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'users']) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                        @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->nama }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-slate-500">Project</label>
                    <select name="project_id" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'projects']) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                        <option value="">— Pilih Project —</option>
                        @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold text-slate-500">Task Activity</label><input name="title" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required></div>
                <div><label class="text-xs font-semibold text-slate-500">Phase</label><select name="phase" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">@foreach($phases as $ph)<option value="{{ $ph }}">{{ $ph }}</option>@endforeach</select></div>
                <div><label class="text-xs font-semibold text-slate-500">Priority</label><select name="priority" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option><option value="urgent">Urgent</option></select></div>
                <div><label class="text-xs font-semibold text-slate-500">Tanggal Aktivitas</label><input type="date" name="activity_date" value="{{ date('Y-m-d') }}" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                <div><label class="text-xs font-semibold text-slate-500">Due Date</label><input type="date" name="due_date" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></div>
                <div><label class="text-xs font-semibold text-slate-500">Status</label><select name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"><option value="backlog">Not Started</option><option value="in_progress" selected>Ongoing</option><option value="in_review">Review</option><option value="done">Done</option></select></div>
                <div class="sm:col-span-2"><label class="text-xs font-semibold text-slate-500">Keterangan</label><textarea name="description" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm"></textarea></div>
                <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Aktivitas</button>
            </form>
        </div>
    </div>
</div>

<script>
async function saveActivityAdmin(e){e.preventDefault();try{const d=await postJson('{{ route('internal.activities.store') }}',Object.fromEntries(new FormData(e.target).entries()));iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
</script>
@endsection
