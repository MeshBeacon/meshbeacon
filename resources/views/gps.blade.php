<x-layouts::app :title="__('Tracking')">
@section('page-actions')
  <div class="flex items-center gap-2">
    <span class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium text-gray-400">
      <span class="relative flex size-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
        <span class="relative inline-flex size-2 rounded-full bg-green-500"></span>
      </span>
      {{ __('Live') }}
    </span>
    <button command="show-modal" commandfor="gps-request-dialog"
      class="inline-flex items-center gap-1.5 rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
        <path fill-rule="evenodd" d="M7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd" />
      </svg>
      {{ __('Request GPS from Duck') }}
    </button>
  </div>
@endsection
@section('content')
<div class="flex flex-col">

  <!-- Header row with search + source filter -->
  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-base font-semibold text-white">{{ __('Duck GPS Locations') }}</h1>
    <div class="flex items-center gap-2">
      <el-select id="source-filter" name="source-filter" value="" class="block w-36">
        <button type="button" class="grid w-full cursor-default grid-cols-1 rounded-md bg-white/5 py-1.5 pl-3 pr-2 text-left text-white outline outline-1 -outline-offset-1 outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500 sm:text-sm/6">
          <el-selectedcontent class="col-start-1 row-start-1 truncate pr-6">{{ __('All Sources') }}</el-selectedcontent>
          <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-400 sm:size-4">
            <path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
          </svg>
        </button>
        <el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">
          @foreach ([['', __('All Sources')], ['Satellite', __('Satellite')], ['Phone', __('Phone')], ['No Fix', __('No Fix')]] as [$val, $label])
          <el-option value="{{ $val }}" class="group/option relative cursor-default select-none py-2 pl-8 pr-4 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
            <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ $label }}</span>
            <span class="absolute inset-y-0 left-0 flex items-center pl-1.5 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
              <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5">
                <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
              </svg>
            </span>
          </el-option>
          @endforeach
        </el-options>
      </el-select>

      <div class="relative rounded-md outline outline-1 -outline-offset-1 outline-white/10 focus-within:outline-2 focus-within:-outline-offset-2 focus-within:outline-yellow-500">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-gray-400">
            <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
          </svg>
        </div>
        <input id="gps-search" type="text" placeholder="{{ __('Search duck ID…') }}"
          class="w-44 rounded-md bg-white/5 py-1.5 pl-8 pr-3 text-sm text-white placeholder:text-gray-500 focus:outline-none">
      </div>
    </div>
  </div>

  <!-- Cards grid -->
  <div id="gps-cards-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">

@forelse ($gpsRecords as $record)
@php
  $srcLabel   = $record->gps_source_label;
  $badgeLabel = $record->gps_badge_label;
  $badgeClass = match($badgeLabel) {
    'Satellite' => 'rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-semibold text-green-400 ring-1 ring-inset ring-green-500/30',
    'Phone'     => 'rounded-full bg-blue-500/20 px-2 py-0.5 text-xs font-semibold text-blue-400 ring-1 ring-inset ring-blue-500/30',
    'No Phone'  => 'rounded-full bg-slate-500/20 px-2 py-0.5 text-xs font-semibold text-slate-400 ring-1 ring-inset ring-slate-500/30',
    default     => 'rounded-full bg-yellow-500/20 px-2 py-0.5 text-xs font-semibold text-yellow-400 ring-1 ring-inset ring-yellow-500/30',
  };
