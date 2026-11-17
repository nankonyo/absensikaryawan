@props(['lines' => 3])

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    @for($i = 0; $i < $lines; $i++)
        <div class="ia-skeleton h-4 rounded-md" style="width: {{ [100, 92, 74, 84][$i % 4] }}%"></div>
    @endfor
</div>
