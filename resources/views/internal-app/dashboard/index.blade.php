@extends('internal-app.layouts.app')
@section('title', 'Dashboard')
@section('subtitle', 'Ringkasan aplikasi, pengumuman, video tutorial & dokumentasi')

@section('body')
<div class="space-y-5">
    @if($isAdmin)
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Total Project</p><p class="mt-1 text-2xl font-black text-slate-800">{{ number_format($projectStats['total']) }}</p></div>
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Total Task</p><p class="mt-1 text-2xl font-black text-blue-600">{{ number_format($projectStats['tasks']) }}</p></div>
        <div class="ia-card p-4"><p class="text-xs font-semibold text-slate-400">Aktivitas Bulan Ini</p><p class="mt-1 text-2xl font-black text-indigo-600">{{ number_format($projectStats['activities']) }}</p></div>
    </div>
    @endif

    <div class="ia-card p-5">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-slate-800"><i class="fa-solid fa-bullhorn mr-1 text-amber-500"></i>Pengumuman</h2>
            @if($isAdmin)<a href="{{ route('internal.pengumuman.index') }}" class="text-xs font-semibold text-blue-600">Kelola &rarr;</a>@endif
        </div>
        <div class="mt-3 space-y-2">
            @forelse($announcements as $a)
            <div class="rounded-xl border border-slate-100 p-3">
                <p class="text-sm font-semibold text-slate-800">{{ $a->title }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags((string) $a->content), 220) }}</p>
                @if($a->pdf_path)
                <a href="{{ asset(ltrim($a->pdf_path, '/')) }}" class="mt-1 inline-block text-xs font-semibold text-blue-600"><i class="fa-solid fa-file-pdf mr-1"></i>Lihat PDF</a>
                @endif
                <p class="mt-1 text-[10px] text-slate-400">{{ $a->created_at }}</p>
            </div>
            @empty
            <p class="py-6 text-center text-sm text-slate-400">Belum ada pengumuman.</p>
            @endforelse
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        {{-- VIDEO TUTORIAL --}}
        <div class="ia-card p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-800"><i class="fa-solid fa-circle-play mr-1 text-red-500"></i>Video Tutorial</h2>
                @if($isAdmin)<a href="{{ route('internal.video-tutorials.index') }}" class="text-xs font-semibold text-blue-600">Kelola &rarr;</a>@endif
            </div>
            <div class="mt-3 space-y-2">
                @forelse($videos as $v)
                <button type="button" onclick="iaOpenVideo(this)" data-url="{{ $v->youtube_url }}" data-title="{{ $v->title }}" class="flex w-full items-center gap-3 rounded-xl border border-slate-100 p-3 text-left hover:bg-slate-50">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600"><i class="fa-solid fa-play"></i></span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $v->title }}</p>
                        @if($v->badge_text)<span class="mt-0.5 inline-block rounded-full {{ $v->badge_color ?: 'bg-blue-600' }} px-2 py-0.5 text-[10px] font-bold text-white">{{ $v->badge_text }}</span>@endif
                    </div>
                </button>
                @empty
                <p class="py-6 text-center text-sm text-slate-400">Belum ada video tutorial.</p>
                @endforelse
            </div>
        </div>

        {{-- DOKUMENTASI --}}
        <div class="ia-card p-5">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-800"><i class="fa-solid fa-folder-open mr-1 text-emerald-500"></i>Dokumentasi</h2>
                @if($isAdmin)<a href="{{ route('internal.dokumentasi.index') }}" class="text-xs font-semibold text-blue-600">Kelola &rarr;</a>@endif
            </div>
            <div class="mt-3 space-y-2">
                @forelse($docs as $d)
                <button type="button" onclick="iaOpenDoc(this)" data-title="{{ $d->title }}" data-desc="{{ $d->description }}" data-file="{{ $d->file_url }}" class="flex w-full items-center gap-3 rounded-xl border border-slate-100 p-3 text-left hover:bg-slate-50">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600"><i class="fa-solid fa-file-lines"></i></span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-700">{{ $d->title }}</p>
                        <p class="text-[11px] text-slate-400">{{ $d->description }}</p>
                    </div>
                </button>
                @empty
                <p class="py-6 text-center text-sm text-slate-400">Belum ada dokumentasi.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Popup Pengumuman (pattern SiTlhp: tampil saat pertama login) --}}
