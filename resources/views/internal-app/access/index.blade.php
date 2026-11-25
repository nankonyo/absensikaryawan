@extends('internal-app.layouts.app')
@section('title', 'Hak Akses')
@section('subtitle', 'Daftar akses dan aksi fitur Internal App (hanya baca)')

@section('body')
@php
    $percent = $actionTotal > 0 ? (int) round($grantedTotal / $actionTotal * 100) : 0;
@endphp
<div class="space-y-5">
    <div class="ia-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <form method="GET" action="{{ route('internal.access.index') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="accessRole" class="text-xs font-semibold text-slate-500">Jabatan / Role</label>
                    <select id="accessRole" name="role" onchange="this.form.submit()" class="mt-1 block w-72 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <option value="">Pilih jabatan/role...</option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" @selected($selectedRoleId === (int) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                    <i class="fa-solid fa-eye mr-1"></i>Tampilkan
                </button>
            </form>

            <div class="flex flex-wrap items-center gap-2 text-[11px]">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700 ring-1 ring-emerald-100">
                    <i class="fa-solid fa-check text-[9px]"></i>Diberikan
                </span>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2.5 py-1 font-semibold text-slate-400 ring-1 ring-slate-200">
                    <i class="fa-solid fa-minus text-[9px]"></i>Tidak diberikan
                </span>
            </div>
        </div>

        <div class="mt-4 border-t border-slate-100 pt-4">
            @if($selectedRole)
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <p class="text-sm font-semibold text-slate-700">{{ $selectedRole->name }}</p>
                    <p class="text-xs text-slate-500">{{ $grantedTotal }} dari {{ $actionTotal }} aksi diberikan</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">{{ $percent }}%</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full bg-blue-600" style="width: {{ $percent }}%"></div>
            </div>
            @if($grantedTotal === 0)
            <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-700">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                Role ini belum memiliki hak akses. Atur melalui menu <a href="{{ route('internal.roles.index') }}" class="font-semibold underline">Role &rarr; Edit</a>.
            </p>
            @endif
            @else
            <p class="text-xs text-slate-500">
                <i class="fa-solid fa-circle-info mr-1"></i>
                Pilih jabatan/role untuk melihat aksi yang diberikan. Semua aksi tetap ditampilkan sebagai teks.
            </p>
            @endif

            <p class="mt-2 text-[11px] text-slate-400">
                Halaman ini hanya menampilkan akses dan aksi. Perubahan hak akses dilakukan pada menu
                <a href="{{ route('internal.roles.index') }}" class="font-semibold text-blue-600">Role</a>.
            </p>
        </div>
    </div>

    <div class="ia-card p-4">
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i>
            <input id="accessSearch" type="search" placeholder="Cari fitur atau aksi..." class="w-full rounded-lg border border-slate-200 py-2 pl-8 pr-3 text-sm">
        </div>
    </div>

    @foreach($features as $index => $group)
    <div class="ia-card p-5" data-feature-group>
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $group['group'] }}</p>
            <span class="text-[11px] text-slate-400">
                {{ count($group['items']) }} fitur
                @if($selectedRoleId)
                &middot; {{ $groupStats[$index]['granted'] }}/{{ $groupStats[$index]['total'] }} aksi
                @endif
            </span>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach($group['items'] as $item)
            <div class="flex flex-wrap items-center justify-between gap-3 py-2.5" data-feature-row>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-700">{{ $item['name'] }}</p>
                    <p class="font-mono text-[10px] text-slate-400">{{ $item['id'] }}</p>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($item['actions'] as $action)
                    @php $active = isset($permissions[$item['id']][$action]); @endphp
                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $active ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-50 text-slate-400 ring-slate-200' }}">
                        <i class="fa-solid {{ $active ? 'fa-check' : 'fa-minus' }} text-[9px]"></i>
                        {{ \App\Support\Feature::actionLabel($action) }}
                    </span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <p id="accessEmpty" class="hidden ia-card p-10 text-center text-sm text-slate-400">
        Tidak ada fitur atau aksi yang cocok dengan pencarian.
    </p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var search = document.getElementById('accessSearch');
    if (!search) return;

    search.addEventListener('input', function () {
        var query = this.value.trim().toLowerCase();
        var anyVisible = false;

        document.querySelectorAll('[data-feature-group]').forEach(function (group) {
            var visible = 0;

            group.querySelectorAll('[data-feature-row]').forEach(function (row) {
                var match = query === '' || row.textContent.toLowerCase().indexOf(query) !== -1;
                row.classList.toggle('hidden', !match);
                if (match) visible++;
            });

            group.classList.toggle('hidden', visible === 0);
            if (visible > 0) anyVisible = true;
        });

        document.getElementById('accessEmpty').classList.toggle('hidden', anyVisible);
    });
});
</script>
@endsection
