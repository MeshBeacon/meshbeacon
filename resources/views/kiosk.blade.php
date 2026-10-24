<!DOCTYPE html>
<html class="h-full bg-gray-50 dark:bg-gray-900">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script>
    // Must match layouts/app.blade.php's meshBeaconApplyTheme() exactly:
    // defaults to DARK unless the user has explicitly chosen light. The
    // previous version here defaulted to light unless the OS/browser
    // reported `prefers-color-scheme: dark`, which disagreed with every
    // other page (same localStorage, no stored "theme" key) and made kiosk
    // show light mode on first visit while the rest of the app showed dark.
    if (localStorage.theme === 'light') {
      document.documentElement.classList.remove('dark');
    } else {
      document.documentElement.classList.add('dark');
    }
  </script>
  {{-- Shared raster tile source config for the MapLibre-based duck-position
       map below. This page doesn't extend layouts.app (it's a standalone
       unattended-display document), so it has to compute and expose this
       itself -- without it, window.createMeshBeaconMap() falls back to an
       empty {} config and the raster source's `tiles` ends up undefined,
       so the basemap never renders. Mirrors layouts/app.blade.php exactly. --}}
  @php
    $mbtilesPath = config('services.map.mbtiles_path');
    $mbtilesService = app(\App\Services\MbtilesService::class);
    $hasOfflineMap = file_exists($mbtilesPath)
        && !file_exists(storage_path('app/use_osm_map.flag'))
        && $mbtilesService->isVectorTileset($mbtilesPath);
    $maxNativeZoom = $mbtilesService->getMaxNativeZoom($mbtilesPath);
  @endphp
  <script>
    window.MESHBEACON_MAP_CONFIG = {
      localUrl: '/tiles/{z}/{x}/{y}.pbf',
      osmUrls: [
        'https://a.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'https://b.tile.openstreetmap.org/{z}/{x}/{y}.png',
        'https://c.tile.openstreetmap.org/{z}/{x}/{y}.png',
      ],
      hasOfflineMap: {{ $hasOfflineMap ? 'true' : 'false' }},
      maxNativeZoom: {{ $maxNativeZoom }},
    };
  </script>
  <title>{{ __('MeshBeacon — Kiosk') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 overflow-hidden">
<script src="{{ asset('vendor/tailwindplus-elements/elements.min.js') }}" type="module"></script>
<script src="{{ asset('vendor/jquery/jquery-3.0.0.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/flowbite/flowbite.min.js') }}"></script>
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>

<div class="flex h-screen flex-col p-4 gap-4">

  {{-- Top bar: branding, live clock, exit link. No interactive controls -
       this screen is meant to be viewed, not operated. --}}
  <div class="shrink-0">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="size-9">
        <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('MeshBeacon — Live Operations') }}</h1>
      </div>
      <div class="flex items-center gap-4">
        <span class="relative flex size-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex size-2.5 rounded-full bg-green-500"></span>
        </span>
        <div class="flex items-baseline gap-1">
          <span id="kiosk-clock-myt" class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white"></span>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">MYT</span>
        </div>
        <div class="flex items-baseline gap-1">
          <span id="kiosk-clock-utc" class="text-2xl font-semibold tabular-nums text-gray-900 dark:text-white"></span>
          <span class="text-sm font-medium text-gray-500 dark:text-gray-400">UTC</span>
        </div>
        @include('partials.locale-switcher')

        <button type="button" onclick="const t = document.documentElement.classList.contains('dark') ? 'light' : 'dark'; localStorage.theme = t; document.documentElement.classList.toggle('dark');" class="rounded-full bg-white dark:bg-gray-800 p-1 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white outline-none focus-visible:ring-2 focus-visible:ring-orange-500" title="{{ __('Toggle theme') }}">
          <span class="sr-only">{{ __('Toggle dark mode') }}</span>
          <svg class="size-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
          </svg>
          <svg class="size-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
          </svg>
        </button>

        <a href="/dashboard" class="text-xs font-medium text-gray-500 dark:text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">{{ __('Exit kiosk') }}</a>
      </div>
    </div>
    <p class="text-xs text-gray-500 mt-1">
      {{ __('Unattended display: make sure you logged in here with "Remember me" checked so this screen stays signed in if it reboots or loses power.') }}
    </p>
  </div>

  {{-- Overview KPI strip --}}
  <dl class="grid grid-cols-6 gap-3 shrink-0" id="sla-stats">
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('PapaDucks') }}</dt>
      <dd id="stat-papaducks" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $papaducks }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('MamaDucks') }}</dt>
      <dd id="stat-mamaducks" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $mamaducks }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Total Messages') }}</dt>
      <dd id="stat-total" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $count }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. time to acknowledge') }}</dt>
      <dd id="sla-avg-ack" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. time to resolve') }}</dt>
      <dd id="sla-avg-resolve" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Open / Resolved') }}</dt>
      <dd id="sla-open-resolved" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
    </div>
  </dl>

  {{-- Main grid: Incidents | Map | Live Feed --}}
  {{-- auto-rows-fr is required here: with the default implicit `auto` row
       sizing, this single row sizes itself to its tallest child's CONTENT
       height rather than stretching to fill the remaining viewport height,
       so #duck-map (which has no intrinsic content/size of its own) ends up
       with ~0px height and MapLibre renders nothing. The incidents/live-feed
       columns don't visibly show this because their scrollable lists still
       render *something* even when squeezed. --}}
  <div class="grid grid-cols-12 gap-4 flex-1 min-h-0 auto-rows-fr">
    <div class="col-span-4 flex flex-col rounded-lg bg-white dark:bg-gray-800/75 ring-1 ring-inset ring-gray-200 dark:ring-white/10 p-4 min-h-0">
      <div class="flex items-center gap-3 mb-3 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Active Incidents') }}</h2>
        <span id="incidents-count" class="hidden inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400 ring-1 ring-inset ring-red-500/30"></span>
      </div>
      <div id="incidents-list" class="@container overflow-y-auto min-h-0" data-compact="true">
        <p class="text-xs text-gray-500 italic">{{ __('Loading…') }}</p>
      </div>
    </div>

    <div class="col-span-5 flex flex-col rounded-lg bg-white dark:bg-gray-800/75 ring-1 ring-inset ring-gray-200 dark:ring-white/10 p-4 min-h-0">
      <div class="flex items-center gap-3 mb-3 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Duck Positions') }}</h2>
      </div>
      <div id="duck-map" class="rounded-lg overflow-hidden ring-1 ring-inset ring-gray-200 dark:ring-white/10 flex-1 min-h-0"></div>
      <script>
      document.addEventListener('DOMContentLoaded', function () {
        var map = window.createMeshBeaconMap('duck-map', { center: [101.6869, 3.139], zoom: 6 });

        function colorFor(badge) {
          return badge === 'Satellite' ? '#22c55e'
               : badge === 'Phone'     ? '#3b82f6'
               : '#eab308';
        }

        var initialFitDone = false;

        function addDuckLayers(geojson) {
          map.addSource('duck-pins', {
            type: 'geojson',
            data: geojson,
            cluster: true,
            clusterMaxZoom: 13,
            clusterRadius: 50,
          });

          map.addLayer({
            id: 'duck-clusters',
            type: 'circle',
            source: 'duck-pins',
            filter: ['has', 'point_count'],
            paint: {
              'circle-color': '#eab308',
              'circle-radius': ['step', ['get', 'point_count'], 16, 10, 20, 50, 26],
              'circle-stroke-width': 2,
              'circle-stroke-color': '#fff',
            },
          });

          map.addLayer({
            id: 'duck-cluster-count',
            type: 'symbol',
            source: 'duck-pins',
            filter: ['has', 'point_count'],
            layout: { 'text-field': ['get', 'point_count_abbreviated'], 'text-size': 12 },
            paint: { 'text-color': '#1f2937' },
          });

          map.addLayer({
            id: 'duck-unclustered',
            type: 'circle',
            source: 'duck-pins',
            filter: ['!', ['has', 'point_count']],
            paint: {
              'circle-color': ['get', 'color'],
              'circle-radius': 6,
              'circle-stroke-width': 2,
              'circle-stroke-color': '#fff',
            },
          });

          map.on('click', 'duck-clusters', function (e) {
            var features = map.queryRenderedFeatures(e.point, { layers: ['duck-clusters'] });
            var clusterId = features[0].properties.cluster_id;
            map.getSource('duck-pins').getClusterExpansionZoom(clusterId).then(function (zoom) {
              map.easeTo({ center: features[0].geometry.coordinates, zoom: zoom });
            }).catch(function () {});
          });

          map.on('click', 'duck-unclustered', function (e) {
            var p = e.features[0].properties;
            var popupHtml = '<div style="min-width:160px;line-height:1.5">'
              + '<p style="font-weight:600;margin:0 0 2px">' + p.duck_id + '</p>'
              + '<p style="font-size:.75rem;color:#9ca3af;margin:0">' + Number(p.lat).toFixed(5) + ', ' + Number(p.lng).toFixed(5) + '</p>'
              + '<p style="font-size:.75rem;color:#9ca3af;margin:0">' + p.source + ' &nbsp;·&nbsp; ' + p.topic + '</p>'
              + '<p style="font-size:.75rem;color:#6b7280;margin:0">' + p.created_at + '</p>'
              + '<a href="' + p.map_url + '" target="_blank" rel="noopener" style="font-size:.75rem;color:#eab308">Open in Maps ↗</a>'
              + '</div>';
            new maplibregl.Popup().setLngLat(e.features[0].geometry.coordinates).setHTML(popupHtml).addTo(map);
          });

          ['duck-clusters', 'duck-unclustered'].forEach(function (layerId) {
            map.on('mouseenter', layerId, function () { map.getCanvas().style.cursor = 'pointer'; });
            map.on('mouseleave', layerId, function () { map.getCanvas().style.cursor = ''; });
          });
        }

        function refreshMap() {
          fetch('/dashboard/map-pins', {
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
          })
          .then(function (r) { return r.json(); })
          .then(function (pins) {
            var features = [];
            var bounds = null;

            pins.forEach(function (duck) {
              var lat = duck.lat;
              var lng = duck.lng;
              if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

              features.push({
                type: 'Feature',
                geometry: { type: 'Point', coordinates: [lng, lat] },
                properties: {
                  duck_id: duck.duck_id,
                  lat: lat,
                  lng: lng,
                  source: duck.source,
                  topic: duck.topic,
                  created_at: duck.created_at,
                  map_url: duck.map_url,
                  color: colorFor(duck.source),
                },
              });

              bounds = bounds || new maplibregl.LngLatBounds();
              bounds.extend([lng, lat]);
            });

            var geojson = { type: 'FeatureCollection', features: features };
            var source = map.getSource('duck-pins');
            if (source) {
              source.setData(geojson);
            } else {
              addDuckLayers(geojson);
            }

            if (!initialFitDone) {
              initialFitDone = true;
              if (bounds) {
                map.fitBounds(bounds, { maxZoom: 14, padding: 40 });
              }
            }
          })
          .catch(function (e) { console.error('Map refresh error', e); });
        }

        map.on('load', function () {
          refreshMap();
          setInterval(refreshMap, 30000);
        });

        // Safety net: if the container's size changes after load (e.g. the
        // grid row above still hadn't settled, or the kiosk window itself
        // is resized), make sure the canvas is re-measured.
        window.addEventListener('resize', function () { map.resize(); });
      });
      </script>
    </div>

    <div class="col-span-3 flex flex-col rounded-lg bg-white dark:bg-gray-800/75 ring-1 ring-inset ring-gray-200 dark:ring-white/10 p-4 min-h-0 overflow-hidden">
      <div class="flex items-center gap-3 mb-3 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Live Feed') }}</h2>
      </div>
      <div class="flow-root overflow-y-auto min-h-0">
        <ul role="list" class="-mb-8"></ul>
      </div>
    </div>
  </div>

  {{-- Bottom row: Duck Health | Mesh Topology --}}
  <div class="grid grid-cols-12 gap-4 shrink-0 max-h-48">
    <div class="col-span-6 flex flex-col rounded-lg bg-white dark:bg-gray-800/75 ring-1 ring-inset ring-gray-200 dark:ring-white/10 p-4 overflow-hidden @container">
      <div class="flex items-center gap-3 mb-2 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Duck Health') }}</h2>
      </div>
      <div id="duck-health-grid" class="grid grid-cols-3 @lg:grid-cols-4 @2xl:grid-cols-6 gap-2 overflow-y-auto">
        <p class="text-xs text-gray-500 italic col-span-full">{{ __('Loading…') }}</p>
      </div>
    </div>
    <div class="col-span-6 flex flex-col rounded-lg bg-white dark:bg-gray-800/75 ring-1 ring-inset ring-gray-200 dark:ring-white/10 p-4 overflow-hidden">
      <div class="flex items-center gap-3 mb-2 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Recent Relay Paths') }}</h2>
      </div>
      <div id="topology-list" class="overflow-y-auto">
        <p class="text-xs text-gray-500 italic">{{ __('Loading…') }}</p>
      </div>
    </div>
  </div>

  <footer class="mt-auto text-center text-xs text-gray-500 shrink-0">
    &copy; {{ date('Y') }} <a href="https://meshbeacon.org" target="_blank" rel="noopener" class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors">MeshBeacon</a>
  </footer>
</div>

<script>
  function updateKioskClock() {
    var elMyt = document.getElementById('kiosk-clock-myt');
    var elUtc = document.getElementById('kiosk-clock-utc');
    var now = new Date();
    if (elMyt) elMyt.textContent = now.toLocaleTimeString('en-GB', { timeZone: 'Asia/Kuala_Lumpur', hourCycle: 'h23' });
    if (elUtc) elUtc.textContent = now.toLocaleTimeString('en-GB', { timeZone: 'UTC', hourCycle: 'h23' });
  }
  updateKioskClock();
  setInterval(updateKioskClock, 1000);
</script>
</body>
</html>
