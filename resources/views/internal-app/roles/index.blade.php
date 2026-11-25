@extends('internal-app.layouts.app')
@section('title', 'Role')
@section('subtitle', 'Kelola role dan hak akses')

@section('body')
<div x-data="{ add: false, edit: false }" @open-role-edit.window="edit = true" class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-800">Daftar Role</h2>
            <p class="text-xs text-slate-500">Role menentukan akses pengguna ke fitur InternalApp.</p>
        </div>
        <button @click="add = true" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Tambah Role</button>
    </div>

    <div class="ia-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Slug</th><th class="px-4 py-3">Pengguna</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($roles as $r)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $r->name }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $r->slug }}</td>
                        <td class="px-4 py-3 text-xs">{{ (int) ($counts[$r->id] ?? 0) }}</td>
                        <td class="px-4 py-3">
                            @if($r->is_active)
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Aktif</span>
                            @else
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-[11px] font-semibold text-red-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="editRole({{ $r->id }})" class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600">Edit</button>
                            <button onclick="deleteRole({{ $r->id }})" class="rounded-lg bg-red-50 px-2 py-1 text-xs font-semibold text-red-600">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada role.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $roles->links() }}</div>
    </div>

    {{-- ADD ROLE --}}
    <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" @click.outside="add = false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Tambah Role</h3><button @click="add=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 space-y-3" onsubmit="return createRole(event)">
                <input name="name" placeholder="Nama role" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                <input name="slug" placeholder="Slug (opsional)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>
                <button class="w-full rounded-xl bg-blue-600 py-2.5 font-semibold text-white">Simpan Role</button>
            </form>
        </div>
    </div>

    {{-- EDIT ROLE + HAK AKSES --}}
    <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit = false">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold">Edit Role</h3>
                    <p class="text-xs text-slate-500">Ubah data role sekaligus hak akses fitur.</p>
                </div>
                <button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button>
            </div>

            <form id="editRoleForm" class="mt-4 space-y-3" onsubmit="return updateRole(event)">
                <input type="hidden" name="id">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <input name="name" placeholder="Nama role" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" required>
                    <input name="slug" placeholder="Slug" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" checked> Aktif</label>

                <div class="rounded-xl border border-slate-200">
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-t-xl bg-slate-50 px-3 py-2">
                        <div>
                            <p class="text-sm font-semibold text-slate-700">Hak Akses</p>
                            <p id="editPermCount" class="text-[11px] text-slate-500">0 / 0 aksi dipilih</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" onclick="toggleAllPerms(true)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600">Pilih Semua</button>
                            <button type="button" onclick="toggleAllPerms(false)" class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-600">Kosongkan</button>
                        </div>
                    </div>
                    <div class="max-h-72 space-y-4 overflow-y-auto p-3">
                        @foreach($features as $group)
                        <div>
                            <p class="mb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ $group['group'] }}</p>
                            <div class="space-y-1.5">
                                @foreach($group['items'] as $item)
                                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2">
                                    <span class="text-xs font-medium text-slate-700">{{ $item['name'] }}</span>
                                    <div class="flex flex-wrap gap-2.5">
                                        @foreach($item['actions'] as $action)
                                        <label class="flex items-center gap-1.5 text-[11px] text-slate-600">
                                            <input type="checkbox" class="perm-check rounded border-slate-300" data-code="{{ $item['id'] }}" data-action="{{ $action }}">
                                            <span>{{ \App\Support\Feature::actionLabel($action) }}</span>
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

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="edit=false" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600">Batal</button>
                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fillForm(form, data) {
    Object.keys(data).forEach(function (k) {
        var el = form.querySelector('[name="' + k + '"]');
        if (!el) return;
        if (el.type === 'checkbox') el.checked = !!data[k];
        else el.value = data[k] === null || data[k] === undefined ? '' : data[k];
    });
}
async function createRole(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const payload = Object.fromEntries(fd.entries());
    payload.is_active = fd.get('is_active') ? 1 : 0;
    try { const d = await postJson('{{ route('internal.roles.store') }}', payload); iaToast(d.message); setTimeout(() => iaReload(), 500); }
    catch (err) { iaToast(err.message, false); }
    return false;
}
async function editRole(id) {
    try {
        const [roleResponse, permissionResponse] = await Promise.all([
            fetch('{{ url('internal/roles') }}/' + id + '/edit', { headers: { 'Accept': 'application/json' } }),
            fetch('{{ url('internal/roles') }}/' + id + '/permissions', { headers: { 'Accept': 'application/json' } }),
        ]);
        const data = await roleResponse.json();
        const permissionData = await permissionResponse.json();
        const map = permissionData.permissions || {};

        fillForm(document.getElementById('editRoleForm'), data);
        document.querySelectorAll('.perm-check').forEach(function (checkbox) {
            checkbox.checked = !!(map[checkbox.dataset.code] && map[checkbox.dataset.code][checkbox.dataset.action]);
        });
        updatePermCount();
        window.dispatchEvent(new CustomEvent('open-role-edit'));
    } catch (e) { iaToast('Gagal memuat role.', false); }
}
function collectPermissions() {
    const permissions = {};
    document.querySelectorAll('.perm-check:checked').forEach(function (checkbox) {
        const code = checkbox.dataset.code;
        const action = checkbox.dataset.action;
        permissions[code] = permissions[code] || {};
        permissions[code][action] = true;
    });
    return permissions;
}
function toggleAllPerms(checked) {
    document.querySelectorAll('.perm-check').forEach(function (checkbox) { checkbox.checked = checked; });
    updatePermCount();
}
function updatePermCount() {
    const total = document.querySelectorAll('.perm-check').length;
    const checked = document.querySelectorAll('.perm-check:checked').length;
    const label = document.getElementById('editPermCount');
    if (label) label.textContent = checked + ' / ' + total + ' aksi dipilih';
}
document.addEventListener('change', function (e) {
    if (e.target && e.target.classList && e.target.classList.contains('perm-check')) updatePermCount();
});
async function updateRole(e) {
    e.preventDefault();
    const fd = new FormData(e.target);
    const id = fd.get('id');
    const payload = Object.fromEntries(fd.entries());
    payload.is_active = fd.get('is_active') ? 1 : 0;
    payload.permissions = collectPermissions();
    try { const d = await postJson('{{ url('internal/roles') }}/' + id + '/update', payload); iaToast(d.message); setTimeout(() => iaReload(), 500); }
    catch (err) { iaToast(err.message, false); }
    return false;
}
async function deleteRole(id) {
    if (!confirm('Hapus role ini?')) return;
    try { const d = await postJson('{{ url('internal/roles') }}/' + id + '/delete', {}); iaToast(d.message); setTimeout(() => iaReload(), 400); }
    catch (e) { iaToast(e.message, false); }
}
</script>
@endsection
