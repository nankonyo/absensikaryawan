<x-app-layout>
    <x-slot name="header">Dokumentasi</x-slot>
    @include('internal-app.partials.ia-assets')

    <div x-data="{ add:false, edit:false }" @open-doc-edit.window="edit=true" class="space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-800">Daftar Dokumentasi</h2>
            <button @click="add=true" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Tambah Dokumentasi</button>
        </div>

        <div class="ia-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Judul</th><th class="px-4 py-3">Deskripsi</th><th class="px-4 py-3">File</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $d)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $d->title }}</td>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags((string)$d->description), 80) }}</td>
                            <td class="px-4 py-2 text-xs">
                                @if($d->file_url)
                                <a href="{{ $d->file_url }}" target="_blank" class="text-blue-600"><i class="fa-solid fa-paperclip mr-1"></i>Buka</a>
                                @else - @endif
                            </td>
                            <td class="px-4 py-2">
                                <label class="inline-flex items-center gap-2 text-xs">
                                    <input type="checkbox" class="toggle-sw" @checked($d->is_active) onchange="toggleDoc({{ $d->id }})">
                                    {{ $d->is_active ? 'Aktif' : 'Nonaktif' }}
                                </label>
                            </td>
                            <td class="px-4 py-2 text-right">
                                @php $dd = ['id'=>$d->id,'title'=>$d->title,'description'=>$d->description,'file_url'=>$d->file_url,'icon_color'=>$d->icon_color,'is_active'=>(bool)$d->is_active]; @endphp
                                <button onclick="editDoc({{ Illuminate\Support\Js::from($dd) }})" class="rounded border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600">Edit</button>
                                <button onclick="deleteDoc({{ $d->id }})" class="rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-600">Hapus</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada dokumentasi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl" @click.outside="add=false">
                <div class="flex items-center justify-between"><h3 class="font-bold">Tambah Dokumentasi</h3><button @click="add=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
                <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return saveDoc(event)">
                    @include('internal-app.dokumentasi._form')
                    <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan</button>
                </form>
            </div>
        </div>
        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit=false">
                <div class="flex items-center justify-between"><h3 class="font-bold">Edit Dokumentasi</h3><button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
                <form id="editDocForm" class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return updateDoc(event)">
                    <input type="hidden" name="id">
                    @include('internal-app.dokumentasi._form')
                    <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function fillDocForm(form,d){['id','title','description','file_url','icon_color'].forEach(function(k){var el=form.querySelector('[name="'+k+'"]'); if(el) el.value=d[k]??'';});var c=form.querySelector('[name="is_active"]'); if(c) c.checked=!!d.is_active;}
    async function saveDoc(e){e.preventDefault();const fd=new FormData(e.target);fd.set('is_active',fd.get('is_active')?1:0);try{const d=await postForm('{{ route('internal.dokumentasi.store') }}',fd);iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
    function editDoc(d){fillDocForm(document.getElementById('editDocForm'),d);window.dispatchEvent(new CustomEvent('open-doc-edit'));}
    async function updateDoc(e){e.preventDefault();const fd=new FormData(e.target);const id=fd.get('id');fd.set('is_active',fd.get('is_active')?1:0);try{const d=await postForm('{{ url('internal/dokumentasi') }}/'+id+'/update',fd);iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
    async function deleteDoc(id){if(!confirm('Hapus dokumentasi ini?'))return;try{const d=await postJson('{{ url('internal/dokumentasi') }}/'+id+'/delete',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
    async function toggleDoc(id){try{const d=await postJson('{{ url('internal/dokumentasi') }}/'+id+'/toggle',{});iaToast(d.message);setTimeout(()=>iaReload(),300);}catch(e){iaToast(e.message,false)}}
    </script>
</x-app-layout>
