@props(['label' => null, 'preview' => null, 'accept' => 'image/*', 'error' => null, 'hint' => null, 'type' => 'image'])

<div class="space-y-2">
    @if($label)
        <label class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
    @endif

    @if($preview)
        <div class="flex items-start gap-3">
            @if($type === 'pdf')
                <div class="flex h-16 w-16 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xs font-bold text-red-600">PDF</div>
            @else
                <img src="{{ $preview }}" alt="Preview" class="h-16 w-16 rounded-xl border border-slate-200 object-cover">
            @endif
            <div class="text-xs text-slate-500">
                <p class="font-semibold text-slate-700">Preview</p>
                <p>File akan tampil setelah disimpan/diupload.</p>
            </div>
        </div>
    @endif

    <input type="file" accept="{{ $accept }}" {{ $attributes->except(['label', 'preview', 'accept', 'error', 'hint', 'type'])->merge([
        'class' => 'block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white hover:file:bg-blue-700',
    ]) }}>

    <div x-data="{ progress: 0, uploading: false }"
         x-on:livewire-upload-start="uploading = true; progress = 0"
         x-on:livewire-upload-progress="progress = $event.detail.progress"
         x-on:livewire-upload-finish="uploading = false; progress = 100"
         x-on:livewire-upload-error="uploading = false"
         x-show="uploading" x-cloak class="space-y-1">
        <div class="h-2 overflow-hidden rounded-full bg-slate-200">
            <div class="h-full bg-blue-600 transition-all" :style="`width: ${progress}%`"></div>
        </div>
        <p class="text-[11px] font-medium text-slate-500">Upload <span x-text="progress"></span>%</p>
    </div>

    @if($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="text-xs font-medium text-red-600">{{ $error }}</p>
    @endif
</div>