<div id="ia-ann-modal" class="fixed inset-0 z-[10050] hidden items-center justify-center bg-black/50 p-4">
    <div class="flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-xl font-bold text-slate-900">Pembaruan</h2>
            <button type="button" onclick="iaCloseAnn()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
            @forelse($announcements as $a)
            <div class="rounded-xl border border-slate-100 p-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-bold text-slate-800">{{ $a->title }}</p>
                    <span class="shrink-0 rounded-md bg-indigo-600 px-2 py-0.5 text-[10px] font-semibold text-white">{{ $a->code ?: 'Pengumuman' }}</span>
                </div>
                <p class="mt-0.5 text-[11px] text-slate-400">{{ $a->created_at }}</p>
                <div class="prose prose-sm mt-2 max-w-none text-sm text-slate-600">{!! $a->content !!}</div>
                @if($a->pdf_path)
                <a href="{{ asset(ltrim($a->pdf_path, '/')) }}" class="mt-2 inline-block text-xs font-semibold text-blue-600"><i class="fa-solid fa-file-pdf mr-1"></i>Lihat PDF</a>
                @endif
            </div>
            @empty
            <p class="py-6 text-center text-sm text-slate-400">Belum ada pengumuman.</p>
            @endforelse
        </div>
        <div class="flex items-center justify-between border-t border-slate-100 px-5 py-4">
            <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                <input type="checkbox" id="ia-ann-dontshow" class="toggle-sw"> Jangan tampilkan lagi
            </label>
            <button type="button" onclick="iaCloseAnn()" class="rounded-lg border border-slate-300 bg-slate-100 px-6 py-2 text-sm font-semibold text-slate-700">Sembunyikan</button>
        </div>
    </div>
</div>

{{-- Modal Video Tutorial --}}
<div id="ia-video-modal" class="fixed inset-0 z-[10055] hidden items-center justify-center bg-black/70 p-4">
    <div class="flex w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 id="ia-video-title" class="text-sm font-bold text-slate-800">Video Tutorial</h3>
            <button type="button" onclick="iaCloseVideo()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="aspect-video bg-black">
            <iframe id="ia-video-frame" class="h-full w-full border-0" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
        </div>
    </div>
</div>

{{-- Modal Dokumentasi --}}
<div id="ia-doc-modal" class="fixed inset-0 z-[10055] hidden items-center justify-center bg-black/70 p-4">
    <div class="flex max-h-[88vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 id="ia-doc-title" class="text-sm font-bold text-slate-800">Dokumentasi</h3>
            <button type="button" onclick="iaCloseDoc()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="flex-1 overflow-auto p-4">
            <p id="ia-doc-desc" class="mb-3 text-sm text-slate-600"></p>
            <iframe id="ia-doc-frame" class="hidden h-[60vh] w-full rounded-lg border border-slate-200"></iframe>
            <img id="ia-doc-image" class="mx-auto hidden max-h-[60vh] rounded-lg" alt="Dokumentasi">
            <a id="ia-doc-link" class="hidden text-sm font-semibold text-blue-600" target="_blank" rel="noopener">Buka file &rarr;</a>
            <p id="ia-doc-empty" class="hidden text-sm text-slate-400">File belum tersedia. Silakan hubungi admin.</p>
        </div>
    </div>
</div>

<script>
(function () {
    var m = document.getElementById('ia-ann-modal');
    if (!m) return;
    if (localStorage.getItem('ia_announcement_hidden') === '1') return;
    if ({{ $announcements->count() }} === 0) return;
    m.classList.remove('hidden'); m.classList.add('flex');
})();

function iaCloseAnn() {
    var cb = document.getElementById('ia-ann-dontshow');
    if (cb && cb.checked) localStorage.setItem('ia_announcement_hidden', '1');
    var m = document.getElementById('ia-ann-modal');
    if (m) { m.classList.add('hidden'); m.classList.remove('flex'); }
}

function iaToEmbed(url) {
    if (!url) return '';
    var m = String(url).match(/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([A-Za-z0-9_-]{6,})/);
    return m ? ('https://www.youtube.com/embed/' + m[1] + '?autoplay=1&rel=0') : url;
}
function iaOpenVideo(btn) {
    var url = iaToEmbed(btn.getAttribute('data-url'));
    document.getElementById('ia-video-title').textContent = btn.getAttribute('data-title') || 'Video Tutorial';
    document.getElementById('ia-video-frame').src = url;
    var m = document.getElementById('ia-video-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function iaCloseVideo() {
    var m = document.getElementById('ia-video-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.getElementById('ia-video-frame').src = '';
}

function iaOpenDoc(btn) {
    var title = btn.getAttribute('data-title') || 'Dokumentasi';
    var desc = btn.getAttribute('data-desc') || '';
    var file = btn.getAttribute('data-file') || '';
    document.getElementById('ia-doc-title').textContent = title;
    document.getElementById('ia-doc-desc').textContent = desc;

    var frame = document.getElementById('ia-doc-frame');
    var img = document.getElementById('ia-doc-image');
    var link = document.getElementById('ia-doc-link');
    var empty = document.getElementById('ia-doc-empty');
    frame.classList.add('hidden'); img.classList.add('hidden'); link.classList.add('hidden'); empty.classList.add('hidden');
    frame.src = ''; img.src = '';

    if (!file) {
        empty.classList.remove('hidden');
    } else if (/\.pdf($|\?)/i.test(file)) {
        frame.src = file; frame.classList.remove('hidden');
    } else if (/\.(png|jpe?g|gif|webp|bmp|svg)($|\?)/i.test(file)) {
        img.src = file; img.classList.remove('hidden');
    } else {
        link.href = file; link.classList.remove('hidden');
    }

    var m = document.getElementById('ia-doc-modal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function iaCloseDoc() {
    var m = document.getElementById('ia-doc-modal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.getElementById('ia-doc-frame').src = '';
    document.getElementById('ia-doc-image').src = '';
}
</script>
@endsection
