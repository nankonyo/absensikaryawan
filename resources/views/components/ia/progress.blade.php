<div x-data x-show="$store.iaProgress.visible" x-cloak class="pointer-events-none fixed inset-x-0 top-0 z-[100]">
    <div class="h-1 w-full bg-blue-100">
        <div class="h-full bg-blue-600 transition-all duration-200" :style="`width: ${$store.iaProgress.value}%`"></div>
    </div>
    <div class="absolute right-4 top-2 rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-bold text-white shadow"
         x-text="`${$store.iaProgress.value}%`"></div>
</div>
