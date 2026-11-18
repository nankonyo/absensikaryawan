<script src="{{ asset('js/ia-progress.js') }}?v={{ @filemtime(public_path('js/ia-progress.js')) }}"></script>
<script src="{{ asset('js/alpine-spa.js') }}?v={{ @filemtime(public_path('js/alpine-spa.js')) }}"></script>
@if (config('app.asset_mode') === 'cdn')
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'Figtree', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        blue: {
                        50: '#FDF2F3',
                        100: '#FBE7E8',
                        200: '#F5C9CC',
                        300: '#EDA2A7',
                        400: '#E2757B',
                        500: '#D95B62',
                        600: '#D34B52',
                        700: '#B33C43',
                        800: '#943139',
                        900: '#7A2A31'
                        },
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