@endphp
<div class="flex flex-col divide-y divide-white/10 overflow-hidden rounded-lg bg-gray-800/50 outline outline-1 -outline-offset-1 outline-white/10"
     data-duck-id="{{ $record->duck_id }}"
     data-gps-src="{{ $srcLabel }}"
     data-gps-record-id="{{ $record->id }}">

  <!-- Card header: duck ID + source badge + auto-poll toggle -->
  @php $poll = $pollStates[$record->duck_id] ?? null; @endphp
  <div class="px-4 py-4 sm:px-6 flex flex-col gap-2">
    <div class="flex items-center justify-between">
      <span class="text-sm font-semibold text-white">{{ $record->duck_id }}</span>
      <span class="{{ $badgeClass }}" data-gps-badge="{{ $record->duck_id }}">{{ $badgeLabel }}</span>
    </div>
    <div class="flex flex-wrap items-center gap-2">
      <button type="button"
        class="poll-toggle-btn inline-flex shrink-0 items-center gap-1 rounded px-2 py-0.5 text-xs ring-1 ring-inset transition-colors {{ $poll?->enabled ? 'bg-cyan-500/20 text-cyan-400 ring-cyan-500/30' : 'bg-white/5 text-gray-500 ring-white/10 hover:bg-white/10' }}"
        data-duck-id="{{ $record->duck_id }}"
        data-poll-enabled="{{ $poll?->enabled ? '1' : '0' }}"
        data-poll-interval="{{ $poll?->interval_minutes ?? 1 }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3 shrink-0">
          <path fill-rule="evenodd" d="M1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8Zm7.75-4.25a.75.75 0 0 0-1.5 0V8c0 .414.336.75.75.75h3.25a.75.75 0 0 0 0-1.5h-2.5v-3.5Z" clip-rule="evenodd"/>
        </svg>
        {{ $poll?->enabled ? __('Polling · :min min', ['min' => $poll?->interval_minutes ?? 1]) : __('Auto-poll') }}
      </button>
      <el-select name="poll-interval-{{ $record->duck_id }}" value="{{ $poll?->interval_minutes ?? 1 }}"
        class="poll-interval-select block w-16 shrink-0" data-duck-id="{{ $record->duck_id }}">
        <button type="button" class="grid w-full cursor-default grid-cols-1 rounded bg-white/5 py-0.5 pl-2 pr-1 text-left text-xs text-gray-400 outline outline-1 -outline-offset-1 outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500">
          <el-selectedcontent class="col-start-1 row-start-1 truncate pr-4">{{ $poll?->interval_minutes ?? 1 }}min</el-selectedcontent>
          <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="col-start-1 row-start-1 size-3.5 self-center justify-self-end text-gray-400">
            <path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
          </svg>
        </button>
        <el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">
          @foreach ([1, 5, 15, 30, 60] as $mins)
          <el-option value="{{ $mins }}" class="group/option relative cursor-default select-none py-1.5 pl-6 pr-3 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
            <span class="block truncate text-xs font-normal group-aria-selected/option:font-semibold">{{ $mins }}min</span>
            <span class="absolute inset-y-0 left-0 flex items-center pl-1 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
              <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4">
                <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
              </svg>
            </span>
          </el-option>
          @endforeach
        </el-options>
      </el-select>
      <span class="basis-full text-xs text-gray-500 sm:basis-auto {{ $poll?->enabled ? '' : 'hidden' }}"
            data-poll-next="{{ $record->duck_id }}">{{ ($poll?->enabled && $poll?->next_run_at) ? __('Requesting in :secs secs', ['secs' => max(0, (int) now()->diffInSeconds($poll->next_run_at))]) : '' }}</span>
    </div>
  </div>

  <!-- Card body: coordinates or no-fix notice -->
  <div class="px-4 py-3 sm:px-6 flex flex-col gap-1.5 grow" data-gps-body="{{ $record->duck_id }}">
    @if ($record->gps_fix_zero)
        @php
          $noFixMsg = $record->gps_no_phone   ? __('No GPS fix — device active, no phone connected')
                    : ($record->gps_from_phone ? __('No GPS fix — phone GPS unavailable')
                    : __('No GPS fix — no satellite signal'));
          $lastCoord = $lastKnownCoords[$record->duck_id] ?? null;
        @endphp
        <div class="grow flex flex-col gap-1.5">
          <p class="inline-flex items-center gap-1.5 rounded bg-yellow-400/10 px-2 py-1.5 text-xs text-yellow-400">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0">
              <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l10.5 10.5a.75.75 0 1 0 1.06-1.06L3.28 2.22ZM7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd" />
            </svg>
            {{ $noFixMsg }}
          </p>
          @if ($lastCoord)
            <div class="mt-0.5">
              <p class="text-xs text-gray-500">{{ __('Last known coordinates') }}</p>
              <p class="font-mono text-xs text-gray-400 mt-0.5">{{ $lastCoord->gps_lat }}, {{ $lastCoord->gps_lng }}</p>
              @if ($lastCoord->gps_alt !== null || $lastCoord->gps_spd !== null || $lastCoord->gps_hdg !== null)
                <div class="flex flex-wrap gap-1 mt-1">
                  @if ($lastCoord->gps_alt !== null)
                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium" style="background:rgba(30,58,138,0.7);color:#93c5fd">{{ number_format($lastCoord->gps_alt, 1) }} m alt</span>
                  @endif
                  @if ($lastCoord->gps_spd !== null)
                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium" style="background:rgba(76,29,149,0.7);color:#c4b5fd">{{ number_format($lastCoord->gps_spd, 1) }} km/h</span>
                  @endif
                  @if ($lastCoord->gps_hdg !== null)
                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium" style="background:rgba(12,74,110,0.7);color:#7dd3fc">{{ number_format($lastCoord->gps_hdg, 1) }}&deg;</span>
                  @endif
                </div>
              @endif
              <p class="text-xs text-gray-600 mt-0.5">{{ $lastCoord->created_at->diffForHumans() }}</p>
            </div>
          @endif
        </div>
        @if ($record->gps_batt !== null)
          <div class="flex items-start gap-1.5">
            <span class="text-xs text-gray-500 w-10 shrink-0 pt-0.5">{{ __('Device') }}</span>
            <div class="flex flex-wrap gap-1.5 flex-1">
              <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium {{ $record->gps_batt < 20 ? 'bg-red-800/60 text-red-300' : ($record->gps_batt < 50 ? 'bg-yellow-800/60 text-yellow-300' : 'bg-green-800/60 text-green-300') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>
                {{ $record->gps_batt }}%
              </span>
            </div>
          </div>
        @endif
    @elseif ($record->map_url)
      <div class="grow flex flex-col gap-1.5">
        <p class="text-xs text-gray-400">{{ __('Coordinates') }}</p>
        <p class="font-mono text-sm text-white">{{ $record->gps_lat }}, {{ $record->gps_lng }}</p>
        @if ($record->gps_sats !== null)
          <p class="text-xs text-gray-500">{{ __(':count satellites in view', ['count' => $record->gps_sats]) }}</p>
        @else
          <p class="text-xs text-gray-500">&nbsp;</p>
        @endif
      </div>
      @if ($record->gps_batt !== null || $record->gps_alt !== null || $record->gps_spd !== null || $record->gps_hdg !== null)
        <div class="mt-0.5 flex flex-col gap-1.5">
          @if ($record->gps_batt !== null)
            <div class="flex items-start gap-1.5">
              <span class="text-xs text-gray-500 w-10 shrink-0 pt-0.5">{{ __('Device') }}</span>
              <div class="flex flex-wrap gap-1.5 flex-1">
                <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium {{ $record->gps_batt < 20 ? 'bg-red-800/60 text-red-300' : ($record->gps_batt < 50 ? 'bg-yellow-800/60 text-yellow-300' : 'bg-green-800/60 text-green-300') }}">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>
                  {{ $record->gps_batt }}%
                </span>
              </div>
            </div>
          @endif
          @if ($record->gps_alt !== null || $record->gps_spd !== null || $record->gps_hdg !== null)
            <div class="flex items-start gap-1.5">
              <span class="text-xs text-gray-500 w-10 shrink-0 pt-0.5">{{ __('GPS') }}</span>
              <div class="flex flex-wrap gap-1.5 flex-1">
                @if ($record->gps_alt !== null)
                  <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-blue-800/60 text-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M8 1.75a.75.75 0 0 1 .674.418l1.882 3.815 4.21.612a.75.75 0 0 1 .416 1.279l-3.046 2.97.719 4.192a.75.75 0 0 1-1.088.791L8 13.347l-3.767 1.98a.75.75 0 0 1-1.088-.79l.72-4.194L.818 7.874a.75.75 0 0 1 .416-1.28l4.21-.611L7.327 2.17A.75.75 0 0 1 8 1.75Z" clip-rule="evenodd"/></svg>
                    {{ number_format($record->gps_alt, 1) }} m
                  </span>
                @endif
                @if ($record->gps_spd !== null)
                  <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-purple-800/60 text-purple-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M7.487 2.89a.75.75 0 1 0-1.474-.28l-.455 2.388a.75.75 0 1 0 1.474.28l.455-2.388Zm4.095.99a.75.75 0 1 0-1.06-1.06L9.22 4.122a.75.75 0 1 0 1.06 1.06l1.302-1.302ZM2.28 8a.75.75 0 1 0-.28-1.474l-2.388.455a.75.75 0 1 0 .28 1.474L2.28 8ZM8 2a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 2ZM5.122 9.22a.75.75 0 0 0 0-1.06L3.818 6.857a.75.75 0 0 0-1.06 1.06l1.304 1.303a.75.75 0 0 0 1.06 0ZM8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm3.25.75a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm-.44 3.22a.75.75 0 1 0 1.06-1.06l-1.3-1.302a.75.75 0 0 0-1.06 1.06l1.3 1.302Z" clip-rule="evenodd"/></svg>
                    {{ number_format($record->gps_spd, 1) }} km/h
                  </span>
                @endif
                @if ($record->gps_hdg !== null)
                  <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-sky-800/60 text-sky-200">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M8 14a6 6 0 1 0 0-12A6 6 0 0 0 8 14ZM9.25 5.75A.75.75 0 0 0 8 5.134a.75.75 0 0 0-1.25.616v4.5a.75.75 0 0 0 1.25.616.75.75 0 0 0 1.25-.616v-4.5Z" clip-rule="evenodd"/></svg>
                    {{ number_format($record->gps_hdg, 1) }}&deg;
                  </span>
                @endif
              </div>
            </div>
          @endif
        </div>
      @endif

      @php $mapDialogId = 'gps-map-dialog-' . $record->id; @endphp
      <button command="show-modal" commandfor="{{ $mapDialogId }}"
         class="mt-2 inline-flex w-fit items-center gap-1.5 rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-green-500">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
          <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />
        </svg>
        {{ __('View on Map') }}
      </button>

      <el-dialog>
        <dialog id="{{ $mapDialogId }}" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
          <el-dialog-backdrop class="fixed inset-0 bg-gray-900/75 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>
          <div tabindex="0" class="flex min-h-full items-center justify-center p-4 focus:outline focus:outline-0">
            <el-dialog-panel class="relative w-full max-w-2xl overflow-hidden rounded-lg bg-gray-800 shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in data-[closed]:scale-95">
              <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                <div>
                  <h3 class="text-sm font-semibold text-white">{{ $record->duck_id }} &mdash; {{ __('GPS Location') }}</h3>
                  <p class="text-xs text-gray-400 mt-0.5">
                    {{ __('Source:') }} <span class="{{ $record->gps_from_phone ? 'text-blue-400' : 'text-green-400' }}">{{ $srcLabel }}</span>
                    @if ($record->gps_sats !== null)
                      &bull; {{ $record->gps_sats }} {{ __('satellites') }}
                    @endif
                    @if ($record->gps_alt !== null)
                      &bull; {{ number_format($record->gps_alt, 1) }} m &bull; {{ number_format($record->gps_spd ?? 0, 1) }} km/h &bull; {{ number_format($record->gps_hdg ?? 0, 1) }}&deg;
                    @endif
                  </p>
                </div>
                <button command="close" commandfor="{{ $mapDialogId }}" class="text-gray-400 hover:text-white text-lg leading-none">&times;</button>
              </div>
              <div class="w-full h-96">
                <iframe
                  src="{{ $record->map_embed_url }}"
                  class="w-full h-full border-0"
                  allowfullscreen
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade">
                </iframe>
              </div>
              <div class="flex justify-end gap-3 px-4 py-3 border-t border-white/10">
                <a href="{{ $record->map_url }}" target="_blank" rel="noopener noreferrer"
                   class="text-xs text-yellow-400 hover:text-yellow-300">{{ __('Open in Google Maps') }} &rarr;</a>
                <button command="close" commandfor="{{ $mapDialogId }}"
                   class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/20">{{ __('Close') }}</button>
              </div>
            </el-dialog-panel>
          </div>
        </dialog>
      </el-dialog>
    @else
      <p class="mt-1 text-xs text-gray-500">{{ __('No location data in this record.') }}</p>
    @endif
  </div>

  <!-- Card footer: timestamp + request GPS button -->
  <div class="px-4 py-3 sm:px-6 flex flex-col gap-2">
    <div class="flex flex-col gap-0.5">
      <span class="text-sm text-white" data-gps-ts="{{ $record->duck_id }}">{{ $record->created_at->diffForHumans() }}</span>
      <span class="text-xs text-gray-500" data-gps-ts-abs="{{ $record->duck_id }}">{{ $record->created_at->format('j M Y, H:i') }}</span>
    </div>
    <div class="flex items-center gap-1.5">
      <button type="button"
        class="gps-history-btn inline-flex flex-1 items-center justify-center gap-1 rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 disabled:opacity-50"
        data-duck-id="{{ $record->duck_id }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
          <path fill-rule="evenodd" d="M8 1a7 7 0 1 0 4.95 11.95.75.75 0 0 0-1.06-1.06A5.5 5.5 0 1 1 13.5 8a.75.75 0 0 0 1.5 0A7 7 0 0 0 8 1Zm0 3a.75.75 0 0 1 .75.75v3.5l2.22 1.28a.75.75 0 0 1-.75 1.3l-2.6-1.5A.75.75 0 0 1 7.25 9V4.75A.75.75 0 0 1 8 4Z" clip-rule="evenodd" />
        </svg>
        {{ __('History') }}
      </button>
      <button type="button"
        class="gps-request-btn inline-flex flex-1 items-center justify-center gap-1 rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 disabled:opacity-50"
        data-duck-id="{{ $record->duck_id }}">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
          <path fill-rule="evenodd" d="M7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd" />
        </svg>
        {{ __('Request') }}
      </button>
    </div>
  </div>
</div>
@empty
  {{-- handled by #gps-empty-state below --}}
@endforelse

    <!-- Empty state -->
    <div id="gps-empty-state" class="col-span-full {{ $gpsRecords->isEmpty() ? '' : 'hidden' }} py-16 text-center">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mx-auto mb-3 size-10 text-gray-600">
        <path fill-rule="evenodd" d="M11.54 22.351l.07.04.028.016a.76.76 0 0 0 .723 0l.028-.015.071-.041a16.975 16.975 0 0 0 1.144-.742 19.58 19.58 0 0 0 2.683-2.282c1.944-1.99 3.963-4.98 3.963-8.827a8.25 8.25 0 0 0-16.5 0c0 3.846 2.02 6.837 3.963 8.827a19.58 19.58 0 0 0 2.682 2.282 16.975 16.975 0 0 0 1.145.742ZM12 13.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" clip-rule="evenodd" />
      </svg>
      <p id="gps-empty-title" class="text-sm font-semibold text-gray-400">{{ __('No GPS records found') }}</p>
      <p id="gps-empty-sub" class="mt-1 text-xs text-gray-600">{{ __('GPS data appears here once ducks report their location on LoRa topic 0xEA.') }}</p>
    </div>
  </div>
</div>

<!-- Request GPS from Duck Modal -->
<el-dialog>
  <dialog id="gps-request-dialog" aria-labelledby="gps-request-title" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
    <el-dialog-backdrop class="fixed inset-0 bg-gray-900/50 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>
    <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline focus:outline-0 sm:items-center sm:p-0">
      <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:translate-y-4 data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in sm:my-8 sm:w-full sm:max-w-sm sm:p-6 data-[closed]:sm:translate-y-0 data-[closed]:sm:scale-95">
        <form id="gps-request-form">
          @csrf
          <div class="space-y-12">
            <div class="border-b border-white/10 pb-3">
              <h2 id="gps-request-title" class="text-base/7 font-semibold text-white">{{ __('Request GPS Location') }}</h2>
              <p class="mt-1 text-sm/6 text-gray-400">{{ __('Sends a GPS request (topic 234) to the specified duck.') }}</p>
              <div class="col-span-full mt-4">
                <label for="gps-request-duck-id" class="block text-sm/6 font-medium text-white">{{ __('Duck ID') }}</label>
                <div class="mt-2">
                  <input id="gps-request-duck-id" type="text" name="duck_id" maxlength="50" placeholder="e.g. MAMAMUHAMMAD"
                    class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-sm text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-yellow-500">
                </div>
              </div>
            </div>
          </div>
          <div class="mt-2 flex items-center gap-3">
            <button type="submit" id="gps-request-send-btn"
              class="w-full flex justify-center rounded-md bg-yellow-500 px-3 py-2 text-sm font-semibold text-white hover:bg-yellow-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500 disabled:opacity-50 disabled:cursor-not-allowed">
              {{ __('Send Request') }}
            </button>
            <button type="button" command="close" commandfor="gps-request-dialog"
              class="rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
              Cancel
            </button>
            <span id="gps-request-status" class="ml-auto text-xs"></span>
          </div>
        </form>
      </el-dialog-panel>
    </div>
  </dialog>
</el-dialog>

<!-- GPS History / Replay Modal (shared across all cards) -->
<el-dialog>
  <dialog id="gps-history-dialog" aria-labelledby="gps-history-title" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
    <el-dialog-backdrop class="fixed inset-0 bg-gray-900/50 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>
    <div tabindex="0" class="flex min-h-full items-end justify-center p-4 text-center focus:outline focus:outline-0 sm:items-center sm:p-0">
      <el-dialog-panel class="relative transform overflow-hidden rounded-lg bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:translate-y-4 data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in sm:my-8 sm:w-full sm:max-w-2xl sm:p-6 data-[closed]:sm:translate-y-0 data-[closed]:sm:scale-95">
        <h2 id="gps-history-title" class="text-base/7 font-semibold text-white">{{ __('Location History') }} &mdash; <span id="gps-history-duck-id"></span></h2>
        <p class="mt-1 text-sm/6 text-gray-400">{{ __('Last 50 recorded fixes.') }}</p>
        <div id="gps-history-map" class="mt-4 h-72 w-full rounded-md bg-gray-900"></div>
        <div class="mt-3">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Battery trend') }}</h3>
            <span id="gps-history-battery-caption" class="text-[11px] text-gray-500"></span>
          </div>
          <div id="gps-history-battery" class="mt-1 flex items-end gap-0.5 h-12"></div>
          <div class="mt-1 flex items-center gap-3 text-[11px] text-gray-500">
            <span class="flex items-center gap-1"><span class="size-2 rounded-sm bg-green-500"></span>&ge;50%</span>
            <span class="flex items-center gap-1"><span class="size-2 rounded-sm bg-yellow-500"></span>20&ndash;49%</span>
            <span class="flex items-center gap-1"><span class="size-2 rounded-sm bg-red-500"></span>&lt;20%</span>
            <span class="ml-auto italic">{{ __('Oldest → newest, hover a bar for details') }}</span>
          </div>
        </div>
        <div id="gps-history-status" class="mt-2 text-xs text-gray-500"></div>
        <div class="mt-4 flex justify-end">
          <button type="button" command="close" commandfor="gps-history-dialog"
            class="rounded-md bg-white/10 px-3 py-2 text-sm font-semibold text-white hover:bg-white/20">
            {{ __('Close') }}
          </button>
        </div>
      </el-dialog-panel>
    </div>
  </dialog>
</el-dialog>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function badgeClass(label) {
    if (label === 'Satellite') return 'rounded-full bg-green-500/20 px-2 py-0.5 text-xs font-semibold text-green-400 ring-1 ring-inset ring-green-500/30';
    if (label === 'Phone')     return 'rounded-full bg-blue-500/20 px-2 py-0.5 text-xs font-semibold text-blue-400 ring-1 ring-inset ring-blue-500/30';
    if (label === 'No Phone')  return 'rounded-full bg-slate-500/20 px-2 py-0.5 text-xs font-semibold text-slate-400 ring-1 ring-inset ring-slate-500/30';
    return 'rounded-full bg-yellow-500/20 px-2 py-0.5 text-xs font-semibold text-yellow-400 ring-1 ring-inset ring-yellow-500/30';
  }

  // SVG icon paths (Heroicons mini 16-solid)
  var ICON_GPS_SLASH = '<path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l10.5 10.5a.75.75 0 1 0 1.06-1.06L3.28 2.22ZM7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd" />';
  var ICON_NO_PHONE  = '<path d="M6 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H6Zm2 9a.75.75 0 1 1 0-1.5A.75.75 0 0 1 8 11Z" />';
  var ICON_BATTERY   = '<path d="M2 7.5A1.5 1.5 0 0 1 3.5 6H11A1.5 1.5 0 0 1 12.5 7.5v1A1.5 1.5 0 0 1 11 10H3.5A1.5 1.5 0 0 1 2 8.5v-1Z" /><path d="M13.25 7.5a.75.75 0 0 0-.75.75v.5a.75.75 0 0 0 1.5 0v-.5a.75.75 0 0 0-.75-.75Z" />';
  var ICON_CLOCK     = '<path fill-rule="evenodd" d="M1 8a7 7 0 1 1 14 0A7 7 0 0 1 1 8Zm7.75-4.25a.75.75 0 0 0-1.5 0V8c0 .414.336.75.75.75h3.25a.75.75 0 0 0 0-1.5h-2.5v-3.5Z" clip-rule="evenodd"/>';

  function secsLabel(nextAt) {
    var secs = Math.max(0, Math.round((new Date(nextAt) - Date.now()) / 1000));
    return 'Requesting in ' + secs + ' secs';
  }

  function pollToggleBtnHtml(duckId, enabled, nextAt, intervalMinutes) {
    var interval = intervalMinutes || 1;
    var cls = enabled
      ? 'poll-toggle-btn inline-flex shrink-0 items-center gap-1 rounded px-2 py-0.5 text-xs ring-1 ring-inset transition-colors bg-cyan-500/20 text-cyan-400 ring-cyan-500/30'
      : 'poll-toggle-btn inline-flex shrink-0 items-center gap-1 rounded px-2 py-0.5 text-xs ring-1 ring-inset transition-colors bg-white/5 text-gray-500 ring-white/10 hover:bg-white/10';
    var label = enabled ? 'Polling \u00b7 ' + interval + 'min' : 'Auto-poll';
    var nextHtml = enabled && nextAt
      ? '<span class="basis-full text-xs text-gray-500 sm:basis-auto" data-poll-next="' + escHtml(duckId) + '">' + secsLabel(nextAt) + '</span>'
      : '<span class="basis-full text-xs text-gray-500 sm:basis-auto hidden" data-poll-next="' + escHtml(duckId) + '"></span>';
    var options = [1, 5, 15, 30, 60].map(function (mins) {
      return '<el-option value="' + mins + '" class="group/option relative cursor-default select-none py-1.5 pl-6 pr-3 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">' +
        '<span class="block truncate text-xs font-normal group-aria-selected/option:font-semibold">' + mins + 'min</span>' +
        '<span class="absolute inset-y-0 left-0 flex items-center pl-1 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected=\'true\'])]/option:hidden [el-selectedcontent_&]:hidden">' +
          '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4"><path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" /></svg>' +
        '</span>' +
      '</el-option>';
    }).join('');
    var intervalSelectHtml =
      '<el-select name="poll-interval-' + escHtml(duckId) + '" value="' + interval + '" class="poll-interval-select block w-16 shrink-0" data-duck-id="' + escHtml(duckId) + '">' +
        '<button type="button" class="grid w-full cursor-default grid-cols-1 rounded bg-white/5 py-0.5 pl-2 pr-1 text-left text-xs text-gray-400 outline outline-1 -outline-offset-1 outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500">' +
          '<el-selectedcontent class="col-start-1 row-start-1 truncate pr-4">' + interval + 'min</el-selectedcontent>' +
          '<svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="col-start-1 row-start-1 size-3.5 self-center justify-self-end text-gray-400"><path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" /></svg>' +
        '</button>' +
        '<el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">' +
          options +
        '</el-options>' +
      '</el-select>';
    return '<div class="flex flex-wrap items-center gap-2">' +
      '<button type="button" class="' + cls + '" data-duck-id="' + escHtml(duckId) + '" data-poll-enabled="' + (enabled ? '1' : '0') + '" data-poll-interval="' + interval + '">' +
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3 shrink-0">' + ICON_CLOCK + '</svg>' +
        label +
      '</button>' +
      intervalSelectHtml +
      nextHtml +
    '</div>';
  }

  function buildGpsBody(rec) {
    if (rec.gps_fix_zero) {
      var msgText = rec.gps_no_phone   ? 'No GPS fix \u2014 device active, no phone connected'
                 : rec.gps_from_phone  ? 'No GPS fix \u2014 phone GPS unavailable'
                 : 'No GPS fix \u2014 no satellite signal';
      var lastKnown = '';
      if (rec.last_known_lat && rec.last_known_lng) {
        var lkPills = '';
        if (rec.last_known_alt !== null && rec.last_known_alt !== undefined)
          lkPills += '<span style="display:inline-flex;align-items:center;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(30,58,138,0.7);color:#93c5fd">' + Number(rec.last_known_alt).toFixed(1) + ' m alt</span>';
        if (rec.last_known_spd !== null && rec.last_known_spd !== undefined)
          lkPills += '<span style="display:inline-flex;align-items:center;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(76,29,149,0.7);color:#c4b5fd">' + Number(rec.last_known_spd).toFixed(1) + ' km/h</span>';
        if (rec.last_known_hdg !== null && rec.last_known_hdg !== undefined)
          lkPills += '<span style="display:inline-flex;align-items:center;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(12,74,110,0.7);color:#7dd3fc">' + Number(rec.last_known_hdg).toFixed(1) + '\u00b0</span>';
        lastKnown = '<div class="mt-0.5">' +
          '<p class="text-xs text-gray-500">Last known coordinates</p>' +
          '<p class="font-mono text-xs text-gray-400 mt-0.5">' + escHtml(rec.last_known_lat) + ', ' + escHtml(rec.last_known_lng) + '</p>' +
          (lkPills ? '<div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:3px">' + lkPills + '</div>' : '') +
          (rec.last_known_at ? '<p class="text-xs text-gray-600 mt-0.5">' + escHtml(rec.last_known_at) + '</p>' : '') +
        '</div>';
      }
      var battHtml = '';
      if (rec.gps_batt !== null && rec.gps_batt !== undefined) {
        var b0 = parseInt(rec.gps_batt, 10);
        var bc0 = b0 < 20 ? 'background:rgba(127,29,29,0.7);color:#fca5a5'
          : b0 < 50 ? 'background:rgba(113,63,18,0.7);color:#fde68a'
          : 'background:rgba(20,83,45,0.7);color:#86efac';
        battHtml = '<div style="display:flex;align-items:flex-start;gap:5px;">' +
          '<span style="font-size:0.65rem;color:#6b7280;flex-shrink:0;min-width:2.5rem;padding-top:2px;">Device</span>' +
          '<div style="display:flex;flex-wrap:wrap;gap:4px;flex:1;">' +
          '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;' + bc0 + '">' +
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px;flex-shrink:0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>' +
          b0 + '%</span></div></div>';
      }
      return '<div class="grow flex flex-col gap-1.5"><p class="inline-flex items-center gap-1.5 rounded bg-yellow-400/10 px-2 py-1.5 text-xs text-yellow-400">' +
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0">' +
          ICON_GPS_SLASH +
        '</svg>' +
        escHtml(msgText) + '</p>' + lastKnown + '</div>' + battHtml;
    }
    if (rec.map_url) {
      var dlgId  = 'gps-map-dialog-' + rec.id;
      var srcCls = rec.gps_from_phone ? 'text-blue-400' : 'text-green-400';
      var sats   = rec.gps_sats !== null
        ? '<p class="text-xs text-gray-500">' + escHtml(rec.gps_sats) + ' satellites in view</p>'
        : '<p class="text-xs text-gray-500">&nbsp;</p>';
      var hasBatt = rec.gps_batt !== null && rec.gps_batt !== undefined;
      var hasAlt  = rec.gps_alt  !== null && rec.gps_alt  !== undefined;
      var hasSpd  = rec.gps_spd  !== null && rec.gps_spd  !== undefined;
      var hasHdg  = rec.gps_hdg  !== null && rec.gps_hdg  !== undefined;
      var telHtml = '';
      if (hasBatt || hasAlt || hasSpd || hasHdg) {
        telHtml = '<div style="margin-top:4px;display:flex;flex-direction:column;gap:5px;">';
        if (hasBatt) {
          var b1 = parseInt(rec.gps_batt, 10);
          var bc1 = b1 < 20 ? 'background:rgba(127,29,29,0.7);color:#fca5a5'
            : b1 < 50 ? 'background:rgba(113,63,18,0.7);color:#fde68a'
            : 'background:rgba(20,83,45,0.7);color:#86efac';
          telHtml += '<div style="display:flex;align-items:flex-start;gap:5px;">' +
            '<span style="font-size:0.65rem;color:#6b7280;flex-shrink:0;min-width:2.5rem;padding-top:2px;">Device</span>' +
            '<div style="display:flex;flex-wrap:wrap;gap:4px;flex:1;">' +
            '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;' + bc1 + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px;flex-shrink:0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>' +
            b1 + '%</span></div></div>';
        }
        if (hasAlt || hasSpd || hasHdg) {
          var gpsPills = '';
          if (hasAlt) gpsPills += '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(30,58,138,0.7);color:#93c5fd">' + Number(rec.gps_alt).toFixed(1) + ' m alt</span>';
          if (hasSpd) gpsPills += '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(76,29,149,0.7);color:#c4b5fd">' +
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" style="width:10px;height:10px;flex-shrink:0"><path fill-rule="evenodd" d="M7.487 2.89a.75.75 0 1 0-1.474-.28l-.455 2.388a.75.75 0 1 0 1.474.28l.455-2.388Zm4.095.99a.75.75 0 1 0-1.06-1.06L9.22 4.122a.75.75 0 1 0 1.06 1.06l1.302-1.302ZM2.28 8a.75.75 0 1 0-.28-1.474l-2.388.455a.75.75 0 1 0 .28 1.474L2.28 8ZM8 2a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 2ZM5.122 9.22a.75.75 0 0 0 0-1.06L3.818 6.857a.75.75 0 0 0-1.06 1.06l1.304 1.303a.75.75 0 0 0 1.06 0ZM8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm3.25.75a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm-.44 3.22a.75.75 0 1 0 1.06-1.06l-1.3-1.302a.75.75 0 0 0-1.06 1.06l1.3 1.302Z" clip-rule="evenodd"/></svg>' +
            Number(rec.gps_spd).toFixed(1) + ' km/h</span>';
          if (hasHdg) gpsPills += '<span style="display:inline-flex;align-items:center;gap:3px;border-radius:3px;padding:1px 6px;font-size:0.7rem;font-weight:500;background:rgba(12,74,110,0.7);color:#7dd3fc">' + Number(rec.gps_hdg).toFixed(1) + '\u00b0</span>';
          telHtml += '<div style="display:flex;align-items:flex-start;gap:5px;">' +
            '<span style="font-size:0.65rem;color:#6b7280;flex-shrink:0;min-width:2.5rem;padding-top:2px;">GPS</span>' +
            '<div style="display:flex;flex-wrap:wrap;gap:4px;flex:1;">' + gpsPills + '</div></div>';
        }
        telHtml += '</div>';
      }
      return '<div class="grow flex flex-col gap-1.5"><p class="text-xs text-gray-400">Coordinates</p>' +
        '<p class="font-mono text-sm text-white">' + escHtml(rec.gps_lat) + ', ' + escHtml(rec.gps_lng) + '</p>' +
        sats + '</div>' + telHtml +
        '<button command="show-modal" commandfor="' + dlgId + '"' +
          ' class="mt-2 inline-flex w-fit items-center gap-1.5 rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-green-500">' +
          '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">' +
            '<path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />' +
          '</svg>View on Map</button>' +
        '<el-dialog><dialog id="' + dlgId + '" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">' +
          '<el-dialog-backdrop class="fixed inset-0 bg-gray-900/75 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>' +
          '<div tabindex="0" class="flex min-h-full items-center justify-center p-4 focus:outline focus:outline-0">' +
            '<el-dialog-panel class="relative w-full max-w-2xl overflow-hidden rounded-lg bg-gray-800 shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in data-[closed]:scale-95">' +
              '<div class="flex items-center justify-between px-4 py-3 border-b border-white/10">' +
                '<div><h3 class="text-sm font-semibold text-white">' + escHtml(rec.duck_id) + ' \u2014 GPS Location</h3>' +
                '<p class="text-xs text-gray-400 mt-0.5">Source: <span class="' + srcCls + '">' + escHtml(rec.gps_source_label) + '</span>' +
                  (rec.gps_sats !== null ? ' &bull; ' + escHtml(rec.gps_sats) + ' satellites' : '') +
                  (rec.gps_alt  !== null && rec.gps_alt !== undefined ? ' &bull; ' + Number(rec.gps_alt).toFixed(1) + ' m &bull; ' + Number(rec.gps_spd || 0).toFixed(1) + ' km/h &bull; ' + Number(rec.gps_hdg || 0).toFixed(1) + '&deg;' : '') +
                '</p></div>' +
                '<button command="close" commandfor="' + dlgId + '" class="text-gray-400 hover:text-white text-lg leading-none">&times;</button>' +
              '</div>' +
              '<div class="w-full h-96"><iframe src="' + escHtml(rec.map_embed_url) + '" class="w-full h-full border-0" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>' +
              '<div class="flex justify-end gap-3 px-4 py-3 border-t border-white/10">' +
                '<a href="' + escHtml(rec.map_url) + '" target="_blank" rel="noopener noreferrer" class="text-xs text-yellow-400 hover:text-yellow-300">Open in Google Maps &rarr;</a>' +
                '<button command="close" commandfor="' + dlgId + '" class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/20">Close</button>' +
              '</div>' +
            '</el-dialog-panel></div>' +
        '</dialog></el-dialog>';
    }
    return '<p class="mt-1 text-xs text-gray-500">No location data in this record.</p>';
  }

  function applyGpsFilters() {
    var q   = document.getElementById('gps-search').value.trim().toLowerCase();
    var src = document.getElementById('source-filter').value;
    var visible = 0;

    document.querySelectorAll('#gps-cards-container [data-duck-id]').forEach(function (card) {
      var id      = card.getAttribute('data-duck-id').toLowerCase();
      var gpsSrc  = card.getAttribute('data-gps-src');

      var matchQ   = (q   === '' || id.includes(q));
      var matchSrc = (src === '' || gpsSrc === src);

      var show = matchQ && matchSrc;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    var empty = document.getElementById('gps-empty-state');
    var title = document.getElementById('gps-empty-title');
    var sub   = document.getElementById('gps-empty-sub');

    if (visible === 0) {
      empty.classList.remove('hidden');
      if (q !== '' && src === '') {
        title.textContent = 'No ducks matching "' + q + '"';
        sub.textContent   = 'Try a different duck ID.';
      } else if (q === '' && src !== '') {
        title.textContent = 'No ducks with source: ' + src;
        sub.textContent   = 'No GPS records with this source type are currently available.';
      } else {
        title.textContent = 'No GPS records found';
        sub.textContent   = 'Try adjusting the search or source filter.';
      }
    } else {
      empty.classList.add('hidden');
    }
  }

  document.getElementById('gps-search').addEventListener('input', applyGpsFilters);
  document.getElementById('source-filter').addEventListener('change', applyGpsFilters);

  // ── Request GPS (shared CSRF token) ─────────────────────────────────────
  var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
    ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    : '';

  // ── Request GPS from Duck (header dialog) ──────────────────────────────
  (function () {
    var dialog    = document.getElementById('gps-request-dialog');
    var form      = document.getElementById('gps-request-form');
    var duckInput = document.getElementById('gps-request-duck-id');
    var sendBtn   = document.getElementById('gps-request-send-btn');
    var statusSpan = document.getElementById('gps-request-status');

    dialog.addEventListener('close', function () {
      duckInput.value = '';
      statusSpan.textContent = '';
      sendBtn.disabled = false;
      sendBtn.textContent = 'Send Request';
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var duckId = duckInput.value.trim();
      if (!duckId) {
        statusSpan.textContent = 'Duck ID is required.';
        statusSpan.style.color = '#f87171';
        return;
      }
      sendBtn.disabled = true;
      sendBtn.textContent = 'Requesting…';
      statusSpan.textContent = '';

      fetch('/gps/request', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ duck_id: duckId }),
      })
      .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
      .then(function (result) {
        if (result.ok) {
          dialog.close();
        } else {
          statusSpan.textContent = result.d.message || 'Request failed.';
          statusSpan.style.color = '#f87171';
          sendBtn.disabled = false;
          sendBtn.textContent = 'Send Request';
        }
      })
      .catch(function () {
        statusSpan.textContent = 'Network error.';
        statusSpan.style.color = '#f87171';
        sendBtn.disabled = false;
        sendBtn.textContent = 'Send Request';
      });
    });
  })();

  // ── Poll toggle (per-card) — delegated ──────────────────────────────────
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.poll-toggle-btn');
    if (!btn) return;
    var duckId = btn.getAttribute('data-duck-id');
    var card = btn.closest('[data-duck-id]');
    var intervalSelect = card ? card.querySelector('.poll-interval-select[data-duck-id="' + CSS.escape(duckId) + '"]') : null;
    var intervalMinutes = intervalSelect ? parseInt(intervalSelect.value, 10) : (parseInt(btn.getAttribute('data-poll-interval'), 10) || 1);
    btn.disabled = true;

    fetch('/gps/poll/toggle', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ duck_id: duckId, interval_minutes: intervalMinutes }),
    })
    .then(function (res) { return res.ok ? res.json() : null; })
    .then(function (data) {
      btn.disabled = false;
      if (!data) return;
      var enabled = data.enabled;
      var nextAt  = data.next_run_at;
      var interval = data.interval_minutes || intervalMinutes;

      btn.setAttribute('data-poll-enabled', enabled ? '1' : '0');
      btn.setAttribute('data-poll-interval', interval);
      btn.className = enabled
        ? 'poll-toggle-btn inline-flex shrink-0 items-center gap-1 rounded px-2 py-0.5 text-xs ring-1 ring-inset transition-colors bg-cyan-500/20 text-cyan-400 ring-cyan-500/30'
        : 'poll-toggle-btn inline-flex shrink-0 items-center gap-1 rounded px-2 py-0.5 text-xs ring-1 ring-inset transition-colors bg-white/5 text-gray-500 ring-white/10 hover:bg-white/10';
      btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3 shrink-0">' + ICON_CLOCK + '</svg>' +
        (enabled ? 'Polling \u00b7 ' + interval + 'min' : 'Auto-poll');

      if (card) {
        var nextEl = card.querySelector('[data-poll-next="' + duckId + '"]');
        if (nextEl) {
          if (enabled && nextAt) {
            nextEl.textContent = secsLabel(nextAt);
            nextEl.classList.remove('hidden');
          } else {
            nextEl.textContent = '';
            nextEl.classList.add('hidden');
          }
        }
      }
    })
    .catch(function () { btn.disabled = false; });
  });

  // ── Poll interval selector (per-card) — delegated ───────────────────────
  document.addEventListener('change', function (e) {
    var sel = e.target.closest('.poll-interval-select');
    if (!sel) return;
    var duckId = sel.getAttribute('data-duck-id');
    var intervalMinutes = parseInt(sel.value, 10);
    sel.disabled = true;

    fetch('/gps/poll/interval', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ duck_id: duckId, interval_minutes: intervalMinutes }),
    })
    .then(function (res) { return res.ok ? res.json() : null; })
    .then(function (data) {
      sel.disabled = false;
      if (!data) return;
      var card = sel.closest('[data-duck-id]');
      if (!card) return;
      var btn = card.querySelector('.poll-toggle-btn');
      if (btn) {
        btn.setAttribute('data-poll-interval', data.interval_minutes);
        if (data.enabled) {
          btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3 shrink-0">' + ICON_CLOCK + '</svg>' +
            'Polling \u00b7 ' + data.interval_minutes + 'min';
        }
      }
      var nextEl = card.querySelector('[data-poll-next="' + duckId + '"]');
      if (nextEl && data.enabled && data.next_run_at) {
        nextEl.textContent = secsLabel(data.next_run_at);
        nextEl.classList.remove('hidden');
      }
    })
    .catch(function () { sel.disabled = false; });
  });

  // ── GPS History / replay modal ──────────────────────────────────────────
  var HISTORY_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M8 1a7 7 0 1 0 4.95 11.95.75.75 0 0 0-1.06-1.06A5.5 5.5 0 1 1 13.5 8a.75.75 0 0 0 1.5 0A7 7 0 0 0 8 1Zm0 3a.75.75 0 0 1 .75.75v3.5l2.22 1.28a.75.75 0 0 1-.75 1.3l-2.6-1.5A.75.75 0 0 1 7.25 9V4.75A.75.75 0 0 1 8 4Z" clip-rule="evenodd" /></svg> History';
  var historyMap = null;
  var historyLayer = null;

  function renderBatteryTrend(points) {
    var container = document.getElementById('gps-history-battery');
    var caption   = document.getElementById('gps-history-battery-caption');
    container.innerHTML = '';
    var withBatt = points.filter(function (p) { return p.batt !== null && p.batt !== undefined; });
    if (!withBatt.length) {
      container.innerHTML = '<span class="text-xs text-gray-600 italic">No battery data recorded.</span>';
      caption.textContent = '';
      return;
    }
    var first = withBatt[0].batt;
    var last  = withBatt[withBatt.length - 1].batt;
    var delta = last - first;
    caption.textContent = first + '% \u2192 ' + last + '%' + (delta !== 0 ? ' (' + (delta > 0 ? '+' : '') + delta + '%)' : '');
    withBatt.forEach(function (p) {
      var pct = Math.max(0, Math.min(100, p.batt));
      var bar = document.createElement('div');
      bar.title = pct + '% \u00b7 ' + p.label;
      bar.className = 'flex-1 rounded-sm ' + (pct < 20 ? 'bg-red-500' : pct < 50 ? 'bg-yellow-500' : 'bg-green-500');
      bar.style.height = Math.max(4, (pct / 100) * 48) + 'px';
      container.appendChild(bar);
    });
  }

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.gps-history-btn');
    if (!btn) return;
    var duckId = btn.getAttribute('data-duck-id');
    var statusEl = document.getElementById('gps-history-status');
    document.getElementById('gps-history-duck-id').textContent = duckId;
    statusEl.textContent = 'Loading\u2026';

    var dialog = document.getElementById('gps-history-dialog');
    if (dialog.showModal) dialog.showModal();

    if (!historyMap) {
      historyMap = L.map('gps-history-map');
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
      }).addTo(historyMap);
    }
    setTimeout(function () { historyMap.invalidateSize(); }, 50);

    fetch('/gps/history/' + encodeURIComponent(duckId), { headers: { 'Accept': 'application/json' } })
      .then(function (res) { return res.ok ? res.json() : null; })
      .then(function (result) {
        var points = (result && result.data) || [];
        if (historyLayer) { historyMap.removeLayer(historyLayer); historyLayer = null; }
        renderBatteryTrend(points);

        if (!points.length) {
          statusEl.textContent = 'No GPS history recorded for this duck yet.';
          historyMap.setView([0, 0], 2);
          return;
        }

        statusEl.textContent = points.length + ' fix' + (points.length === 1 ? '' : 'es') + ' plotted.';
        var latlngs = points.map(function (p) { return [p.lat, p.lng]; });
        var group = L.featureGroup();
        L.polyline(latlngs, { color: '#facc15', weight: 3 }).addTo(group);
        points.forEach(function (p, i) {
          L.circleMarker([p.lat, p.lng], {
            radius: i === points.length - 1 ? 6 : 4,
            color: i === points.length - 1 ? '#22d3ee' : '#facc15',
            fillOpacity: 0.8,
          }).bindPopup(escHtml(p.label) + '<br>' + escHtml(p.source) + (p.batt !== null && p.batt !== undefined ? ' \u00b7 ' + p.batt + '%' : '')).addTo(group);
        });
        historyLayer = group.addTo(historyMap);
        historyMap.fitBounds(group.getBounds().pad(0.15));
      })
      .catch(function () {
        statusEl.textContent = 'Failed to load history.';
      });
  });

  // ── Request GPS (per-card buttons) — delegated so new cards work ────────
  var GPS_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path fill-rule="evenodd" d="M7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd" /></svg> Request';

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.gps-request-btn');
    if (!btn) return;
    var duckId = btn.getAttribute('data-duck-id');
    btn.disabled = true;
    btn.textContent = 'Requesting…';

    fetch('/gps/request', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify({ duck_id: duckId }),
    })
    .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, d: d }; }); })
    .then(function (result) {
      btn.textContent = result.ok ? 'Sent!' : 'Failed';
      setTimeout(function () { btn.disabled = false; btn.innerHTML = GPS_ICON_SVG; }, 2000);
    })
    .catch(function () {
      btn.textContent = 'Error';
      setTimeout(function () { btn.disabled = false; btn.innerHTML = GPS_ICON_SVG; }, 2000);
    });
  });

  // ── Build a full card element for a duck never seen before ─────────────
  function buildGpsCard(rec) {
    var duckId    = rec.duck_id;
    var srcLabel  = rec.gps_source_label;
    var badgeLbl  = rec.gps_badge_label || srcLabel;
    return '<div class="flex flex-col divide-y divide-white/10 overflow-hidden rounded-lg bg-gray-800/50 outline outline-1 -outline-offset-1 outline-white/10"' +
      ' data-duck-id="'       + escHtml(duckId)          + '"' +
      ' data-gps-src="'       + escHtml(srcLabel)         + '"' +
      ' data-gps-record-id="' + escHtml(String(rec.id))   + '">' +
      '<div class="px-4 py-4 sm:px-6 flex flex-col gap-2">' +
        '<div class="flex items-center justify-between">' +
          '<span class="text-sm font-semibold text-white">' + escHtml(duckId) + '</span>' +
          '<span class="' + badgeClass(badgeLbl) + '" data-gps-badge="' + escHtml(duckId) + '">' + escHtml(badgeLbl) + '</span>' +
        '</div>' +
        pollToggleBtnHtml(duckId, false, null, rec.poll_interval_minutes || 1) +
      '</div>' +
      '<div class="px-4 py-3 sm:px-6 flex flex-col gap-1.5 grow" data-gps-body="' + escHtml(duckId) + '">' +
        buildGpsBody(rec) +
      '</div>' +
      '<div class="px-4 py-3 sm:px-6 flex flex-col gap-2">' +
        '<div class="flex flex-col gap-0.5">' +
          '<span class="text-sm text-white" data-gps-ts="' + escHtml(duckId) + '">' + escHtml(rec.created_at_for_humans) + '</span>' +
          '<span class="text-xs text-gray-500" data-gps-ts-abs="' + escHtml(duckId) + '">' + escHtml(rec.created_at_formatted) + '</span>' +
        '</div>' +
        '<div class="flex items-center gap-1.5">' +
          '<button type="button"' +
            ' class="gps-history-btn inline-flex flex-1 items-center justify-center gap-1 rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 disabled:opacity-50"' +
            ' data-duck-id="' + escHtml(duckId) + '">' +
            HISTORY_ICON_SVG +
          '</button>' +
          '<button type="button"' +
            ' class="gps-request-btn inline-flex flex-1 items-center justify-center gap-1 rounded-md bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 disabled:opacity-50"' +
            ' data-duck-id="' + escHtml(duckId) + '">' +
            GPS_ICON_SVG +
          '</button>' +
        '</div>' +
      '</div>' +
    '</div>';
  }

  // ── Live polling ─────────────────────────────────────────────────────────
  function pollGps() {
    fetch('/gps/json', { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data) return;
        var container  = document.getElementById('gps-cards-container');
        var emptyState = document.getElementById('gps-empty-state');
        var hadNew     = false;

        Object.keys(data).forEach(function (duckId) {
          var rec  = data[duckId];
          var card = container.querySelector('[data-duck-id="' + CSS.escape(duckId) + '"]');

          if (!card) {
            // New duck: build and inject a card before the empty-state sentinel.
            var tmp = document.createElement('div');
            tmp.innerHTML = buildGpsCard(rec);
            card = tmp.firstElementChild;
            container.insertBefore(card, emptyState);
            hadNew = true;
          } else {
            // Existing duck: update in place.
            card.setAttribute('data-gps-src', rec.gps_source_label);

            var tsEl = card.querySelector('[data-gps-ts]');
            if (tsEl) tsEl.textContent = rec.created_at_for_humans;
            var tsAbsEl = card.querySelector('[data-gps-ts-abs]');
            if (tsAbsEl) tsAbsEl.textContent = rec.created_at_formatted;

            var badgeEl = card.querySelector('[data-gps-badge]');
            if (badgeEl) {
              var lbl = rec.gps_badge_label || rec.gps_source_label;
              badgeEl.className   = badgeClass(lbl);
              badgeEl.textContent = lbl;
            }

            if (card.getAttribute('data-gps-record-id') !== String(rec.id)) {
              if (!card.querySelector('dialog[open]')) {
                var bodyEl = card.querySelector('[data-gps-body]');
                if (bodyEl) bodyEl.innerHTML = buildGpsBody(rec);
                card.setAttribute('data-gps-record-id', rec.id);
              }
            }

            // Sync poll state if it changed server-side (e.g. scheduler toggled next_run_at).
            var pollBtn = card.querySelector('.poll-toggle-btn');
            if (pollBtn && typeof rec.poll_enabled !== 'undefined') {
              var pollEnabled = !!rec.poll_enabled;
              var curEnabled  = pollBtn.getAttribute('data-poll-enabled') === '1';
              var pollInterval = rec.poll_interval_minutes || 1;
              var curInterval  = parseInt(pollBtn.getAttribute('data-poll-interval'), 10) || 1;
              if (pollEnabled !== curEnabled || pollInterval !== curInterval) {
                pollBtn.setAttribute('data-poll-enabled', pollEnabled ? '1' : '0');
                pollBtn.setAttribute('data-poll-interval', pollInterval);
                pollBtn.className = pollEnabled
                  ? 'poll-toggle-btn inline-flex shrink-0 items-center gap-1 rounded px-2 py-0.5 text-xs ring-1 ring-inset transition-colors bg-cyan-500/20 text-cyan-400 ring-cyan-500/30'
                  : 'poll-toggle-btn inline-flex shrink-0 items-center gap-1 rounded px-2 py-0.5 text-xs ring-1 ring-inset transition-colors bg-white/5 text-gray-500 ring-white/10 hover:bg-white/10';
                pollBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3 shrink-0">' + ICON_CLOCK + '</svg>' +
                  (pollEnabled ? 'Polling \u00b7 ' + pollInterval + 'min' : 'Auto-poll');
                var intervalSelect = card.querySelector('.poll-interval-select[data-duck-id="' + CSS.escape(duckId) + '"]');
                if (intervalSelect && document.activeElement !== intervalSelect) intervalSelect.value = pollInterval;
              }
              var nextEl = card.querySelector('[data-poll-next="' + duckId + '"]');
              if (nextEl) {
                if (pollEnabled && rec.poll_next_at) {
                  nextEl.textContent = secsLabel(rec.poll_next_at);
                  nextEl.classList.remove('hidden');
                } else {
                  nextEl.textContent = '';
                  nextEl.classList.add('hidden');
                }
              }
            }
          }
        });

        // Re-apply search/source filters so new cards respect any active filter.
        if (hadNew) applyGpsFilters();
      })
      .catch(function () {});
  }

  pollGps();
  setInterval(pollGps, 10_000);
</script>
@endsection
</x-layouts::app>
