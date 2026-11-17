<x-app-layout>
    <x-slot name="header">Video Tutorial</x-slot>
    @include('internal-app.partials.ia-assets')

    <div x-data="{ add:false, edit:false }" @open-video-edit.window="edit=true" class="space-y-5">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-800">Daftar Video Tutorial</h2>
            <button @click="add=true" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-plus mr-1"></i>Tambah Video</button>
        </div>

        <div class="ia-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Judul</th><th class="px-4 py-3">URL</th><th class="px-4 py-3">Badge</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($items as $v)
                        <tr>
                            <td class="px-4 py-2 font-medium">{{ $v->title }}</td>
                            <td class="px-4 py-2 text-xs max-w-[280px] truncate"><a href="{{ $v->youtube_url }}" target="_blank" class="text-blue-600">{{ $v->youtube_url }}</a></td>
                            <td class="px-4 py-2 text-xs">@if($v->badge_text)<span class="rounded-full {{ $v->badge_color ?: 'bg-blue-600' }} px-2 py-0.5 text-[10px] font-bold text-white">{{ $v->badge_text }}</span>@endif</td>
                            <td class="px-4 py-2">
                                <label class="inline-flex items-center gap-2 text-xs">
                                    <input type="checkbox" class="toggle-sw" @checked($v->is_active) onchange="toggleVideo({{ $v->id }})">
                                    {{ $v->is_active ? 'Aktif' : 'Nonaktif' }}
                                </label>
                            </td>
                            <td class="px-4 py-2 text-right">
                                @php $vd = ['id'=>$v->id,'title'=>$v->title,'youtube_url'=>$v->youtube_url,'badge_text'=>$v->badge_text,'badge_color'=>$v->badge_color,'is_active'=>(bool)$v->is_active]; @endphp
                                <button onclick="editVideo({{ Illuminate\Support\Js::from($vd) }})" class="rounded border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-600">Edit</button>
                                <button onclick="deleteVideo({{ $v->id }})" class="rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-600">Hapus</button>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada video.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div x-show="add" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl" @click.outside="add=false">
                <div class="flex items-center justify-between"><h3 class="font-bold">Tambah Video</h3><button @click="add=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
                <form class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return saveVideo(event)">
                    @include('internal-app.video-tutorials._form')
                    <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan</button>
                </form>
            </div>
        </div>
        <div x-show="edit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-2xl bg-white p-5 shadow-xl" @click.outside="edit=false">
                <div class="flex items-center justify-between"><h3 class="font-bold">Edit Video</h3><button @click="edit=false" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
                <form id="editVideoForm" class="mt-4 grid gap-3 sm:grid-cols-2" onsubmit="return updateVideo(event)">
                    <input type="hidden" name="id">
                    @include('internal-app.video-tutorials._form')
                    <button class="rounded-xl bg-blue-600 py-2.5 font-semibold text-white sm:col-span-2">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function fillVideoForm(form,d){['id','title','youtube_url','badge_text','badge_color'].forEach(function(k){var el=form.querySelector('[name="'+k+'"]'); if(el) el.value=d[k]??'';});var c=form.querySelector('[name="is_active"]'); if(c) c.checked=!!d.is_active;}
    async function saveVideo(e){e.preventDefault();const fd=new FormData(e.target);const p=Object.fromEntries(fd.entries());p.is_active=fd.get('is_active')?1:0;try{const d=await postJson('{{ route('internal.video-tutorials.store') }}',p);iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
    function editVideo(d){fillVideoForm(document.getElementById('editVideoForm'),d);window.dispatchEvent(new CustomEvent('open-video-edit'));}
    async function updateVideo(e){e.preventDefault();const fd=new FormData(e.target);const id=fd.get('id');const p=Object.fromEntries(fd.entries());p.is_active=fd.get('is_active')?1:0;try{const d=await postJson('{{ url('internal/video-tutorials') }}/'+id+'/update',p);iaToast(d.message);setTimeout(()=>iaReload(),500);}catch(err){iaToast(err.message,false)}return false}
    async function deleteVideo(id){if(!confirm('Hapus video ini?'))return;try{const d=await postJson('{{ url('internal/video-tutorials') }}/'+id+'/delete',{});iaToast(d.message);setTimeout(()=>iaReload(),400);}catch(e){iaToast(e.message,false)}}
    async function toggleVideo(id){try{const d=await postJson('{{ url('internal/video-tutorials') }}/'+id+'/toggle',{});iaToast(d.message);setTimeout(()=>iaReload(),300);}catch(e){iaToast(e.message,false)}}
    </script>
</x-app-layout>
