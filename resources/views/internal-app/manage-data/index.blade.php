@extends('internal-app.layouts.app')
@section('title', 'Manage Data')
@section('subtitle', 'Seeder dummy, reset, dan import data master')

@section('body')
<div class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Projects</p><p class="mt-1 text-2xl font-black text-slate-800">{{ number_format($stats['projects']) }}</p></div>
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Tasks</p><p class="mt-1 text-2xl font-black text-blue-600">{{ number_format($stats['tasks']) }}</p></div>
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Activity Logs</p><p class="mt-1 text-2xl font-black text-indigo-600">{{ number_format($stats['activity_logs']) }}</p></div>
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Credential</p><p class="mt-1 text-2xl font-black text-emerald-600">{{ number_format($stats['credentials']) }}</p></div>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800">Data Dummy</h2>
            <p class="text-xs text-slate-500">Membuat data simulasi project, task, aktivitas karyawan, absensi, izin, lembur, dan credential bertanda <code>[DUMMY]</code>. Data master/real tidak diubah.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                <button onclick="seedDummy()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-seedling mr-1"></i>Seed Data Dummy</button>
                <button onclick="resetDummy()" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-trash mr-1"></i>Reset Record Data Dummy</button>
            </div>
        </div>

        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800">Import Data Master</h2>
            <div class="mt-3 space-y-2 text-xs">
                <p>Activity Project: <b>{{ is_file($activityFile) ? 'file ditemukan' : 'file tidak ditemukan' }}</b></p>
                <p>Server &amp; Domain: <b>{{ is_file($credFile) ? 'file ditemukan' : 'file tidak ditemukan' }}</b></p>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button onclick="importActivity()" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700"><i class="fa-solid fa-file-import mr-1"></i>Import Activity Project</button>
                <button onclick="importCred()" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700"><i class="fa-solid fa-file-import mr-1"></i>Import Credential</button>
            </div>
            <p class="mt-3 text-[11px] text-red-500">Import credential akan mengganti data credential existing.</p>
        </div>

        <div class="ia-card p-5">
            <h2 class="font-bold text-slate-800">Pengaturan Email</h2>
            <p class="text-xs text-slate-500">Jadwal pengiriman email mingguan sekarang ada di menu <b>Pengaturan &rarr; Pengiriman Email</b>.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="{{ route('admin.settings.index') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Buka Pengaturan</a>
                <a href="{{ route('internal.email-logs.index') }}" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600">Riwayat Email</a>
            </div>
        </div>
    </div>
</div>

<script>
async function seedDummy(){if(!confirm('Buat data dummy?'))return;try{const d=await postJson('{{ route('internal.manage-data.seed-dummy') }}',{});iaToast(d.message);setTimeout(()=>iaReload(),600);}catch(e){iaToast(e.message,false)}}
async function resetDummy(){if(!confirm('Hapus semua record data dummy?'))return;try{const d=await postJson('{{ route('internal.manage-data.reset-dummy') }}',{});iaToast(d.message);setTimeout(()=>iaReload(),600);}catch(e){iaToast(e.message,false)}}
async function importActivity(){if(!confirm('Import Activity Project dari Excel?'))return;try{const d=await postJson('{{ route('internal.manage-data.import-activity') }}',{});iaToast(d.message);setTimeout(()=>iaReload(),800);}catch(e){iaToast(e.message,false)}}
async function importCred(){if(!confirm('Import credential dari Excel? Data credential non-OPD akan diganti.'))return;try{const d=await postJson('{{ route('internal.manage-data.import-credentials') }}',{});iaToast(d.message);setTimeout(()=>iaReload(),800);}catch(e){iaToast(e.message,false)}}

</script>
@endsection
