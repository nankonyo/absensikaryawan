@props(['latitude', 'longitude', 'label'])

@php
    $lat = (float) $latitude;
    $lng = (float) $longitude;
    $mapId = 'map-'.uniqid();
@endphp

<div class="isolate">
    <p class="text-sm text-gray-500 mb-2">{{ $label }}</p>
    <div id="{{ $mapId }}" class="relative z-0 w-full h-64 rounded-lg border border-gray-200 bg-slate-100" style="height:16rem"></div>
    <script>
        (function () {
            var mapId = @json($mapId);
            var lat = {{ $lat }};
            var lng = {{ $lng }};
            var cssUrl = @json(asset('cdn/leaflet/leaflet.css'));
            var jsUrl = @json(asset('cdn/leaflet/leaflet.js'));

            function mount() {
                var el = document.getElementById(mapId);

                if (!el || !window.L) {
                    return;
                }

                var map = window.L.map(mapId, {
                    attributionControl: false,
                    zoomControl: true,
                }).setView([lat, lng], 16);

                window.L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                }).addTo(map);

                window.L.marker([lat, lng]).addTo(map);

                // Layout/SPA kadang menghitung ukuran container setelah render.
                setTimeout(function () { map.invalidateSize(); }, 200);
            }

            function loadLeaflet() {
                if (window.L) {
                    mount();
                    return;
                }

                if (!window.__leafletLoader) {
                    window.__leafletLoader = new Promise(function (resolve, reject) {
                        if (!document.querySelector('link[data-leaflet-css]')) {
                            var link = document.createElement('link');
                            link.rel = 'stylesheet';
                            link.href = cssUrl;
                            link.setAttribute('data-leaflet-css', '1');
                            document.head.appendChild(link);
                        }

                        var script = document.createElement('script');
                        script.src = jsUrl;
                        script.async = true;
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }

                window.__leafletLoader.then(mount).catch(function () {
                    var el = document.getElementById(mapId);
                    if (el) {
                        el.innerHTML = '<div class="flex h-full items-center justify-center p-4 text-center text-sm text-gray-500">Peta tidak dapat dimuat. Gunakan tautan Google Maps di bawah.</div>';
                    }
                });
            }

            loadLeaflet();
        })();
    </script>
    <a href="https://www.google.com/maps/search/?api=1&query={{ $lat }},{{ $lng }}"
       target="_blank" rel="noopener" class="mt-2 inline-block text-sm text-blue-600 hover:underline">
        {{ __('ui.view_on_map') }}
    </a>
</div>
