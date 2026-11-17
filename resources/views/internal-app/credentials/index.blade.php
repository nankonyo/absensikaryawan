@extends('internal-app.layouts.app')
@section('title', 'Server & Domain')
@section('subtitle', 'Pencatatan credential, domain, server, dan masa berlaku')

@section('body')
@php
    $soonDate = now()->addDays(30)->toDateString();
    $today = now()->toDateString();
@endphp
<div x-data="{ add: false, edit: false }" @open-cred-edit.window="edit = true" class="space-y-5">

    @if($expired > 0 || $expiring > 0)
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
        <b>Pemberitahuan:</b>
        {{ $expiring }} credential expired dalam 30 hari ke depan, {{ $expired }} sudah expired.
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <form method="get" class="flex flex-wrap items-center gap-2">
            <input name="q" value="{{ $q }}" placeholder="Cari nama / IP / user" class="w-56 rounded-lg border border-slate-200 px-3 py-2 text-sm">
            <select name="category" class="rounded-lg border border-slate-200 px-3 py-2 text-sm">
                <option value="">Semua kategori</option>
                @foreach($categories as $cat)
                <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filter</button>
        </form>
        <div class="flex gap-2">
            <button onclick="importCredentials()" class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700"><i class="fa-solid fa-file-import mr-1"></i>Import Excel</button>
            <button @click="add = true" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Tambah Credential</button>
        </div>
    </div>

    <div class="ia-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr><th class="px-3 py-3">Kategori</th><th class="px-3 py-3">Vendor / Nama</th><th class="px-3 py-3">IP / URL</th><th class="px-3 py-3">Username</th><th class="px-3 py-3">Password</th><th class="px-3 py-3">Expired</th><th class="px-3 py-3">Status</th><th class="px-3 py-3 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($items as $c)
                    @php
                        $isExpired = $c->expired_date && $c->expired_date < $today;
                        $isSoon = $c->expired_date && $c->expired_date >= $today && $c->expired_date <= $soonDate;
                    @endphp
                    <tr class="{{ $isExpired ? 'bg-red-50' : ($isSoon ? 'bg-red-50/60' : '') }}">
                        <td class="px-3 py-2 text-xs"><span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $c->category }}</span></td>
                        <td class="px-3 py-2"><b>{{ $c->vendor ?: '-' }}</b><p class="text-xs text-slate-500">{{ $c->name ?: '-' }}</p></td>
                        <td class="px-3 py-2 text-xs">{{ $c->ip_address ?: '-' }}<p class="text-slate-400">{{ $c->url ?: '' }}</p></td>
                        <td class="px-3 py-2 text-xs">{{ $c->username ?: '-' }}</td>
                        <td class="px-3 py-2 text-xs">
                            @if($c->password)
                            <button onclick="revealPassword({{ $c->id }}, this)" class="rounded border border-slate-200 px-2 py-0.5 font-semibold text-slate-600">Lihat</button>
                            @else - @endif
                        </td>
                        <td class="px-3 py-2 text-xs {{ $isExpired ? 'font-bold text-red-600' : ($isSoon ? 'font-semibold text-red-500' : '') }}">
                            {{ $c->expired_date ?: '-' }}
                            @if($isExpired) <span class="ml-1 rounded bg-red-600 px-1 text-[10px] text-white">EXPIRED</span>@endif
                        </td>
                        <td class="px-3 py-2 text-xs">{{ $c->status ?: '-' }}</td>
                        <td class="px-3 py-2 text-right">
                            @php
                                $credData = ['id'=>$c->id,'category'=>$c->category,'group_name'=>$c->group_name,'vendor'=>$c->vendor,'name'=>$c->name,'ip_address'=>$c->ip_address,'url'=>$c->url,'username'=>$c->username,'email'=>$c->email,'expired_date'=>$c->expired_date,'status'=>$c->status,'notes'=>$c->notes];
                            @endphp
                            <button onclick="editCredential({{ Illuminate\Support\Js::from($credData) }})" class="rounded border border-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-600">Edit</button>
                            <button onclick="deleteCredential({{ $c->id }})" class="rounded bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-600">Hapus</button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-3 py-10 text-center text-slate-400">Belum ada credential. Klik Import Excel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4">{{ $items->links() }}</div>
    </div>

    {{-- ADD --}}
    <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="add=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Tambah Credential</h3><button @click="add=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return createCredential(event)">
                @include('internal-app.credentials._form')
                <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan</button>
            </form>
        </div>
    </div>

    {{-- EDIT --}}
    <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit=false">
            <div class="flex items-center justify-between"><h3 class="font-bold">Edit Credential</h3><button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <form id="editCredForm" class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return updateCredential(event)">
                <input type="hidden" name="id">
                @include('internal-app.credentials._form')
                <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</div>

<script>
function fillCredForm(form, d) {
    ['id','category','group_name','vendor','name','ip_address','url','username','email','expired_date','status','notes'].forEach(function(k){
        var el = form.querySelector('[name="'+k+'"]'); if(el) el.value = d[k] === null || d[k] === undefined ? '' : d[k];
    });
    var pw = form.querySelector('[name="password"]'); if (pw) pw.value = '';
}
async function createCredential(e){e.preventDefault();try{const fd=new FormData(e.target);const d=await postJson('{{ route('internal.servers.store') }}',Object.fromEntries(fd.entries()));iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
function editCredential(d){const form=document.getElementById('editCredForm');fillCredForm(form,d);window.dispatchEvent(new CustomEvent('open-cred-edit'));}
async function updateCredential(e){e.preventDefault();const fd=new FormData(e.target);const id=fd.get('id');try{const d=await postJson('{{ url('internal/servers') }}/'+id+'/update',Object.fromEntries(fd.entries()));iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
async function deleteCredential(id){if(!confirm('Hapus credential ini?'))return;try{const d=await postJson('{{ url('internal/servers') }}/'+id+'/delete',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
async function revealPassword(id, btn){try{const r=await fetch('{{ url('internal/servers') }}/'+id+'/password',{headers:{'Accept':'application/json'}});const d=await r.json();if(d.password){btn.textContent=d.password;btn.classList.remove('text-slate-600');btn.classList.add('text-blue-700','font-mono');}else{iaToast('Password kosong',false);}}catch(e){iaToast('Gagal ambil password',false)}}
async function importCredentials(){if(!confirm('Import ulang semua credential dari Excel? Data credential non-OPD akan diganti.'))return;try{const d=await postJson('{{ route('internal.servers.import') }}',{});iaToast(d.message);setTimeout(()=>iaReload(),700);}catch(e){iaToast(e.message,false)}}
</script>
@endsection
