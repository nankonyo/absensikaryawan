@props(['active' => false])

<a wire:navigate {{ $attributes->merge([
    'class' => 'group menu-link flex items-center justify-between gap-3 rounded-xl p-2.5 text-sm font-medium transition-all '
        . ($active
            ? 'bg-blue-50/80 text-blue-600'
            : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900'),
]) }}>
    <span class="flex min-w-0 items-center gap-3 [&>svg]:h-5 [&>svg]:w-5 [&>svg]:shrink-0 {{ $active ? '[&>svg]:text-blue-600' : '[&>svg]:text-gray-500 group-hover:[&>svg]:text-gray-800' }}">
        {{ $slot }}
    </span>
</a>
