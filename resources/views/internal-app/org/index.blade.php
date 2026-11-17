@extends('internal-app.layouts.app')
@section('title', 'Struktur Organisasi')
@section('subtitle', 'Hierarchy Direktur → General Manager → Project Manager → Konsultan → Programmer → Staf')

@section('body')
@php $userNames = $users->pluck('nama', 'id'); @endphp
<div x-data="{ add: false, edit: false }" @open-node-edit.window="edit = true" class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-slate-800">Tree Hierarchy</h2>
            <p class="text-xs text-slate-500">Drag handle <i class="fa-solid fa-grip-vertical"></i> untuk memindahkan node / mengurutkan sejajar. Perubahan langsung tersimpan.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="syncDefaults()" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600"><i class="fa-solid fa-arrows-rotate mr-1"></i>Sync Default Structure</button>
            <button @click="add = true" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Tambah Node</button>
        </div>
    </div>

    <div class="ia-card p-4">
        <div id="org-root" class="org-children" data-parent="">
            @foreach($tree as $item)
                @include('internal-app.org._node', ['node' => $item['node'], 'children' => $item['children']])
            @endforeach
        </div>
        @if(empty($tree))
        <p class="py-10 text-center text-sm text-slate-400">Belum ada struktur.</p>
        @endif
    </div>

    {{-- ADD --}}
    <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" @click.outside="add=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Tambah Node Struktur</h3><button @click="add=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 space-y-3" onsubmit="return createNode(event)">
                @include('internal-app.org._form')
                <button class="w-full rounded-xl bg-blue-600 py-2.5 font-semibold text-white">Simpan</button>
            </form>
        </div>
    </div>

    {{-- EDIT --}}
    <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Edit Node Struktur</h3><button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form id="editNodeForm" class="mt-4 space-y-3" onsubmit="return updateNode(event)">
                <input type="hidden" name="id">
                @include('internal-app.org._form')
                <button class="w-full rounded-xl bg-blue-600 py-2.5 font-semibold text-white">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function initOrgSortable() {
    document.querySelectorAll('.org-children').forEach(function (el) {
        if (el._orgSortable) {
            el._orgSortable.destroy();
        }
        el._orgSortable = new Sortable(el, {
            group: 'org',
            handle: '.drag-handle',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onEnd: function () { saveOrgOrder(); }
        });
    });
}

function collectOrgOrder(container, parentId) {
    var items = [];
    var sort = 0;
    Array.from(container.children).forEach(function (item) {
        if (!item.classList || !item.classList.contains('org-item')) return;
        sort++;
        var id = Number(item.getAttribute('data-id'));
        items.push({ id: id, parent_id: parentId, sort: sort });
        var childCon = item.querySelector(':scope > .org-children');
        if (childCon) {
            items = items.concat(collectOrgOrder(childCon, id));
        }
    });
    return items;
}

async function saveOrgOrder() {
    var root = document.getElementById('org-root');
    if (!root) return;
    var items = collectOrgOrder(root, null);
    try {
        const d = await postJson('{{ route('internal.organization.reorder') }}', { items: items });
        iaToast(d.message);
    } catch (e) { iaToast(e.message, false); }
}

function fillNodeForm(form, d) {
    ['id','name','parent_id','user_id','sort'].forEach(function (k) {
        var el = form.querySelector('[name="'+k+'"]');
        if (el) el.value = d[k] === null || d[k] === undefined ? '' : d[k];
    });
}
async function createNode(e){e.preventDefault();try{const d=await postJson('{{ route('internal.organization.store') }}',Object.fromEntries(new FormData(e.target).entries()));iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
function editNode(d){fillNodeForm(document.getElementById('editNodeForm'),d);window.dispatchEvent(new CustomEvent('open-node-edit'));}
async function updateNode(e){e.preventDefault();const fd=new FormData(e.target);const id=fd.get('id');try{const d=await postJson('{{ url('internal/organization') }}/'+id+'/update',Object.fromEntries(fd.entries()));iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
async function deleteNode(id){if(!confirm('Hapus node dan seluruh anaknya?'))return;try{const d=await postJson('{{ url('internal/organization') }}/'+id+'/delete',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
async function syncDefaults(){try{const d=await postJson('{{ route('internal.organization.sync-defaults') }}',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}

initOrgSortable();
window.addEventListener('pageshow', function () { setTimeout(initOrgSortable, 50); });
</script>
@endsection
