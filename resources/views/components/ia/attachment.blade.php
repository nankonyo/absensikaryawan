@props(['url' => null, 'label' => 'Lihat', 'type' => null])

@php
    $path = parse_url($url ?? '', PHP_URL_PATH) ?? '';
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $isPdf = $type === 'pdf' || $extension === 'pdf';
@endphp

<div x-data="{ open: false }" class="inline-block">
    <button type="button" @click="open = true"
            class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">
        {{ $label }}
    </button>

    <div x-show="open" x-cloak @click.self="open = false"
         class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/60 p-4">
        <div class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <p class="text-sm font-semibold text-slate-800">{{ $label }}</p>
                <button type="button" @click="open = false" class="rounded-lg px-2 py-1 text-slate-500 hover:bg-slate-100">✕</button>
            </div>

            @if($isPdf)
                <iframe src="{{ $url }}" class="h-[75vh] w-full border-0" title="{{ $label }}"></iframe>
            @else
                <div class="flex max-h-[75vh] items-center justify-center overflow-auto bg-slate-50 p-4">
                    <img src="{{ $url }}" alt="{{ $label }}" class="max-h-[70vh] w-auto rounded-xl object-contain shadow">
                </div>
            @endif
        </div>
    </div>
</div>
