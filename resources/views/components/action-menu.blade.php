@props(['height' => 132])

<div class="relative inline-block text-left" x-data="{ open: false, top: 0, left: 0 }">
    <button
        type="button"
        class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-500 hover:bg-gray-100 hover:text-gray-700"
        aria-label="{{ __('ui.actions') }}"
        :aria-expanded="open"
        @click="
            const rect = $el.getBoundingClientRect();
            const menuHeight = {{ (int) $height }};

            open = ! open;
            top = rect.bottom + menuHeight > window.innerHeight
                ? rect.top - menuHeight - 4
                : rect.bottom + 4;
            left = Math.max(8, Math.min(rect.right - 192, window.innerWidth - 200));
        "
        @keydown.escape.window="open = false"
    >
        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 11.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM10 17a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        :style="`top: ${top}px; left: ${left}px;`"
        class="fixed z-50 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5"
        style="display: none;"
    >
        {{ $slot }}
    </div>
</div>
