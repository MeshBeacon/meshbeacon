<x-layouts::app :title="__('Dashboard')">
@section('content')
{{-- Active Incidents panel - moved to the top: this is the most actionable,
     time-critical widget (open SOS alerts needing acknowledgment), so it
     should be visible immediately rather than below passive/reference
     widgets. Header row stacks vertically on mobile so the action buttons
     don't overflow narrow screens. --}}
<div id="incidents" class="mt-6 mb-2">
  @if (config('services.central_dms.dashboard_readonly'))
    <div class="mb-3 flex items-center gap-2 rounded-md bg-blue-500/10 px-3 py-2 text-xs text-blue-300 ring-1 ring-inset ring-blue-500/30">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0">
        <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm.75-10a.75.75 0 0 0-1.5 0v4c0 .199.079.39.22.53l2.5 2.5a.75.75 0 1 0 1.06-1.06L8.75 8.94V5Z" clip-rule="evenodd" />
      </svg>
      {{ __('Read-only monitoring instance - incident dispatch happens at the field site, not here.') }}
    </div>
  @endif
  <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-3">
    <div class="flex items-center gap-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Active Incidents') }}</h2>
      <span id="incidents-count" class="hidden inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-500/30"></span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      @unless (config('services.central_dms.dashboard_readonly'))
      <button id="bulk-ack-btn"
        class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 dark:bg-white/5 px-2.5 py-1 text-xs font-medium text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-white/10 hover:bg-gray-200 dark:bg-white/10 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
          <path fill-rule="evenodd" d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14Zm3.844-8.791a.75.75 0 0 0-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 1 0-1.114 1.004l2.25 2.5a.75.75 0 0 0 1.15-.043l4.25-5.5Z" clip-rule="evenodd" />
        </svg>
        {{ __('Acknowledge All') }}
      </button>
      @endunless
      <button id="notif-btn"
        onclick="requestNotificationPermission()"
        class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 dark:bg-white/5 px-2.5 py-1 text-xs font-medium text-gray-500 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-white/10 hover:bg-gray-200 dark:bg-white/10 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
          <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        {{ __('Enable Notifications') }}
      </button>
    </div>
  </div>
  <div class="relative mb-3 max-w-sm">
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-gray-500 dark:text-gray-400">
        <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
      </svg>
    </div>
    <input id="incidents-search" type="text" placeholder="{{ __('Search duck, notes, assignee…') }}"
      class="w-full rounded-md min-w-0 bg-gray-100 dark:bg-white/5 pl-9 pr-3 py-1.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-500 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-orange-500" />
  </div>
  <div id="incidents-list" class="@container">
    <p class="text-xs text-gray-500 italic">{{ __('Loading…') }}</p>
  </div>
</div>

{{-- Overview - combined device counts + incident SLA into a single KPI
     strip instead of two separate stat grids, to reduce visual duplication.
     2 columns on mobile, up to 6 across on large screens. --}}
<div class="mt-6">
  <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-3">{{ __('Overview') }}</h2>
  <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-6" id="sla-stats">
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('PapaDucks') }}</dt>
      <dd id="stat-papaducks" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $papaducks }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('MamaDucks') }}</dt>
      <dd id="stat-mamaducks" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $mamaducks }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Messages') }}</dt>
      <dd id="stat-total" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $count }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. time to acknowledge') }}</dt>
      <dd id="sla-avg-ack" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. time to resolve') }}</dt>
      <dd id="sla-avg-resolve" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Open / Resolved') }}</dt>
      <dd id="sla-open-resolved" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
    </div>
  </dl>
