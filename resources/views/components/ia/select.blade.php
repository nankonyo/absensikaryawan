@props(['label' => null, 'error' => null, 'hint' => null])

<div class="space-y-1.5">
    @if($label)
        <label class="block text-sm font-semibold text-slate-700">{{ $label }}</label>
    @endif

    <select {{ $attributes->except(['label', 'error', 'hint'])->merge([
        'class' => 'ia-select w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-100',
    ]) }}>
        {{ $slot }}
    </select>

    @if($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="text-xs font-medium text-red-600">{{ $error }}</p>
    @endif
</div>
