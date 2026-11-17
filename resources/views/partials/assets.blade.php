<script src="{{ asset('js/ia-progress.js') }}?v={{ @filemtime(public_path('js/ia-progress.js')) }}"></script>
@if (config('app.asset_mode') === 'cdn')
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="{{ asset('css/app-custom.css') }}?v={{ @filemtime(public_path('css/app-custom.css')) }}">

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/id.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
    <script type="module" src="{{ asset('cdn/app.js') }}?v={{ @filemtime(public_path('cdn/app.js')) }}"></script>
@else
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
