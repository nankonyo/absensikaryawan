@props(['active' => false])

<a {{ $attributes->merge(['class' => 'flex items-center gap-3 px-4 py-3 rounded-lg transition ' . ($active ? 'bg-blue-50 text-blue-600' : 'text-gray-700 hover:bg-gray-100')]) }}>
    {{ $slot }}
</a>
