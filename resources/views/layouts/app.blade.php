@vite('resources/css/app.css')
@vite('resources/js/app.js')
<html class="h-full bg-gray-50 dark:bg-gray-900">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#1f2937">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script>
    // Single source of truth for the dark/light class on <html>. Defaults to
    // dark unless the user explicitly chose light. Runs on initial load AND
    // on every Livewire wire:navigate transition (via livewire:navigated) —
    // Livewire's SPA-style navigation morphs <html>'s class attribute against
    // the freshly-fetched *static* server HTML (which never contains the
    // dynamically-added "dark" token), silently stripping it on every soft
    // navigation unless it's explicitly reapplied here.
    function meshBeaconApplyTheme() {
      if (localStorage.theme === 'light') {
        document.documentElement.classList.remove('dark');
      } else {
        document.documentElement.classList.add('dark');
      }
    }
    meshBeaconApplyTheme();
    document.addEventListener('livewire:navigated', meshBeaconApplyTheme);
  </script>
  {{-- Shared raster tile source config for the MapLibre-based maps (Dashboard,
       Kiosk, Tracking, Messages, Status). Computed once here instead of being
       duplicated per-page; consumed by window.createMeshBeaconMap() in app.js. --}}
  @php
    $mbtilesPath = config('services.map.mbtiles_path');
    $mbtilesService = app(\App\Services\MbtilesService::class);
    // Only a Vector (PBF) MBTiles file is ever usable as the offline map (see
    // docs/OFFLINE_MAPS.md) — checking the declared format here, not just
    // file_exists(), means an incompatible/corrupt installed file falls back
    // to online tiles instead of breaking every map on the page.
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
  {{-- Translation strings needed by app.js's client-side live polling (duck
       card body, GPS warnings, Online/Offline badge, incident badges, and
       the message-modal history) — that content is rebuilt from plain JS
       template strings with no access to Laravel's __(), so every label it
       needs is exposed here instead. Keep in sync with the matching keys
       in lang/ms.json. --}}
  <script>
    window.MESHBEACON_I18N = {
      sent: @json(__('Sent')),
      received: @json(__('Received')),
      online: @json(__('Online')),
      offline: @json(__('Offline')),
      device: @json(__('Device')),
      gps: @json(__('GPS')),
      lastKnownLocation: @json(__('Last known location')),
      openInMaps: @json(__('Open in Maps')),
      copyCoordinates: @json(__('Copy coordinates')),
      noActiveIncidents: @json(__('No active incidents in the past 24 hours.')),
      noIncidentsMatchSearch: @json(__('No incidents match your search.')),
      incOpenFull: @json(__('Open')),
      incAcknowledgedFull: @json(__('Acknowledged')),
      incRespondingFull: @json(__('Responding')),
      incResolvedFull: @json(__('Resolved')),
      sosHw: @json(__('SOS HW')),
      nearestRelay: @json(__('Nearest relay')),
      requestGps: @json(__('Request GPS')),
      directToConcentrator: @json(__('Direct to concentrator')),
      noRelayPathRecorded: @json(__('No relay path recorded')),
      noMessage: @json(__('No message')),
      resendAck: @json(__('Re-send ACK')),
      acknowledgeAction: @json(__('Acknowledge')),
      respondingAction: @json(__('Responding')),
      resolvedAction: @json(__('Resolved')),
      assignedTo: @json(__('Assigned to')),
      unassigned: @json(__('Unassigned')),
      addNotePlaceholder: @json(__('Add a note…')),
      save: @json(__('Save')),
      incidentsCountOfTotal: @json(__(':count of :total')),
      incidentsMoreSuffix: @json(__('+:count more — see full dashboard for all active incidents')),
      sosHardwareFeedLabel: @json(__('SOS — Hardware Button')),
      sosMobileFeedLabel: @json(__('SOS — Mobile App')),
      urgency: @json(__('Urgency')),
      urgencyLow: @json(__('Low')),
      urgencyMedium: @json(__('Medium')),
      urgencyCritical: @json(__('Critical')),
      viewOnMap: @json(__('View on Map')),
      gpsCoordinates: @json(__('Coordinates')),
      gpsSatellitesInView: @json(__(':count satellites in view')),
      gpsLastKnownCoordinates: @json(__('Last known coordinates')),
      gpsNoFixDeviceActive: @json(__('No GPS fix — device active, no phone connected')),
      gpsNoFixPhoneUnavailable: @json(__('No GPS fix — phone GPS unavailable')),
      gpsNoFixNoSatellite: @json(__('No GPS fix — no satellite signal')),
      gpsNoReportsYet: @json(__('No GPS reports yet — tap Request to ask for its location.')),
      gpsNoLocationData: @json(__('No location data in this record.')),
      gpsLocationModalTitle: @json(__('GPS Location')),
      gpsSourceLabel: @json(__('Source:')),
      gpsSatellitesWord: @json(__('satellites')),
      openInOsm: @json(__('Open in OpenStreetMap')),
      close: @json(__('Close')),
      incidentLocation: @json(__('Incident Location')),
      historyButton: @json(__('History')),
      requestButton: @json(__('Request')),
      requesting: @json(__('Requesting…')),
      sentBang: @json(__('Sent!')),
      failed: @json(__('Failed')),
      errorWord: @json(__('Error')),
      pollingTemplate: @json(__('Polling · :min min')),
      autoPoll: @json(__('Auto-poll')),
      requestingInSecs: @json(__('Requesting in :secs secs')),
      duckIdRequired: @json(__('Duck ID is required.')),
      requestFailed: @json(__('Request failed.')),
      networkError: @json(__('Network error.')),
      sendRequest: @json(__('Send Request')),
      gpsFixPlottedSingular: @json(__('1 fix plotted.')),
      gpsFixesPlottedPlural: @json(__(':count fixes plotted.')),
      gpsNoHistoryRecorded: @json(__('No GPS history recorded for this duck yet.')),
      gpsFailedToLoadHistory: @json(__('Failed to load history.')),
      gpsNoBatteryDataRecorded: @json(__('No battery data recorded.')),
      lastSeen: @json(__('Last seen')),
      sosHardwareTitle: @json(__('SOS — Hardware Button Triggered')),
      sosHardwareBody: @json(__('Physical SOS button was pressed on the device.')),
      sosHardwareFullBody: @json(__('This SOS was sent because the physical SOS button on the device was pressed.')),
      sosMobileTitle: @json(__('SOS — Mobile Phone Triggered')),
      sosMobileBody: @json(__('SOS sent from the mobile app and should include GPS coordinates.')),
      sosMobileFullBody: @json(__("This SOS was sent from the user's mobile phone application and should include GPS coordinates.")),
      rogerTitle: @json(__('Roger — Device Confirmed')),
      rogerBody: @json(__('Triple-click confirmation from the device.')),
      rogerFullBody: @json(__('The person holding the device triple-clicked the button to confirm they have received your message.')),
      criticalTitle: @json(__('Critical — Immediate Attention Required')),
      criticalBody: @json(__('This message has been marked as critical urgency and must be attended to immediately.')),
      gpsNoHardwareLong: @json(__('No GPS hardware — this device cannot report location')),
      gpsUnavailableLong: @json(__('GPS location unavailable — no satellite fix')),
      gpsNoHardwareShort: @json(__('No GPS hardware — location unavailable')),
      gpsNoFixShort: @json(__('No satellite fix — waiting for GPS signal')),
      gpsNoFixLastKnown: @json(__('No satellite fix — showing last known location')),
      incOpen: @json(__('OPEN')),
      incAckd: @json(__("ACK'D")),
      incResp: @json(__('RESP')),
    };
  </script>
