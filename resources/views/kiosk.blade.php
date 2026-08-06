<!DOCTYPE html>
<html class="h-full bg-gray-900">
<head>
  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ __('OpenDMS — Kiosk') }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-900 overflow-hidden">
<script src="{{ asset('vendor/tailwindplus-elements/elements.min.js') }}" type="module"></script>
<script src="{{ asset('vendor/jquery/jquery-3.0.0.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/flowbite/flowbite.min.js') }}"></script>
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>

<div class="flex h-screen flex-col p-4 gap-4">

  {{-- Top bar: branding, live clock, exit link. No interactive controls —
       this screen is meant to be viewed, not operated. --}}
  <div class="shrink-0">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-3">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="size-9">
        <h1 class="text-xl font-bold tracking-tight text-white">{{ __('OpenDMS — Live Operations') }}</h1>
      </div>
      <div class="flex items-center gap-4">
        <span class="relative flex size-2.5">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
          <span class="relative inline-flex size-2.5 rounded-full bg-green-500"></span>
        </span>
        <span id="kiosk-clock" class="text-2xl font-semibold tabular-nums text-white"></span>
        @include('partials.locale-switcher')
        <a href="/dashboard" class="text-xs font-medium text-gray-500 hover:text-gray-300">{{ __('Exit kiosk') }}</a>
      </div>
    </div>
    <p class="text-xs text-gray-500 mt-1">
      {{ __('Unattended display: make sure you logged in here with "Remember me" checked so this screen stays signed in if it reboots or loses power.') }}
    </p>
  </div>

  {{-- Overview KPI strip --}}
  <dl class="grid grid-cols-6 gap-3 shrink-0" id="sla-stats">
    <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-400">{{ __('PapaDucks') }}</dt>
      <dd id="stat-papaducks" class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $papaducks }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-400">{{ __('MamaDucks') }}</dt>
      <dd id="stat-mamaducks" class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $mamaducks }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-400">{{ __('Total Messages') }}</dt>
      <dd id="stat-total" class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $count }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-400">{{ __('Avg. time to acknowledge') }}</dt>
      <dd id="sla-avg-ack" class="mt-1 text-3xl font-semibold tracking-tight text-white">&mdash;</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-400">{{ __('Avg. time to resolve') }}</dt>
      <dd id="sla-avg-resolve" class="mt-1 text-3xl font-semibold tracking-tight text-white">&mdash;</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-3 shadow ring-1 ring-inset ring-white/10">
      <dt class="truncate text-xs font-medium text-gray-400">{{ __('Open / Resolved') }}</dt>
      <dd id="sla-open-resolved" class="mt-1 text-3xl font-semibold tracking-tight text-white">&mdash;</dd>
    </div>
  </dl>

  {{-- Main grid: Incidents | Map | Live Feed --}}
  <div class="grid grid-cols-12 gap-4 flex-1 min-h-0">
    <div class="col-span-4 flex flex-col rounded-lg bg-gray-800/75 ring-1 ring-inset ring-white/10 p-4 min-h-0">
      <div class="flex items-center gap-3 mb-3 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('Active Incidents') }}</h2>
        <span id="incidents-count" class="hidden inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-500/30"></span>
      </div>
      <div id="incidents-list" class="@container overflow-y-auto min-h-0" data-compact="true">
        <p class="text-xs text-gray-500 italic">{{ __('Loading…') }}</p>
      </div>
    </div>

    <div class="col-span-5 flex flex-col rounded-lg bg-gray-800/75 ring-1 ring-inset ring-white/10 p-4 min-h-0">
      <div class="flex items-center gap-3 mb-3 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('Duck Positions') }}</h2>
      </div>
      <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
      <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
      <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
      <div id="duck-map" class="rounded-lg overflow-hidden ring-1 ring-inset ring-white/10 flex-1 min-h-0"></div>
      <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
      <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
      <script>
      (function () {
        var map = L.map('duck-map', { zoomControl: true }).setView([3.139, 101.6869], 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        var clusterGroup = L.markerClusterGroup();
        map.addLayer(clusterGroup);

        var initialFitDone = false;

        function makeIcon(badge) {
          var color = badge === 'Satellite' ? '#22c55e'
                    : badge === 'Phone'     ? '#3b82f6'
                    : '#eab308';
          return L.divIcon({
            className: '',
            html: '<div style="width:12px;height:12px;border-radius:50%;background:' + color + ';border:2px solid #fff;box-shadow:0 0 4px rgba(0,0,0,.5);"></div>',
            iconSize:    [12, 12],
            iconAnchor:  [6, 6],
            popupAnchor: [0, -10],
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
            clusterGroup.clearLayers();
            var bounds = [];

            pins.forEach(function (duck) {
              var lat = duck.lat;
              var lng = duck.lng;
              if (!lat || !lng || isNaN(lat) || isNaN(lng)) return;

              var mapsLink = duck.map_url;
              var popup = '<div style="min-width:160px;line-height:1.5">'
                + '<p style="font-weight:600;margin:0 0 2px">' + duck.duck_id + '</p>'
                + '<p style="font-size:.75rem;color:#9ca3af;margin:0">' + lat.toFixed(5) + ', ' + lng.toFixed(5) + '</p>'
                + '<p style="font-size:.75rem;color:#9ca3af;margin:0">' + duck.source + ' &nbsp;·&nbsp; ' + duck.topic + '</p>'
                + '<p style="font-size:.75rem;color:#6b7280;margin:0">' + duck.created_at + '</p>'
                + '<a href="' + mapsLink + '" target="_blank" rel="noopener" style="font-size:.75rem;color:#eab308">Open in Maps ↗</a>'
                + '</div>';

              L.marker([lat, lng], { icon: makeIcon(duck.source) })
               .bindPopup(popup)
               .addTo(clusterGroup);

              bounds.push([lat, lng]);
            });

            if (!initialFitDone) {
              initialFitDone = true;
              if (bounds.length) {
                map.fitBounds(bounds, { maxZoom: 14, padding: [40, 40] });
              }
            }
          })
          .catch(function (e) { console.error('Map refresh error', e); });
        }

        refreshMap();
        setInterval(refreshMap, 30000);
      })();
      </script>
    </div>

    <div class="col-span-3 flex flex-col rounded-lg bg-gray-800/75 ring-1 ring-inset ring-white/10 p-4 min-h-0 overflow-hidden">
      <div class="flex items-center gap-3 mb-3 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('Live Feed') }}</h2>
      </div>
      <div class="flow-root overflow-y-auto min-h-0">
        <ul role="list" class="-mb-8"></ul>
      </div>
    </div>
  </div>

  {{-- Bottom row: Duck Health | Mesh Topology --}}
  <div class="grid grid-cols-12 gap-4 shrink-0 max-h-48">
    <div class="col-span-6 flex flex-col rounded-lg bg-gray-800/75 ring-1 ring-inset ring-white/10 p-4 overflow-hidden @container">
      <div class="flex items-center gap-3 mb-2 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('Duck Health') }}</h2>
      </div>
      <div id="duck-health-grid" class="grid grid-cols-3 @lg:grid-cols-4 @2xl:grid-cols-6 gap-2 overflow-y-auto">
        <p class="text-xs text-gray-500 italic col-span-full">{{ __('Loading…') }}</p>
      </div>
    </div>
    <div class="col-span-6 flex flex-col rounded-lg bg-gray-800/75 ring-1 ring-inset ring-white/10 p-4 overflow-hidden">
      <div class="flex items-center gap-3 mb-2 shrink-0">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('Recent Relay Paths') }}</h2>
      </div>
      <div id="topology-list" class="overflow-y-auto">
        <p class="text-xs text-gray-500 italic">{{ __('Loading…') }}</p>
      </div>
    </div>
  </div>

</div>

<script>
  function updateKioskClock() {
    var el = document.getElementById('kiosk-clock');
    if (!el) return;
    el.textContent = new Date().toLocaleTimeString('en-GB', { hourCycle: 'h23' });
  }
  updateKioskClock();
  setInterval(updateKioskClock, 1000);
</script>
</body>
</html>