</div>

        {{-- Duck Health --}}
        <div class="mt-6">
          <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Duck Health') }}</h2>
            <span class="relative flex size-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex size-2 rounded-full bg-green-500"></span>
            </span>
          </div>
          <div id="duck-health-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-2">
            <p class="text-xs text-gray-500 italic col-span-full">{{ __('Loading…') }}</p>
          </div>
        </div>

        <div class="mt-6">
          <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Duck Positions') }}</h2>
            <span class="relative flex size-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex size-2 rounded-full bg-green-500"></span>
            </span>
          </div>
          <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
          <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
          <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
          <div id="duck-map" class="rounded-lg overflow-hidden ring-1 ring-inset ring-gray-200 dark:ring-white/10 h-64 sm:h-96 lg:h-[420px]"></div>
          <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
          <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
          <script>
          (function () {
            var map = L.map('duck-map', { zoomControl: true }).setView([3.139, 101.6869], 6);

            var localUrl = '/tiles/{z}/{x}/{y}.png';
            var osmUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            var hasOfflineMap = {{ file_exists(config('services.map.mbtiles_path')) && !file_exists(storage_path('app/use_osm_map.flag')) ? 'true' : 'false' }};
            
            <?php
            $maxNativeZoom = app(\App\Services\MbtilesService::class)->getMaxNativeZoom(config('services.map.mbtiles_path'));
            ?>
            var tileLayer = L.tileLayer(hasOfflineMap ? localUrl : (navigator.onLine ? osmUrl : localUrl), {
              maxZoom: 19,
              maxNativeZoom: hasOfflineMap ? {{ $maxNativeZoom }} : 19,
              attribution: '&copy; MeshBeacon / OpenStreetMap'
            }).addTo(map);

            if (!hasOfflineMap) {
                window.addEventListener('offline', function() {
                    tileLayer.setUrl(localUrl);
                });
                window.addEventListener('online', function() {
                    tileLayer.setUrl(osmUrl);
                });
            }

            var clusterGroup = L.markerClusterGroup();
            map.addLayer(clusterGroup);

            var lastBounds = null;
            var initialFitDone = false;

            // Recenter control
            var RecenterControl = L.Control.extend({
              options: { position: 'topleft' },
              onAdd: function () {
                var btn = L.DomUtil.create('button', '');
                btn.title = 'Recenter map';
                btn.style.cssText = 'width:30px;height:30px;background:#fff;border:2px solid rgba(0,0,0,.2);border-radius:4px;cursor:pointer;display:flex;align-items:center;justify-content:center;padding:0;';
                btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:16px;height:16px;color:#333"><path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-2.079 3.51-4.793 3.51-8.227 0-4.589-3.514-8.1-7.8-8.1S4.2 4.76 4.2 9.34c0 3.434 1.566 6.148 3.51 8.227a19.58 19.58 0 0 0 2.684 2.282 16.975 16.975 0 0 0 1.144.742ZM12 13.45a4.11 4.11 0 1 0 0-8.22 4.11 4.11 0 0 0 0 8.22Z" clip-rule="evenodd"/></svg>';
                L.DomEvent.on(btn, 'click', L.DomEvent.stopPropagation);
                L.DomEvent.on(btn, 'click', function () {
                  if (lastBounds && lastBounds.length) {
                    map.fitBounds(lastBounds, { maxZoom: 14, padding: [40, 40] });
                  } else {
                    map.setView([3.139, 101.6869], 6);
                  }
                });
                return btn;
              }
            });
            new RecenterControl().addTo(map);

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

                lastBounds = bounds;

                // Only auto-fit on the first successful load; subsequent refreshes
                // preserve the user's current pan/zoom position.
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

        {{-- Live Activity: hourly message-volume chart + trend indicator
             (rendered by the script in layouts/app.blade.php via #area-chart /
             #chart-trend), plus the raw incoming-message feed. Kept below the
             map rather than at the very top of the page, since incidents/
             overview/health/position are higher-priority for at-a-glance
             situational awareness; this is supporting detail. --}}
        <div class="mt-6">
          <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Live Activity') }}</h2>
            <span class="relative flex size-2">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
              <span class="relative inline-flex size-2 rounded-full bg-green-500"></span>
            </span>
          </div>
          <div class="flex flex-col lg:flex-row">
            <div class="max-w w-full bg-transparent border border-gray-200 dark:border-white/10 rounded-base shadow-xs p-4 md:p-6">
              <div class="flex justify-between items-start">
                <div>
                  <h5 id="stat-messages-today" class="text-2xl font-semibold text-heading text-gray-900 dark:text-white">{{ Number::forHumans($count) }}</h5>
                  <p class="text-body text-gray-500 dark:text-gray-400">{{ __('Messages today') }}</p>
                </div>
                <div id="chart-trend" class="flex items-center px-2.5 py-0.5 font-medium text-gray-500 dark:text-gray-400 text-center">
                  <svg class="w-5 h-5 animate-pulse" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"/></svg>
                  <span class="ml-1">…</span>
                </div>
              </div>
              <div id="area-chart"></div>
            </div>

            <div class="flow-root w-full lg:w-140 bg-transparent border border-gray-200 dark:border-white/10 m-4 rounded-base shadow-xs p-6 overflow-hidden">
              <ul role="list" class="-mb-8">
              </ul>
            </div>
          </div>
        </div>

        {{-- Trends (folded in from the standalone /analytics page, which was
             thin content-wise and duplicated the same duck telemetry shown
             above). Adds duck + time-range filtering that the old page lacked. --}}
        <div class="mt-6">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Trends') }}</h2>
            <div class="flex flex-wrap items-center gap-2">
              <el-autocomplete class="relative block">
                <input id="trends-duck-filter" type="text" autocomplete="off" placeholder="{{ __('All Ducks') }}"
                  class="block w-36 rounded-md bg-gray-100 dark:bg-white/5 pl-2 pr-7 py-1 text-xs text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-orange-500" />
                <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="pointer-events-none absolute right-2 top-1/2 size-3.5 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                  <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
                </svg>
                <el-options id="trends-duck-options" anchor="bottom start" popover class="m-0 max-h-60 w-[var(--input-width)] overflow-auto rounded-md bg-white dark:bg-gray-800 p-0 py-1 text-xs outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 empty:hidden [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete]">
                  <el-option value="{{ __('All Ducks') }}" class="group/option relative cursor-default select-none py-1.5 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-orange-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
                    <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('All Ducks') }}</span>
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-orange-600 dark:text-orange-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden">
                      <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </el-option>
                </el-options>
              </el-autocomplete>
              <div class="inline-flex rounded-md ring-1 ring-inset ring-gray-200 dark:ring-white/10 overflow-hidden">
                <button type="button" data-hours="24" class="trends-range-btn px-2.5 py-1 text-xs font-medium bg-gray-200 dark:bg-gray-950/50 text-gray-900 dark:text-white">{{ __('24h') }}</button>
                <button type="button" data-hours="168" class="trends-range-btn px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">{{ __('7d') }}</button>
                <button type="button" data-hours="720" class="trends-range-btn px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">{{ __('30d') }}</button>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-transparent rounded-base p-5 border border-gray-200 dark:border-white/10 shadow-xs">
              <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">{{ __('Battery Levels Over Time') }}</h3>
              <div id="trends-battery-chart" class="w-full h-72"></div>
            </div>
            <div class="bg-transparent rounded-base p-5 border border-gray-200 dark:border-white/10 shadow-xs">
              <h3 class="text-base font-medium text-gray-900 dark:text-white mb-4">{{ __('Signal Strength (RSSI)') }}</h3>
              <div id="trends-rssi-chart" class="w-full h-72"></div>
            </div>
          </div>
        </div>
        <script type="module">
        (function () {
          const batteryEl = document.getElementById('trends-battery-chart');
          const rssiEl = document.getElementById('trends-rssi-chart');
          if (!batteryEl || !rssiEl || typeof ApexCharts === 'undefined') return;

          const computedStyle = getComputedStyle(document.documentElement);
          const brandColor = computedStyle.getPropertyValue('--color-fg-brand').trim() || "#1447E6";
          const successColor = computedStyle.getPropertyValue('--color-fg-success').trim() || "#10B981";

          const isDarkMode = () => document.documentElement.classList.contains('dark');

          function themePalette() {
            const dark = isDarkMode();
            return {
              grid: dark ? '#374151' : '#e5e7eb',
              label: dark ? '#9ca3af' : '#4b5563',
              legend: dark ? '#d1d5db' : '#374151',
              mode: dark ? 'dark' : 'light',
            };
          }

          function buildCommonOptions() {
            const palette = themePalette();
            return {
              chart: {
                height: 280,
                type: "line",
                fontFamily: "Inter, sans-serif",
                dropShadow: { enabled: false },
                toolbar: { show: false },
                animations: { enabled: true },
                background: 'transparent',
                // Locks the Battery and RSSI charts together so zooming/panning
                // the time axis on one applies the same range to the other.
                group: 'trends-sync'
              },
              stroke: { width: 3, curve: 'smooth' },
              grid: {
                show: true,
                borderColor: palette.grid,
                strokeDashArray: 4,
                padding: { left: 10, right: 10, top: 0, bottom: 0 },
              },
              xaxis: {
                type: 'datetime',
                labels: {
                  style: { colors: palette.label, fontSize: '11px' },
                  // ApexCharts defaults to formatting datetime axes in UTC
                  // regardless of the browser's timezone. Disable that so
                  // labels/tooltips reflect local (Asia/Kuala_Lumpur) time.
                  datetimeUTC: false,
                  datetimeFormatter: {
                    year: 'yyyy',
                    month: 'MMM \'yy',
                    day: 'dd MMM',
                    hour: 'HH:mm'
                  }
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
              },
              yaxis: {
                labels: {
                  style: { colors: palette.label, fontSize: '11px' },
                }
              },
              legend: {
                labels: { colors: palette.legend },
                position: 'top',
              },
              theme: { mode: palette.mode },
              tooltip: {
                theme: palette.mode,
                x: { format: 'dd MMM HH:mm' }
              },
              noData: {
                text: '{{ __('Loading…') }}',
                style: { color: palette.label, fontSize: '14px' },
              }
            };
          }

          const commonOptions = buildCommonOptions();

          const batteryChart = new ApexCharts(batteryEl, {
            ...commonOptions,
            chart: { ...commonOptions.chart, id: 'trends-battery-chart' },
            colors: [successColor, '#F59E0B', '#3B82F6', '#EC4899', '#8B5CF6'],
            yaxis: { ...commonOptions.yaxis, min: 0, max: 100, tickAmount: 5 }
          });
          batteryChart.render();

          const rssiChart = new ApexCharts(rssiEl, {
            ...commonOptions,
            chart: { ...commonOptions.chart, id: 'trends-rssi-chart' },
            colors: [brandColor, '#10B981', '#F59E0B', '#EC4899', '#8B5CF6'],
            yaxis: { ...commonOptions.yaxis, min: -130, max: 0, tickAmount: 5, reversed: false }
          });
          rssiChart.render();

          // Chart backgrounds/borders follow Tailwind's `dark:` classes automatically,
          // but ApexCharts renders its grid/axis/legend/tooltip colors into inline
          // SVG at creation time, so they don't react to the theme toggle on their
          // own. Watch for the `dark` class flipping on <html> and re-apply.
          const applyThemeToCharts = () => {
            const opts = buildCommonOptions();
            const themedOptions = {
              grid: opts.grid,
              xaxis: { labels: opts.xaxis.labels },
              yaxis: { labels: opts.yaxis.labels },
              legend: opts.legend,
              theme: opts.theme,
              tooltip: opts.tooltip,
              noData: opts.noData,
            };
            batteryChart.updateOptions(themedOptions, false, false);
            rssiChart.updateOptions(themedOptions, false, false);
          };
          new MutationObserver(applyThemeToCharts).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

          let currentHours = 24;
          let currentDuck = '';

          function loadTrendsData() {
            const params = new URLSearchParams({ hours: currentHours });
            if (currentDuck) params.set('duck_id', currentDuck);

            fetch('/dashboard/trends-data?' + params.toString(), {
              headers: { 'Accept': 'application/json' }
            })
              .then((r) => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
              .then((json) => {
                if (json.batterySeries && json.batterySeries.length) {
                  batteryChart.updateSeries(json.batterySeries);
                } else {
                  batteryChart.updateSeries([]);
                  batteryChart.updateOptions({ noData: { text: '{{ __('No battery data available') }}' } });
                }

                if (json.rssiSeries && json.rssiSeries.length) {
                  rssiChart.updateSeries(json.rssiSeries);
                } else {
                  rssiChart.updateSeries([]);
                  rssiChart.updateOptions({ noData: { text: '{{ __('No RSSI data available') }}' } });
                }
              })
              .catch((e) => console.error('Failed to load trends data:', e));
          }

          document.querySelectorAll('.trends-range-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
              currentHours = parseInt(btn.dataset.hours, 10);
              document.querySelectorAll('.trends-range-btn').forEach((b) => {
                b.classList.remove('bg-gray-200', 'dark:bg-gray-950/50', 'text-gray-900', 'dark:text-white');
                b.classList.add('text-gray-600', 'dark:text-gray-300');
              });
              btn.classList.add('bg-gray-200', 'dark:bg-gray-950/50', 'text-gray-900', 'dark:text-white');
              btn.classList.remove('text-gray-600', 'dark:text-gray-300');
              loadTrendsData();
            });
          });

          const duckFilter = document.getElementById('trends-duck-filter');
          const duckOptionsList = document.getElementById('trends-duck-options');
          // The 'All Ducks' option must use a non-empty value ({{ __('All Ducks') }}
          // as text) because the underlying el-autocomplete element ignores clicks
          // on options with an empty string value, so it maps back to '' here.
          const ALL_DUCKS_VALUE = @json(__('All Ducks'));
          duckFilter.addEventListener('change', () => {
            currentDuck = duckFilter.value === ALL_DUCKS_VALUE ? '' : duckFilter.value;
            loadTrendsData();
          });

          // Populated by renderDuckHealth() in app.js, reusing the dashboard's
          // existing duck-health poll instead of firing a second request just
          // for the option list.
          window.updateTrendsDuckOptions = function (ducks) {
            const dynamicOptions = Array.from(duckOptionsList.querySelectorAll('el-option[data-dynamic]'));
            const incoming = ducks.map((d) => d.duck_id).sort().join(',');
            const existing = dynamicOptions.map((o) => o.getAttribute('value')).sort().join(',');
            if (incoming === existing) return;

            const current = duckFilter.value;
            dynamicOptions.forEach((o) => o.remove());
            ducks.forEach((d) => {
              const opt = document.createElement('el-option');
              opt.setAttribute('value', d.duck_id);
              opt.dataset.dynamic = '1';
              opt.className = 'group/option relative cursor-default select-none py-1.5 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-orange-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block';
              const label = document.createElement('span');
              label.className = 'block truncate font-normal group-aria-selected/option:font-semibold';
              label.textContent = d.duck_id;
              opt.appendChild(label);
              const check = document.createElement('span');
              check.className = "absolute inset-y-0 right-0 flex items-center pr-3 text-orange-600 dark:text-orange-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden";
              check.innerHTML = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>';
              opt.appendChild(check);
              duckOptionsList.appendChild(opt);
            });

            if (current && !ducks.some((d) => d.duck_id === current)) {
              duckFilter.value = '';
              currentDuck = '';
              loadTrendsData();
            }
          };

          loadTrendsData();
          setInterval(loadTrendsData, 60000);
        })();
        </script>

        {{-- Mesh Topology --}}
        <div class="mt-6">
          <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Recent Relay Paths') }}</h2>
          </div>
          <div id="topology-list">
            <p class="text-xs text-gray-500 italic">{{ __('Loading…') }}</p>
          </div>
        </div>
@endsection
</x-layouts::app>
