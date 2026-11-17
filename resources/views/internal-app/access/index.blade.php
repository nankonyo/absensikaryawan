@extends('internal-app.layouts.app')
@section('title', 'Hak Akses')
@section('subtitle', 'Pengaturan hak akses fitur per jabatan/role')

@section('body')
<div class="space-y-5">
    <div class="ia-card p-5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="text-xs font-semibold text-slate-500">Jabatan / Role</label>
                <select id="accessRole" data-ajax-url="{{ route('internal.ajax.options', ['type' => 'roles']) }}" data-placeholder="Pilih jabatan/role..." class="w-72 rounded-lg border border-slate-200 px-3 py-2 text-sm"></select>
            </div>
            <button onclick="saveAccess()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-floppy-disk mr-1"></i>Simpan Hak Akses</button>
        </div>
    </div>

    <div class="ia-card p-5">
        <div id="accessMatrix" class="space-y-4">
            @foreach($features as $group)
            <div>
                <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $group['group'] }}</p>
                <div class="space-y-2">
                    @foreach($group['items'] as $item)
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 p-3">
                        <span class="text-sm font-medium text-slate-700">{{ $item['name'] }}</span>
                        <div class="flex flex-wrap gap-3">
                            @foreach($item['actions'] as $action)
                            <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                <input type="checkbox" class="perm-check" data-code="{{ $item['id'] }}" data-action="{{ $action }}">
                                <span class="capitalize">{{ $action }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<script>
document.addEventListener('change', async function (e) {
    if (e.target && e.target.id === 'accessRole') {
        const roleId = e.target.value;
        document.querySelectorAll('.perm-check').forEach(c => c.checked = false);
        if (!roleId) return;
        try {
            const r = await fetch('{{ url('internal/roles') }}/' + roleId + '/permissions', { headers: { 'Accept': 'application/json' } });
            const d = await r.json();
            const map = d.permissions || {};
            document.querySelectorAll('.perm-check').forEach(function (c) {
                c.checked = !!(map[c.dataset.code] && map[c.dataset.code][c.dataset.action]);
            });
        } catch (err) { iaToast('Gagal memuat hak akses.', false); }
    }
});

async function saveAccess() {
    const roleId = document.getElementById('accessRole').value;
    if (!roleId) { iaToast('Pilih jabatan/role terlebih dahulu.', 'warning', 'Peringatan'); return; }
    const permissions = {};
    document.querySelectorAll('.perm-check:checked').forEach(function (c) {
        permissions[c.dataset.code] = permissions[c.dataset.code] || {};
        permissions[c.dataset.code][c.dataset.action] = true;
    });
    try {
        const d = await postJson('{{ url('internal/roles') }}/' + roleId + '/permissions', { permissions: permissions });
        iaToast(d.message);
    } catch (e) { iaToast(e.message, false); }
}
</script>
@endsection
