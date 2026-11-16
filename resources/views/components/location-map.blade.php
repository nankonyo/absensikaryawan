@props(['latitude', 'longitude', 'label'])

@php
    $lat = (float) $latitude;
    $lng = (float) $longitude;
    $mapId = 'map-'.uniqid();
@endphp

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endonce

<div class="isolate">
    <p class="text-sm text-gray-500 mb-2">{{ $label }}</p>
    <div id="{{ $mapId }}" class="relative z-0 w-full h-64 rounded-lg border border-gray-200"></div>
    <script>
        (function () {
            var map = L.map(@json($mapId), {
                attributionControl: false,
                zoomControl: true,
            }).setView([{{ $lat }}, {{ $lng }}], 16);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(map);

            L.marker([{{ $lat }}, {{ $lng }}]).addTo(map);
        })();
    </script>
    <a href="https://www.google.com/maps/search/?api=1&query={{ $lat }},{{ $lng }}"
       target="_blank" rel="noopener" class="mt-2 inline-block text-sm text-blue-600 hover:underline">
        {{ __('ui.view_on_map') }}
    </a>
</div>
