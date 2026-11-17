@props(['label' => null, 'description' => null])

<label {{ $attributes->only('class')->merge(['class' => 'flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3']) }}>
    <span>
        @if($label)
            <span class="block text-sm font-semibold text-slate-700">{{ $label }}</span>
        @endif
        @if($description)
            <span class="block text-xs text-slate-500">{{ $description }}</span>
        @endif
    </span>

    <span class="relative inline-flex shrink-0">
        <input type="checkbox" {{ $attributes->except(['label', 'description'])->merge(['class' => 'peer sr-only']) }}>
        <span class="h-6 w-11 rounded-full bg-slate-200 transition peer-checked:bg-blue-600"></span>
        <span class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
    </span>
</label>
