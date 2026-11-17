<x-app-layout>
    <x-slot name="header">Pengumuman</x-slot>
    @include('internal-app.partials.ia-assets')

    <div x-data="{ add:false, edit:false }" @open-ann-edit.window="edit=true" class="space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-800">Daftar Pengumuman</h2>
            <button @click="add=true" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Tambah Pengumuman</button>
        </div>

        <div class="ia-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Judul</th><th class="px-4 py-3">Kode</th><th class="px-4 py-3">PDF</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Tanggal</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $a)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $a->title }}</td>
                            <td class="px-4 py-2 text-xs">{{ $a->code ?: '-' }}</td>
                            <td class="px-4 py-2 text-xs">@if($a->pdf_path)<a href="{{ asset(ltrim($a->pdf_path,'/')) }}" class="text-blue-600"><i class="fa-solid fa-file-pdf mr-1"></i>Lihat</a>@else - @endif</td>
                            <td class="px-4 py-2">
                                <label class="inline-flex items-center gap-2 text-xs">
                                    <input type="checkbox" class="toggle-sw" @checked($a->is_active) onchange="toggleAnn({{ $a->id }})">
                                    {{ $a->is_active ? 'Aktif' : 'Nonaktif' }}
                                </label>
                            </td>
                            <td class="px-4 py-2 text-xs whitespace-nowrap">{{ $a->created_at }}</td>
                            <td class="px-4 py-2 text-right">
                                @php $ad = ['id'=>$a->id,'code'=>$a->code,'slug'=>$a->slug,'title'=>$a->title,'content'=>$a->content,'is_active'=>(bool)$a->is_active,'pdf_url'=>$a->pdf_path ? asset(ltrim($a->pdf_path,'/')) : null]; @endphp
                                <button onclick="editAnn({{ Illuminate\Support\Js::from($ad) }})" class="rounded border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600">Edit</button>
                                <button onclick="deleteAnn({{ $a->id }})" class="rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-600">Hapus</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada pengumuman.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="add=false">
                <div class="flex items-center justify-between"><h3 class="font-bold">Tambah Pengumuman</h3><button @click="add=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
                <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return saveAnn(event)">
                    @include('internal-app.pengumuman._form')
                    <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan</button>
                </form>
            </div>
        </div>
        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit=false">
                <div class="flex items-center justify-between"><h3 class="font-bold">Edit Pengumuman</h3><button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
                <form id="editAnnForm" class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return updateAnn(event)">
                    <input type="hidden" name="id">
                    @include('internal-app.pengumuman._form')
                    <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function syncEditor(form, name, value){
        var el = form.querySelector('[name="'+name+'"]');
        if(!el) return;
        el.value = value || '';
        if (window.tinymce && el.id && tinymce.get(el.id)) tinymce.get(el.id).setContent(value || '');
    }
    function fillAnnForm(form,d){
        ['id','code','slug','title'].forEach(function(k){var el=form.querySelector('[name="'+k+'"]'); if(el) el.value=d[k]??'';});
        syncEditor(form,'content',d.content);
        var c=form.querySelector('[name="is_active"]'); if(c) c.checked=!!d.is_active;
    }
    async function saveAnn(e){e.preventDefault();try{const d=await postForm('{{ route('internal.pengumuman.store') }}',new FormData(e.target));iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
    function editAnn(d){fillAnnForm(document.getElementById('editAnnForm'),d);window.dispatchEvent(new CustomEvent('open-ann-edit'));}
    async function updateAnn(e){e.preventDefault();const fd=new FormData(e.target);const id=fd.get('id');try{const d=await postForm('{{ url('internal/pengumuman') }}/'+id+'/update',fd);iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
    async function deleteAnn(id){if(!confirm('Hapus pengumuman ini?'))return;try{const d=await postJson('{{ url('internal/pengumuman') }}/'+id+'/delete',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
    async function toggleAnn(id){try{const d=await postJson('{{ url('internal/pengumuman') }}/'+id+'/toggle',{});iaToast(d.message);setTimeout(()=>iaReload(),300);}catch(e){iaToast(e.message,false)}}
    </script>
</x-app-layout>
