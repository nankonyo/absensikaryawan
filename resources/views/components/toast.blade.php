@php
    $toasts = collect(['success', 'error', 'warning', 'info'])
        ->filter(fn (string $type) => session()->has($type))
        ->map(fn (string $type) => [
            'message' => session($type),
            'duration' => in_array($type, ['error', 'warning'], true) ? 5000 : 3000,
        ])
        ->values();
@endphp

<div id="toast-root" class="pointer-events-none fixed inset-x-0 bottom-6 z-[60] flex flex-col items-center gap-2 px-4"></div>

<script>
    (() => {
        const root = document.getElementById('toast-root');

        window.showToast = (message, duration = 3000) => {
            if (!message || !root) {
                return;
            }

            const toast = document.createElement('div');
            toast.textContent = message;
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.className = 'pointer-events-auto max-w-full rounded-full bg-gray-900/80 px-5 py-3 text-center text-sm font-medium text-white shadow-lg backdrop-blur transition-opacity duration-300';

            root.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, duration);
        };

        @json($toasts).forEach((toast) => window.showToast(toast.message, toast.duration));
    })();
</script>
