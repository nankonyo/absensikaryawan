@extends('internal-app.layouts.app')
@section('title', $project->name)
@section('subtitle', 'Detail project, URL & akses')

@section('body')
@php
    $statusColor = [
        'planning' => 'bg-slate-100 text-slate-600',
        'in_progress' => 'bg-blue-50 text-blue-700',
        'completed' => 'bg-emerald-50 text-emerald-700',
        'overdue' => 'bg-red-50 text-red-600',
        'on_hold' => 'bg-amber-50 text-amber-700',
    ][$project->status] ?? 'bg-slate-100 text-slate-600';
    $accountTypeLabel = ['gmail' => 'Akun Gmail', 'hosting' => '3rd Party Hosting', 'other' => 'Lainnya'][$project->account_type] ?? '-';
@endphp
<div x-data="{edit:false, showPwd:false, pwd:'', loadingPwd:false, copied:false}" class="space-y-5">

    <div class="ia-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $statusColor }}">{{ strtoupper(str_replace('_', ' ', $project->status)) }}</span>
                    <span class="text-[11px] text-slate-400">{{ $project->code ?: 'Tanpa kode' }}</span>
                </div>
                <h1 class="mt-2 text-2xl font-black text-slate-800">{{ $project->name }}</h1>
                <p class="mt-1 text-xs text-slate-500">Owner: {{ $project->owner_name ?: '-' }} · {{ $project->start_date ?: '-' }} s/d {{ $project->end_date ?: '-' }}</p>
                @if($project->daerah || $project->instansi)
                <p class="mt-1 text-xs text-slate-500"><i class="fa-solid fa-location-dot mr-1 text-slate-400"></i>{{ $project->daerah ?: '-' }} · {{ $project->instansi ?: '-' }}</p>
                @endif
                @if($project->description)<p class="mt-2 max-w-3xl text-sm text-slate-600">{{ $project->description }}</p>@endif
            </div>
            <div class="flex flex-wrap gap-2">
                <button @click="edit=true" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white"><i class="fa-solid fa-pen mr-1"></i>Edit</button>
                <a href="{{ route('internal.projects.board', ['project_id'=>$project->id]) }}" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">Kanban</a>
                <a href="{{ route('internal.projects.gantt', ['project_id'=>$project->id]) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Gantt</a>
            </div>
        </div>
        <div class="mt-4">
            <div class="flex items-center justify-between text-[11px] text-slate-400"><span>Progress</span><span class="font-bold text-slate-600">{{ $project->progress }}%</span></div>
            <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-2 rounded-full bg-blue-600" style="width:{{ $project->progress }}%"></div></div>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800"><i class="fa-solid fa-link mr-1 text-slate-400"></i>URL Domain</h2>
            <div class="mt-3 space-y-3 text-sm">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Production</p>
                    @if($project->production_url)
                    <a href="{{ $project->production_url }}" target="_blank" rel="noopener" class="break-all font-medium text-blue-600 hover:underline">{{ $project->production_url }}</a>
                    @else <p class="text-slate-400">Belum diisi</p> @endif
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Development / Staging</p>
                    @if($project->development_url)
                    <a href="{{ $project->development_url }}" target="_blank" rel="noopener" class="break-all font-medium text-blue-600 hover:underline">{{ $project->development_url }}</a>
                    @else <p class="text-slate-400">Belum diisi</p> @endif
                </div>
            </div>
        </div>

        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800"><i class="fa-solid fa-key mr-1 text-slate-400"></i>Akses &amp; Credential</h2>
            <div class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                <div><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Hosting / Provider</p><p class="font-medium text-slate-700">{{ $project->hosting_provider ?: '-' }}</p></div>
                <div><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Tipe Akun</p><p class="font-medium text-slate-700">{{ $accountTypeLabel }}</p></div>
                <div><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Email Akun</p><p class="break-all font-medium text-slate-700">{{ $project->account_email ?: '-' }}</p></div>
                <div><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Username</p><p class="break-all font-medium text-slate-700">{{ $project->account_username ?: '-' }}</p></div>
                <div class="sm:col-span-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Password</p>
                    @if($hasCredential)
                    <div class="mt-1 flex items-center gap-2">
                        <code class="rounded-lg bg-slate-100 px-2 py-1 text-xs text-slate-700" x-text="showPwd ? (pwd || '••••••••') : '••••••••'"></code>
                        <button type="button" @click="if(!showPwd){ if(!pwd){ loadingPwd=true; fetch('{{ route('internal.projects.credential', ['id'=>$project->id]) }}',{headers:{'Accept':'application/json'}}).then(r=>r.json()).then(d=>{pwd=d.password||'';loadingPwd=false;showPwd=true;}).catch(()=>{loadingPwd=false;iaToast('Gagal mengambil password',false);}); } else { showPwd=true; } } else { showPwd=false; }" class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-600">
                            <i class="fa-solid fa-eye" x-show="!showPwd"></i><i class="fa-solid fa-eye-slash" x-show="showPwd" x-cloak></i>
                            <span x-show="!showPwd">Lihat</span><span x-show="showPwd" x-cloak>Sembunyikan</span>
                        </button>
                        <button type="button" @click="if(pwd){ navigator.clipboard.writeText(pwd); copied=true; setTimeout(()=>copied=false,1500); } else { iaToast('Klik Lihat dulu', false); }" class="rounded-lg border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-600"><i class="fa-solid fa-copy"></i> <span x-show="!copied">Salin</span><span x-show="copied" x-cloak>Tersalin</span></button>
                    </div>
                    @else
                    <p class="text-slate-400">Belum ada password tersimpan</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($project->notes)
    <div class="ia-card p-5">
        <h2 class="font-bold text-slate-800"><i class="fa-solid fa-note-sticky mr-1 text-slate-400"></i>Catatan Project</h2>
        <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $project->notes }}</p>
    </div>
    @endif

    <div class="ia-card p-5">
        <h2 class="font-bold text-slate-800"><i class="fa-solid fa-clock-rotate-left mr-1 text-slate-400"></i>Status Terakhir Project</h2>
        @if($project->last_status)
        <div class="mt-2 rounded-xl border border-blue-100 bg-blue-50/50 p-3">
            <p class="whitespace-pre-line text-sm text-slate-700">{{ $project->last_status }}</p>
            <p class="mt-1 text-[11px] text-slate-400">Diperbarui {{ $project->last_status_at ?: '-' }} oleh {{ $lastStatusBy ?: '-' }}</p>
        </div>
        @else
        <p class="mt-2 text-sm text-slate-400">Belum ada status. Tambahkan catatan di bawah — status bisa juga otomatis dari aktivitas project management.</p>
        @endif

        <form class="mt-3 flex flex-col gap-2 sm:flex-row" onsubmit="return saveProjectNote(event)">
            <textarea name="note" rows="2" placeholder="Tulis catatan / status terbaru project..." class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm" required></textarea>
            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Simpan Status</button>
        </form>

        @if($notes->count())
        <div class="mt-4 max-h-72 space-y-2 overflow-y-auto">
            @foreach($notes as $n)
            <div class="rounded-xl border border-slate-100 p-2">
                <p class="text-sm text-slate-700"><span class="font-semibold">{{ $n->nama }}</span> — {{ $n->description }}</p>
                <p class="text-[11px] text-slate-400">{{ strtoupper($n->type) }} · {{ $n->activity_date }} · {{ $n->created_at }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800">Tim ({{ $members->count() }})</h2>
            <div class="mt-3 space-y-2">
                @forelse($members as $m)
                <div class="flex items-center gap-2 rounded-xl border border-slate-100 p-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">{{ strtoupper(substr($m->nama, 0, 1)) }}</span>
                    <div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-700">{{ $m->nama }}</p><p class="text-[11px] text-slate-400">{{ $m->role }}</p></div>
                </div>
                @empty
                <p class="py-4 text-center text-sm text-slate-400">Belum ada anggota.</p>
                @endforelse
            </div>
        </div>

        <div class="ia-card p-5 xl:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-800">Task ({{ $tasks->count() }})</h2>
                <a href="{{ route('internal.projects.board', ['project_id'=>$project->id]) }}" class="text-xs font-semibold text-blue-600">Buka Kanban</a>
            </div>
            <div class="mt-3 space-y-2">
                @forelse($tasks as $t)
                <a href="{{ route('internal.projects.tasks.show', $t->id) }}" class="flex items-center justify-between gap-3 rounded-xl border border-slate-100 p-3 hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $t->title }}</p>
                        <p class="text-[11px] text-slate-400">{{ $t->assignee_name }} · {{ str_replace('_', ' ', $t->status) }}</p>
                    </div>
                    <span class="text-xs font-bold text-slate-500">{{ $t->progress }}%</span>
                </a>
                @empty
                <p class="py-6 text-center text-sm text-slate-400">Belum ada task pada project ini.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="ia-card p-5">
        <h2 class="font-bold text-slate-800">Aktivitas Terbaru</h2>
        <div class="mt-3 space-y-2">
            @forelse($logs as $l)
            <div class="flex items-start gap-3 rounded-xl border border-slate-100 p-3">
                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-blue-500"></span>
                <div class="min-w-0">
                    <p class="text-sm text-slate-700"><span class="font-semibold">{{ $l->user_name ?: 'System' }}</span> — {{ $l->description }}</p>
                    <p class="text-[11px] text-slate-400">{{ $l->task_title }} · {{ $l->created_at }}</p>
                </div>
            </div>
            @empty
            <p class="py-6 text-center text-sm text-slate-400">Belum ada aktivitas.</p>
            @endforelse
        </div>
    </div>

    <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Edit Project</h3><button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return saveProject(event)">
                <input name="name" value="{{ $project->name }}" placeholder="Nama project" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2" required>
                <select name="status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    @foreach(['planning'=>'Planning','in_progress'=>'In Progress','completed'=>'Completed','overdue'=>'Overdue','on_hold'=>'On Hold'] as $val=>$label)
                    <option value="{{ $val }}" @selected($project->status===$val)>{{ $label }}</option>
                    @endforeach
                </select>
                <input name="color" type="color" value="{{ $project->color ?: '#2563eb' }}" class="h-10 rounded-xl border border-slate-200 px-2 py-1">
                <input name="start_date" type="date" value="{{ $project->start_date }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="end_date" type="date" value="{{ $project->end_date }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <textarea name="description" rows="2" placeholder="Deskripsi" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">{{ $project->description }}</textarea>
                <input name="daerah" value="{{ $project->daerah }}" placeholder="Daerah" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="instansi" value="{{ $project->instansi }}" placeholder="Instansi / Dinas / OPD" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">

                <div class="sm:col-span-2 mt-1 border-t border-slate-100 pt-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">URL &amp; Akses</div>
                <input name="production_url" value="{{ $project->production_url }}" placeholder="URL Production" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                <input name="development_url" value="{{ $project->development_url }}" placeholder="URL Development / Staging" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                <input name="hosting_provider" value="{{ $project->hosting_provider }}" placeholder="Hosting / Provider" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">
                <select name="account_type" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="gmail" @selected($project->account_type==='gmail')>Akun Gmail</option>
                    <option value="hosting" @selected($project->account_type==='hosting')>3rd Party Hosting</option>
                    <option value="other" @selected($project->account_type==='other')>Lainnya</option>
                </select>
                <input name="account_email" type="email" value="{{ $project->account_email }}" placeholder="Email akun" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="account_username" value="{{ $project->account_username }}" placeholder="Username akun" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <input name="account_password" type="password" placeholder="Password baru (kosongkan jika tidak diubah)" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <textarea name="notes" rows="3" placeholder="Catatan project / credential tambahan" class="rounded-xl border border-slate-200 px-3 py-2 text-sm sm:col-span-2">{{ $project->notes }}</textarea>

                <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script>
async function saveProject(e){
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    try {
        const d = await postJson('{{ route('internal.projects.update', ['id'=>$project->id]) }}', payload);
        iaToast(d.message);
        setTimeout(()=>iaReload(), 500);
    } catch (err) { iaToast(err.message, false); }
    return false;
}
async function saveProjectNote(e){
    e.preventDefault();
    try {
        const d = await postJson('{{ route('internal.projects.note', ['id'=>$project->id]) }}', Object.fromEntries(new FormData(e.target).entries()));
        iaToast(d.message);
        setTimeout(()=>iaReload(), 500);
    } catch (err) { iaToast(err.message, false); }
    return false;
}
</script>
@endsection
