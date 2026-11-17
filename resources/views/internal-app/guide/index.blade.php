@extends('internal-app.layouts.app')
@section('title', 'Panduan Penggunaan')
@section('subtitle', $module === 'absensi' ? 'Panduan Modul Absensi' : 'Panduan Modul Project Management')

@section('body')
@php
    $moduleTitle = $module === 'absensi' ? 'Absensi' : 'Project Management';
    $backRoute = $module === 'absensi' ? route('internal.projects.index') : route('internal.projects.index');
@endphp
<div x-data="guideApp(@js($steps))" class="space-y-5">
    <div class="ia-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-slate-800">Panduan {{ $moduleTitle }}</h1>
                <p class="text-sm text-slate-500">Artikel bantuan, FAQ, dan tur interaktif.</p>
                @if($isAdmin)<span class="mt-1 inline-block rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700">MODE ADMIN</span>@endif
            </div>
            <div class="flex gap-2">
                <a href="{{ $backRoute }}" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600">Kembali</a>
                @if($steps->count())<button @click="startTour()" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-person-chalkboard mr-1"></i>Mulai Tur</button>@endif
            </div>
        </div>
        <form method="get" class="mt-4 flex max-w-lg gap-2">
            <input name="q" value="{{ $q }}" placeholder="Cari panduan / FAQ, contoh: gagal absen gps" class="flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm">
            <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Cari</button>
        </form>
        <div class="mt-4 flex gap-2 text-sm">
            <button @click="tab='articles'" :class="tab==='articles'?'bg-blue-600 text-white':'bg-slate-100 text-slate-600'" class="rounded-lg px-3 py-1.5 font-semibold">Artikel</button>
            <button @click="tab='faq'" :class="tab==='faq'?'bg-blue-600 text-white':'bg-slate-100 text-slate-600'" class="rounded-lg px-3 py-1.5 font-semibold">FAQ</button>
        </div>
    </div>

    <div x-show="tab==='articles'" class="space-y-3">
        @forelse($guides as $g)
        <details class="ia-card p-5" {{ $loop->first ? 'open' : '' }}>
            <summary class="cursor-pointer font-bold text-slate-800">{{ $g->title }} @if($g->role_scope==='admin')<span class="ml-2 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700">ADMIN</span>@endif</summary>
            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-600">{{ $g->content }}</p>
        </details>
        @empty
        <div class="ia-card p-10 text-center text-sm text-slate-400">Tidak ada artikel yang cocok.</div>
        @endforelse
    </div>

    <div x-show="tab==='faq'" class="space-y-3">
        @forelse($faqs as $f)
        <details class="ia-card p-5">
            <summary class="cursor-pointer font-semibold text-slate-800">{{ $f->question }}</summary>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $f->answer }}</p>
        </details>
        @empty
        <div class="ia-card p-10 text-center text-sm text-slate-400">Tidak ada FAQ yang cocok.</div>
        @endforelse
    </div>

    <div x-show="tour" x-cloak class="fixed inset-0 z-[10000]">
        <div class="absolute inset-0 bg-slate-900/45" @click="endTour()"></div>
        <div class="absolute bottom-6 left-1/2 w-[min(520px,92vw)] -translate-x-1/2 rounded-2xl bg-white p-5 shadow-2xl">
            <div class="flex items-center justify-between"><span class="text-xs font-bold text-indigo-600">LANGKAH <span x-text="step+1"></span> / <span x-text="steps.length"></span></span><button @click="endTour()" class="text-slate-400"><i class="fa-solid fa-xmark"></i></button></div>
            <h3 class="mt-2 font-bold text-slate-800" x-text="steps[step] ? steps[step].title : ''"></h3>
            <p class="mt-1 text-sm text-slate-600" x-text="steps[step] ? steps[step].description : ''"></p>
            <div class="mt-4 flex justify-between">
                <button @click="prevStep()" class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-600">Sebelumnya</button>
                <button @click="nextStep()" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white" x-text="step===steps.length-1?'Selesai':'Berikutnya'"></button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function guideApp(steps) {
    return {
        tab: 'articles', tour: false, step: 0, steps: steps || [],
        highlightEl: null,
        startTour() { this.step = 0; this.tour = true; this.$nextTick(() => this.highlight()); },
        endTour() { this.tour = false; if (this.highlightEl) this.highlightEl.classList.remove('tour-highlight'); this.highlightEl = null; },
        highlight() {
            if (this.highlightEl) this.highlightEl.classList.remove('tour-highlight');
            const s = this.steps[this.step];
            if (!s || !s.target_selector) return;
            const el = document.querySelector(s.target_selector);
            if (el) {
                this.highlightEl = el;
                el.classList.add('tour-highlight');
                el.scrollIntoView({behavior: 'smooth', block: 'center'});
            }
        },
        nextStep() { if (this.step >= this.steps.length - 1) { this.endTour(); return; } this.step++; this.highlight(); },
        prevStep() { if (this.step > 0) { this.step--; this.highlight(); } }
    };
}
</script>
@endpush
@endsection
