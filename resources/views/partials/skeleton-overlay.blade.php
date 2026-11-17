<div id="ia-skeleton-overlay" class="absolute inset-0 z-20 hidden overflow-y-auto bg-slate-50/95 backdrop-blur-sm">
    <div class="space-y-5 p-4 lg:p-6">
        <div class="space-y-2">
            <div class="ia-skeleton h-7 w-56 rounded-lg"></div>
            <div class="ia-skeleton h-3 w-80 max-w-full rounded-md"></div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="ia-skeleton h-3 w-24 rounded"></div>
                    <div class="ia-skeleton mt-3 h-8 w-16 rounded-lg"></div>
                    <div class="ia-skeleton mt-3 h-3 w-full rounded"></div>
                </div>
            @endfor
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <div class="ia-skeleton h-4 w-40 rounded"></div>
                <div class="ia-skeleton h-8 w-24 rounded-lg"></div>
            </div>
            <div class="space-y-3">
                @for ($i = 0; $i < 6; $i++)
                    <div class="flex items-center gap-4">
                        <div class="ia-skeleton h-4 w-4 rounded-full"></div>
                        <div class="ia-skeleton h-4 flex-1 rounded"></div>
                        <div class="ia-skeleton h-4 w-24 rounded"></div>
                        <div class="ia-skeleton h-4 w-16 rounded"></div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>
