@extends('internal-app.layouts.app')
@section('title', 'Project Management')
@section('subtitle', 'Dashboard proyek & aktivitas tim')

@section('body')
<div x-data="{modal:false}" class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="ia-card p-5"><p class="text-xs font-semibold text-slate-400">Total Proyek</p><p class="mt-1 text-3xl font-black text-slate-800">{{ $metrics['total'] }}</p></div>
        <div class="ia-card p-5"><p class="text-xs font-semibold text-slate-400">Berjalan</p><p class="mt-1 text-3xl font-black text-blue-600">{{ $metrics['in_progress'] }}</p></div>
        <div class="ia-card p-5"><p class="text-xs font-semibold text-slate-400">Selesai</p><p class="mt-1 text-3xl font-black text-emerald-600">{{ $metrics['completed'] }}</p></div>
        <div class="ia-card p-5"><p class="text-xs font-semibold text-slate-400">Terlambat</p><p class="mt-1 text-3xl font-black text-red-500">{{ $metrics['overdue'] }}</p></div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-bold text-slate-800">Proyek</h2>
        <div class="flex flex-1 flex-wrap items-center justify-end gap-2">
            <form method="GET" action="{{ route('internal.projects.index') }}" class="flex min-w-[240px] flex-1 items-center gap-2 sm:max-w-md">
                <input name="q" value="{{ $search ?? '' }}" placeholder="Cari project, kode, daerah, instansi..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <button @click="modal=true" class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Proyek Baru</button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($projects as $p)
        <div class="ia-card p-5">
            <div class="flex items-start justify-between gap-3">
                <div><span class="rounded-full px-2 py-0.5 text-[10px] font-bold text-white" style="background:{{ $p->color ?: '#D34B52' }}">{{ ucwords(str_replace('_', ' ', $p->status)) }}</span><h3 class="mt-2 text-lg font-bold">{{ $p->name }}</h3><p class="text-xs text-slate-500">{{ $p->code ?: 'Tanpa kode' }} · {{ $p->owner_name ?: '-' }}</p>@if($p->daerah || $p->instansi)<p class="text-[11px] text-slate-400">{{ $p->daerah ?: '-' }} · {{ $p->instansi ?: '-' }}</p>@endif</div>
                <span class="text-xs font-bold text-slate-500">{{ $p->progress }}%</span>
            </div>
            <p class="mt-2 line-clamp-2 min-h-[32px] text-xs text-slate-500">{{ $p->description }}</p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" style="width:{{ $p->progress }}%"></div></div>
            <div class="mt-2 flex items-center justify-between text-[11px] text-slate-400"><span>{{ $p->start_date ?: '-' }} s/d {{ $p->end_date ?: '-' }}</span><span>{{ $p->updated_at }}</span></div>
            <div class="mt-4 grid grid-cols-3 gap-2">
                <a href="{{ route('internal.projects.show', ['id'=>$p->id]) }}" class="rounded-lg border border-slate-200 py-2 text-center text-xs font-semibold text-slate-600">Detail</a>
                <a href="{{ route('internal.projects.board', ['project_id'=>$p->id]) }}" class="rounded-lg bg-slate-900 py-2 text-center text-xs font-semibold text-white">Kanban</a>
                <a href="{{ route('internal.projects.gantt', ['project_id'=>$p->id]) }}" class="rounded-lg border border-slate-200 py-2 text-center text-xs font-semibold text-slate-600">Gantt</a>
            </div>
        </div>
        @empty
        <div class="ia-card p-10 text-center text-sm text-slate-400 md:col-span-2 xl:col-span-3">Belum ada proyek. Klik Proyek Baru.</div>
        @endforelse
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800">Who is Doing What</h2>
            <p class="text-xs text-slate-500">Aktivitas task yang sedang dikerjakan.</p>
            <div class="mt-3 space-y-2">
                @forelse($activeTasks as $t)
                <a href="{{ route('internal.projects.tasks.show', $t->id) }}" class="flex items-center gap-3 rounded-xl border border-slate-100 p-3 hover:bg-slate-50">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 font-bold text-blue-700">{{ strtoupper(substr($t->assignee_name,0,1)) }}</span>
                    <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ $t->assignee_name }}</p><p class="truncate text-xs text-slate-500">{{ $t->title }} · {{ $t->project_name }}</p></div>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $t->status==='in_progress'?'bg-blue-50 text-blue-700':'bg-indigo-50 text-indigo-700' }}">{{ str_replace('_',' ',$t->status) }}</span>
                </a>
                @empty
                <p class="py-6 text-center text-sm text-slate-400">Tidak ada task aktif.</p>
                @endforelse
            </div>
        </div>
        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800">Update Terbaru Task</h2>
            <div class="mt-3 space-y-2">
                @forelse($recentTasks as $t)
                <a href="{{ route('internal.projects.tasks.show', $t->id) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3 hover:bg-slate-50"><div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $t->title }}</p><p class="text-xs text-slate-500">{{ $t->project_name }}</p></div><span class="text-[11px] font-semibold text-slate-400">{{ $t->progress }}%</span></a>
                @empty
                <p class="py-6 text-center text-sm text-slate-400">Belum ada task.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="modal=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Proyek Baru</h3><button @click="modal=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return createProject(event)">
                <input name="name" placeholder="Nama proyek" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
                <input name="code" placeholder="Kode proyek" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm"><option value="planning">Planning</option><option value="in_progress">In Progress</option><option value="on_hold">On Hold</option></select>
                <input type="date" name="start_date" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input type="date" name="end_date" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <textarea name="description" rows="3" placeholder="Deskripsi" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2"></textarea>
                <input name="daerah" placeholder="Daerah (mis. Kab. Mimika / Prov. Papua Selatan)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="instansi" placeholder="Instansi / Dinas / OPD" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">

                <div class="sm:col-span-2 mt-1 border-t border-slate-100 pt-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">URL &amp; Akses Project</div>
                <input name="production_url" placeholder="URL Production (https://...)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                <input name="development_url" placeholder="URL Development / Staging (https://...)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                <input name="hosting_provider" placeholder="Hosting / Provider (Gmail, cPanel, Niagahoster...)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                <select name="account_type" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="gmail">Akun Gmail</option>
                    <option value="hosting">3rd Party Hosting</option>
                    <option value="other">Lainnya</option>
                </select>
                <input name="account_email" type="email" placeholder="Email akun" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="account_username" placeholder="Username akun" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="account_password" type="password" placeholder="Password akun" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <textarea name="notes" rows="3" placeholder="Catatan project / credential tambahan" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2"></textarea>

                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-semibold text-slate-500">Tim Project (multiple)</label>
                    <select name="members[]" multiple class="h-28 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                        @foreach($users as $u)<option value="{{ $u['id'] }}">{{ $u['name'] }}</option>@endforeach
                    </select>
                </div>
                <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Proyek</button>
            </form>
        </div>
    </div>
</div>

<script>
async function createProject(e){
    e.preventDefault();
    const f=e.target, fd=new FormData(f);
    const payload={
        name:fd.get('name'),code:fd.get('code'),status:fd.get('status'),start_date:fd.get('start_date'),end_date:fd.get('end_date'),description:fd.get('description'),daerah:fd.get('daerah'),instansi:fd.get('instansi'),
        production_url:fd.get('production_url'),development_url:fd.get('development_url'),hosting_provider:fd.get('hosting_provider'),
        account_type:fd.get('account_type'),account_email:fd.get('account_email'),account_username:fd.get('account_username'),account_password:fd.get('account_password'),notes:fd.get('notes'),
        members:fd.getAll('members[]').map(Number)
    };
    try{const d=await postJson('{{ route('internal.projects.store') }}',payload);iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}
    return false;
}
</script>
@endsection