</head>
<body class="h-full" data-dashboard-readonly="{{ config('services.central_dms.dashboard_readonly') ? '1' : '0' }}">
<script src="{{ asset('vendor/tailwindplus-elements/elements.min.js') }}" type="module"></script>
<script src="{{ asset('vendor/jquery/jquery-3.0.0.min.js') }}"></script>
<script src="{{ asset('vendor/datatables/dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/flowbite/flowbite.min.js') }}"></script>
<script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>
<div class="min-h-full">
  <div class="relative bg-white dark:bg-gray-800/50 pb-32">
    <nav class="bg-transparent">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 dark:border-white/10">
          <div class="flex h-16 items-center justify-between px-4 sm:px-0">
            <div class="flex items-center">
              <a href="/dashboard" class="flex items-center gap-3 shrink-0 group">
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="size-10 group-hover:opacity-80 transition-opacity">
              </a>
              <div class="hidden md:block">
                @php
                  $navLinks = [
                    'dashboard' => ['/dashboard', __('Dashboard')],
                    'status' => ['/status', __('Status')],
                    'gps' => ['/gps', __('Tracking')],
                    'reports' => ['/reports', __('Reports')],
                  ];
                  // TAK bridge is a separate opt-in service (TAK_BRIDGE_ENABLED); see docs/TAK_BRIDGE.md
                  if (config('services.tak.enabled')) {
                      $navLinks['tak/logs'] = ['/tak/logs', __('TAK Logs')];
                  }
                  $navLinks['messages'] = ['/messages', __('Messages')];
                @endphp
                <div class="ml-10 flex items-baseline space-x-4">
                  @foreach ($navLinks as $path => [$href, $label])
                  <a href="{{ $href }}" @if(request()->is($path)) aria-current="page" class="rounded-md bg-gray-200 dark:bg-gray-950/50 px-3 py-2 text-sm font-medium text-gray-900 dark:text-white" @else class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white" @endif>{{ $label }}</a>
                  @endforeach
                </div>
              </div>
            </div>
            <div class="hidden lg:block">
              <div class="ml-4 flex items-center gap-3 md:ml-6">
                <a href="/system-health" class="system-health-indicator inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 ring-1 ring-inset ring-gray-200 dark:ring-white/10 hover:bg-gray-100 dark:hover:bg-white/5"
                  data-label-ok="{{ __('All systems normal') }}"
                  data-label-degraded="{{ __('Degraded') }}"
                  data-label-down="{{ __('System down') }}"
                  data-label-unknown="{{ __('Status unknown') }}">
                  <span class="relative flex size-2">
                    <span class="system-health-dot-ping absolute inline-flex h-full w-full rounded-full bg-gray-500 opacity-0"></span>
                    <span class="system-health-dot relative inline-flex size-2 rounded-full bg-gray-500"></span>
                  </span>
                  <span class="system-health-label">{{ __('Checking…') }}</span>
                </a>

                @include('partials.locale-switcher')

                <button type="button" onclick="localStorage.theme = document.documentElement.classList.contains('dark') ? 'light' : 'dark'; meshBeaconApplyTheme();" class="rounded-full bg-white dark:bg-gray-800 p-1 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white outline-none focus-visible:ring-2 focus-visible:ring-orange-500" title="{{ __('Toggle theme') }}">
                  <span class="sr-only">{{ __('Toggle dark mode') }}</span>
                  <svg class="size-5 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                  </svg>
                  <svg class="size-5 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                  </svg>
                </button>

                <!-- Profile dropdown -->
                <el-dropdown class="relative ml-3">
                  <button class="relative flex max-w-xs items-center rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
                    <span class="absolute -inset-1.5"></span>
                    <span class="sr-only">{{ __('Open user menu') }}</span>
                    <span class="flex size-8 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10">
                      {{ auth()->user()->initials() }}
                    </span>
                  </button>

                  <el-menu anchor="bottom end" popover class="m-0 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 p-0 py-1 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 transition [--anchor-gap:theme(spacing.2)] [transition-behavior:allow-discrete] data-[closed]:scale-95 data-[closed]:transform data-[closed]:opacity-0 data-[enter]:duration-100 data-[leave]:duration-75 data-[enter]:ease-out data-[leave]:ease-in">
                    <a href="{{ route('profile.edit') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-600 dark:text-gray-300 focus:bg-gray-100 dark:bg-white/5 focus:outline-none">{{ __('Settings') }}</a>
                    {{-- Telegram alerting is opt-in (TELEGRAM_BOT_TOKEN); when not
                         configured, SendTelegramAlert is a no-op and this log would
                         only ever show "No logs found." — hide the link. Administrative/
                         audit destination, so it lives here rather than the primary nav. --}}
                    @if (config('services.telegram.bot_token'))
                    <a href="{{ route('telegram.logs') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-600 dark:text-gray-300 focus:bg-gray-100 dark:bg-white/5 focus:outline-none">{{ __('Telegram Logs') }}</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" data-test="logout-button" class="block w-full px-4 py-2 text-start text-sm text-gray-600 dark:text-gray-300 cursor-pointer focus:bg-gray-100 dark:bg-white/5 focus:outline-none">{{ __('Sign out') }}</button>
                    </form>
                  </el-menu>
                </el-dropdown>
              </div>
            </div>
            <div class="-mr-2 flex lg:hidden">
              <!-- Mobile menu button -->
              <button type="button" command="--toggle" commandfor="mobile-menu" class="relative inline-flex items-center justify-center rounded-md p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:bg-white/5 hover:text-gray-900 dark:text-white focus:outline focus:outline-2 focus:outline-offset-2 focus:outline-orange-500">
                <span class="absolute -inset-0.5"></span>
                <span class="sr-only">{{ __('Open main menu') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 [[aria-expanded='true']_&]:hidden">
                  <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" data-slot="icon" aria-hidden="true" class="size-6 [&:not([aria-expanded='true']_*)]:hidden">
                  <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <el-disclosure id="mobile-menu" hidden class="border-b border-gray-200 dark:border-white/10 lg:hidden [&:not([hidden])]:block">
        <div class="space-y-1 px-2 py-3 sm:px-3">
          <!-- Current: "bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white", Default: "text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:bg-white/5 hover:text-gray-900 dark:text-white" -->
          @foreach ($navLinks as $path => [$href, $label])
          <a href="{{ $href }}" @if(request()->is($path)) aria-current="page" class="block rounded-md bg-gray-50 dark:bg-gray-900 px-3 py-2 text-base font-medium text-gray-900 dark:text-white" @else class="block rounded-md px-3 py-2 text-base font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:bg-white/5 hover:text-gray-900 dark:text-white" @endif>{{ $label }}</a>
          @endforeach
        </div>
        <div class="border-t border-gray-200 dark:border-white/10 pb-3 pt-4">
          <div class="flex items-center px-5">
            <div class="shrink-0">
              <span class="flex size-10 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-700 text-sm font-medium text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10">
                {{ auth()->user()->initials() }}
              </span>
            </div>
            <div class="ml-3">
              <div class="text-base/5 font-medium text-gray-900 dark:text-white">{{ auth()->user()->name }}</div>
              <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</div>
            </div>
          </div>
          <div class="mt-3 space-y-1 px-2">
            <a href="/system-health" class="system-health-indicator flex items-center gap-2 rounded-md px-3 py-2 text-base font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white"
              data-label-ok="{{ __('All systems normal') }}"
              data-label-degraded="{{ __('Degraded') }}"
              data-label-down="{{ __('System down') }}"
              data-label-unknown="{{ __('Status unknown') }}">
              <span class="relative flex size-2">
                <span class="system-health-dot-ping absolute inline-flex h-full w-full rounded-full bg-gray-500 opacity-0"></span>
                <span class="system-health-dot relative inline-flex size-2 rounded-full bg-gray-500"></span>
              </span>
              <span class="system-health-label">{{ __('Checking…') }}</span>
            </a>
            <div class="px-3 pb-2">@include('partials.locale-switcher')</div>
            <a href="{{ route('profile.edit') }}" wire:navigate class="block rounded-md px-3 py-2 text-base font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:bg-white/5 hover:text-gray-900 dark:text-white">{{ __('Your profile') }}</a>
            <a href="{{ route('profile.edit') }}" wire:navigate class="block rounded-md px-3 py-2 text-base font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:bg-white/5 hover:text-gray-900 dark:text-white">{{ __('Settings') }}</a>
            @if (config('services.telegram.bot_token'))
            <a href="{{ route('telegram.logs') }}" wire:navigate class="block rounded-md px-3 py-2 text-base font-medium text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:bg-white/5 hover:text-gray-900 dark:text-white">{{ __('Telegram Logs') }}</a>
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" data-test="logout-button" class="block w-full rounded-md px-3 py-2 text-start text-base font-medium text-gray-500 dark:text-gray-400 cursor-pointer hover:bg-gray-100 dark:bg-white/5 hover:text-gray-900 dark:text-white">{{ __('Sign out') }}</button>
            </form>
          </div>
        </div>
      </el-disclosure>
    </nav>
    <header class="py-10">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        @if(request()->is('dashboard'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Dashboard') }}</h1>
          <a href="/kiosk" title="{{ __('Tip: if this will run on a shared/unattended screen, log in there with "Remember me" checked so it stays signed in after a reboot.') }}" class="inline-flex items-center gap-1.5 rounded-md bg-gray-100 dark:bg-white/5 px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-300 ring-1 ring-inset ring-gray-200 dark:ring-white/10 hover:bg-gray-200 dark:bg-white/10 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
              <path fill-rule="evenodd" d="M1 3.75A2.75 2.75 0 0 1 3.75 1h8.5A2.75 2.75 0 0 1 15 3.75v6.5A2.75 2.75 0 0 1 12.25 13h-8.5A2.75 2.75 0 0 1 1 10.25v-6.5Zm1.5 0v6.5c0 .69.56 1.25 1.25 1.25h8.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25h-8.5c-.69 0-1.25.56-1.25 1.25ZM6 14.75a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
            </svg>
            {{ __('Kiosk Mode') }}
          </a>
        </div>
        @endif
        @if(request()->is('status'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Status') }}</h1>
          @yield('page-actions')
        </div>
        @endif
        @if(request()->is('gps'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Tracking') }}</h1>
          @yield('page-actions')
        </div>
        @endif
        @if(request()->is('reports'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Reports') }}</h1>
          @yield('page-actions')
        </div>
        @endif
        @if(request()->is('messages'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Messages') }}</h1>
          @yield('page-actions')
        </div>
        @endif
        @if(request()->is('system-health'))
        <div class="flex items-center justify-between">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('System Health') }}</h1>
          @yield('page-actions')
        </div>
        @endif
        @if(request()->is('operations'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Operations') }}</h1>
          @yield('page-actions')
        </div>
        @endif
        @if(request()->is('tak/logs'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('TAK Logs') }}</h1>
        </div>
        @endif
        @if(request()->is('telegram/logs'))
        <div class="flex flex-wrap items-center justify-between gap-4">
          <h1 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Telegram Logs') }}</h1>
        </div>
        @endif
      </div>
    </header>
  </div>

  <main class="relative -mt-32">
    <div class="mx-auto max-w-7xl px-4 pb-12 sm:px-6 lg:px-8">
      <div class="rounded-lg bg-white dark:bg-gray-800 px-5 py-6 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 sm:px-6">
     
        @yield('content')
        {{ $slot ?? '' }}

      </div>
    </div>
  </main>
  
  <footer class="py-6 text-center text-sm text-gray-500">
    <div class="mb-2">
      &copy; {{ date('Y') }} <a href="https://meshbeacon.org" target="_blank" rel="noopener" class="hover:text-gray-900 dark:hover:text-white transition-colors font-medium">MeshBeacon</a>.
    </div>
    <div class="text-xs text-gray-400 dark:text-gray-500">
      Licensed under the <a href="http://www.apache.org/licenses/LICENSE-2.0" target="_blank" rel="noopener" class="hover:text-gray-600 dark:hover:text-gray-300 transition-colors underline">Apache License, Version 2.0</a>.
    </div>
  </footer>
</div>
@fluxScripts
<script>
  // PWA disabled: actively unregister any previously installed service worker
  // and clear its caches so existing users aren't stuck on stale cached pages.
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then((registrations) => {
      registrations.forEach((registration) => registration.unregister());
    });
  }
  if (window.caches) {
    caches.keys().then((names) => names.forEach((name) => caches.delete(name)));
  }
</script>
</body>

<script type="module">
const chartEl = document.getElementById("area-chart");

if (chartEl && typeof ApexCharts !== 'undefined') {
  const computedStyle = getComputedStyle(document.documentElement);
  const brandColor = computedStyle.getPropertyValue('--color-fg-brand').trim() || "#1447E6";

  const options = {
    chart: {
      height: "100%",
      maxWidth: "100%",
      type: "area",
      fontFamily: "Inter, sans-serif",
      dropShadow: { enabled: false },
      toolbar: { show: false },
      animations: { enabled: true },
    },
    tooltip: {
      enabled: true,
      x: { show: true },
    },
    fill: {
      type: "gradient",
      gradient: {
        opacityFrom: 0.55,
        opacityTo: 0,
        shade: brandColor,
        gradientToColors: [brandColor],
      },
    },
    dataLabels: { enabled: false },
    stroke: { width: 6 },
    grid: {
      show: false,
      strokeDashArray: 4,
      padding: { left: 2, right: 2, top: 0 },
    },
    series: [{ name: "Messages", data: [], color: brandColor }],
    xaxis: {
      categories: [],
      labels: {
        show: true,
        rotate: 0,
        style: { colors: '#9ca3af', fontSize: '11px' },
        formatter: (val) => val,
      },
      axisBorder: { show: false },
      axisTicks: { show: false },
    },
    yaxis: { show: false },
    noData: {
      text: @json(__('Loading…')),
      style: { color: '#9ca3af', fontSize: '14px' },
    },
  };

  const chart = new ApexCharts(chartEl, options);
  chart.render();

  const upArrow   = `<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"/></svg>`;
  const downArrow = `<svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19V6m0 13-4-4m4 4 4-4"/></svg>`;

  function updateTrend(trend) {
    const trendEl = document.getElementById('chart-trend');
    if (!trendEl || !trend) return;
    const isUp = trend.direction === 'up';
    trendEl.className = `flex items-center px-2.5 py-0.5 font-medium text-center ${isUp ? 'text-fg-success' : 'text-red-600 dark:text-red-400'}`;
    trendEl.innerHTML  = (isUp ? upArrow : downArrow) + `<span class="ml-1">${trend.percentage}%</span>`;
    trendEl.title = @json(__('Current hour: :current msgs · Previous hour: :previous msgs'))
      .replace(':current', trend.current_hour)
      .replace(':previous', trend.previous_hour);
  }

  async function loadHourlyData() {
    try {
      const response = await fetch('/dashboard/hourly');
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const json = await response.json();
      chart.updateOptions({
        series: [{ name: "Messages", data: json.data, color: brandColor }],
        xaxis: { categories: json.labels },
        noData: { text: @json(__('No messages today')) },
      });
      updateTrend(json.trend);
    } catch (e) {
      console.error('Failed to load hourly chart data:', e);
    }
  }

  loadHourlyData();
  setInterval(loadHourlyData, 60_000);
}

const healthIndicators = document.querySelectorAll('.system-health-indicator');

if (healthIndicators.length) {
  const applyHealthState = (state, label) => {
    const dotColor  = { ok: 'bg-fg-success', degraded: 'bg-yellow-500', down: 'bg-red-500', unknown: 'bg-gray-500' }[state];
    const showPing  = state === 'down';

    healthIndicators.forEach((el) => {
      const dot  = el.querySelector('.system-health-dot');
      const ping = el.querySelector('.system-health-dot-ping');
      const text = el.querySelector('.system-health-label');

      dot.className  = `system-health-dot relative inline-flex size-2 rounded-full ${dotColor}`;
      ping.className  = `system-health-dot-ping absolute inline-flex h-full w-full rounded-full ${dotColor} ${showPing ? 'animate-ping opacity-75' : 'opacity-0'}`;
      text.textContent = label ?? el.dataset[`label${state.charAt(0).toUpperCase()}${state.slice(1)}`];
    });
  };

  async function pollSystemHealth() {
    try {
      const response = await fetch('{{ route('system-health.status') }}', { headers: { 'Accept': 'application/json' } });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const report = await response.json();
      const state  = report.ready === false ? 'down' : (report.status === 'degraded' ? 'degraded' : 'ok');
      applyHealthState(state, healthIndicators[0]?.dataset[`label${state.charAt(0).toUpperCase()}${state.slice(1)}`]);
    } catch (e) {
      applyHealthState('unknown', healthIndicators[0]?.dataset.labelUnknown);
    }
  }

  pollSystemHealth();
  setInterval(pollSystemHealth, 20_000);
}
</script>
</html>
