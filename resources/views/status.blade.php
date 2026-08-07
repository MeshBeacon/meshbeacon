<x-layouts::app :title="__('status')">
@section('page-actions')
  <div class="flex items-center gap-2">
    <button command="show-modal" commandfor="send-duck-dialog"
      class="inline-flex items-center gap-1.5 rounded-md bg-white/10 px-3 py-1.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/10 hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
        <path fill-rule="evenodd" d="M1 8.74C1 9.99 2.01 11 3.26 11h1.04l.56 2.8a.75.75 0 0 0 1.46-.2L6.998 11H9.5C10.881 11 12 9.881 12 8.5v-5C12 2.119 10.881 1 9.5 1h-6C2.119 1 1 2.119 1 3.5v5.24ZM13 5.36c.44.17.75.6.75 1.11V8.5a3 3 0 0 1-3 3H9.499l-.044.222A2.75 2.75 0 0 0 12 9.5V4.25c.57.22 1 .77 1 1.11Z" clip-rule="evenodd" />
      </svg>
      {{ __('Send Message to Duck') }}
    </button>
    <button type="button" id="open-broadcast-btn"
      class="inline-flex items-center gap-1.5 rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white ring-1 ring-inset ring-red-500 hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
        <path d="M13.488 2.513a1.75 1.75 0 0 0-2.475 0L6.75 6.774a2.75 2.75 0 0 0-.596.892L5.18 9.817a.75.75 0 0 0 .985.985l2.15-.975a2.75 2.75 0 0 0 .892-.596l4.262-4.262a1.75 1.75 0 0 0 0-2.475ZM3.5 6.75A3.25 3.25 0 0 1 6.75 3.5h.75a.75.75 0 0 1 0 1.5h-.75A1.75 1.75 0 0 0 5 6.75v5.5c0 .966.784 1.75 1.75 1.75h5.5A1.75 1.75 0 0 0 14 12.25v-.75a.75.75 0 0 1 1.5 0v.75A3.25 3.25 0 0 1 12.25 15.5h-5.5A3.25 3.25 0 0 1 3.5 12.25v-5.5Z" />
      </svg>
      {{ __('Emergency Broadcast') }}
    </button>
  </div>
@endsection
@section('content')
<style>
  @keyframes critical-glow {
    0%, 100% { box-shadow: 0 0 10px 2px rgba(239,68,68,0.25), 0 0 0 2px rgba(239,68,68,0.6); }
    50%       { box-shadow: 0 0 24px 6px rgba(239,68,68,0.5), 0 0 0 2px rgba(239,68,68,1); }
  }
  .critical-card { animation: critical-glow 2s ease-in-out infinite; }
  .gps-toggle-map {
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
    margin-left: 0.75rem;
    border-radius: 0.25rem;
    background-color: #16a34a;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1rem;
    font-weight: 600;
    color: #fff;
    cursor: pointer;
    border: none;
    text-decoration: none;
  }
  .gps-toggle-map:hover { background-color: #15803d; color: #fff; }
  .gps-copy-coords {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    cursor: pointer;
    color: #9ca3af;
    padding: 2px;
    border-radius: 4px;
  }
  .gps-copy-coords:hover { color: #fff; background: rgba(255,255,255,0.1); }
</style>
<div class="flex flex-col">
  <!-- Toast notification -->
  <div id="broadcast-toast" class="pointer-events-none fixed bottom-5 right-5 z-50 flex items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold shadow-lg transition-all duration-300 opacity-0 translate-y-2" role="alert" aria-live="assertive">
    <svg id="broadcast-toast-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0">
      <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" />
    </svg>
    <span id="broadcast-toast-msg"></span>
  </div>

  <div class="mb-4 flex items-center justify-between">
    <h1 class="text-base font-semibold text-white">{{ __('Duck Status') }}</h1>
    <div class="flex items-center gap-2">
      <label class="inline-flex cursor-pointer items-center gap-2 select-none" title="{{ __('Show online ducks only') }}">
        <span class="text-xs text-gray-400 whitespace-nowrap">{{ __('Online only') }}</span>
        <div class="relative">
          <input type="checkbox" id="online-only-toggle" class="sr-only peer">
          <div class="w-9 h-5 rounded-full bg-gray-600 peer-checked:bg-green-500 transition-colors duration-200 peer-focus-visible:outline peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-green-500"></div>
          <div class="absolute top-0.5 left-0.5 size-4 rounded-full bg-white transition-transform duration-200 peer-checked:translate-x-4"></div>
        </div>
      </label>

      <el-select id="incident-filter" name="incident-filter" value="" class="block w-36">
        <button type="button" class="grid w-full cursor-default grid-cols-1 rounded-md bg-white/5 py-1.5 pl-3 pr-2 text-left text-white outline outline-1 -outline-offset-1 outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500 sm:text-sm/6">
          <el-selectedcontent class="col-start-1 row-start-1 truncate pr-6">{{ __('All Incidents') }}</el-selectedcontent>
          <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-400 sm:size-4">
            <path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
          </svg>
        </button>
        <el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">
          @foreach ([['', __('All Incidents')], ['any', __('Has Incident')], ['open', __('Open')], ['acknowledged', __('Acknowledged')], ['responding', __('Responding')]] as [$val, $label])
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

      <el-select id="urgency-filter" name="urgency-filter" value="" class="block w-36">
        <button type="button" class="grid w-full cursor-default grid-cols-1 rounded-md bg-white/5 py-1.5 pl-3 pr-2 text-left text-white outline outline-1 -outline-offset-1 outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500 sm:text-sm/6">
          <el-selectedcontent class="col-start-1 row-start-1 truncate pr-6">{{ __('All Urgency') }}</el-selectedcontent>
          <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-400 sm:size-4">
            <path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
          </svg>
        </button>
        <el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">
          <el-option value="" class="group/option relative cursor-default select-none py-2 pl-8 pr-4 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
            <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('All Urgency') }}</span>
            <span class="absolute inset-y-0 left-0 flex items-center pl-1.5 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
              <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5">
                <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
              </svg>
            </span>
          </el-option>
          <el-option value="0" class="group/option relative cursor-default select-none py-2 pl-8 pr-4 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
            <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('Low') }}</span>
            <span class="absolute inset-y-0 left-0 flex items-center pl-1.5 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
              <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5">
                <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
              </svg>
            </span>
          </el-option>
          <el-option value="1" class="group/option relative cursor-default select-none py-2 pl-8 pr-4 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
            <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('Medium') }}</span>
            <span class="absolute inset-y-0 left-0 flex items-center pl-1.5 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
              <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5">
                <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
              </svg>
            </span>
          </el-option>
          <el-option value="2" class="group/option relative cursor-default select-none py-2 pl-8 pr-4 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
            <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('Critical') }}</span>
            <span class="absolute inset-y-0 left-0 flex items-center pl-1.5 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
              <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-5">
                <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
              </svg>
            </span>
          </el-option>
        </el-options>
      </el-select>

      <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-gray-400">
            <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
          </svg>
        </div>
        <input id="duck-search" type="text" placeholder="{{ __('Search duck ID…') }}"
          class="w-56 rounded-md bg-gray-800 py-1.5 pl-9 pr-3 text-sm text-white placeholder-gray-500 outline outline-1 -outline-offset-1 outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-yellow-500">
      </div>
    </div>
  </div>
  <div id="duck-cards-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
@foreach ($mamaducks as $mamaduck)
<div class="{{ $mamaduck->urgency === \App\Enums\Urgency::Critical ? 'critical-card flex flex-col divide-y divide-red-500/30 overflow-hidden rounded-lg bg-red-950/40 outline outline-2 -outline-offset-2 outline-red-500' : 'flex flex-col divide-y divide-white/10 overflow-hidden rounded-lg bg-gray-800/50 outline outline-1 -outline-offset-1 outline-white/10' }}" data-duck-id="{{ $mamaduck->duck_id }}" data-urgency="{{ $mamaduck->urgency !== null ? $mamaduck->urgency->value : '' }}" data-online="{{ $mamaduck->created_at->gt(now()->subHour()) ? '1' : '0' }}" data-incident-status="{{ ($activeIncidents[$mamaduck->duck_id] ?? null)?->status ?? '' }}">
  <!-- Header -->
  <div class="{{ $mamaduck->urgency === \App\Enums\Urgency::Critical ? 'px-4 py-4 sm:px-6 flex flex-col gap-2 bg-red-900/50' : 'px-4 py-4 sm:px-6 flex flex-col gap-2' }}">
    <div class="flex items-center justify-between">
      <span class="{{ $mamaduck->urgency === \App\Enums\Urgency::Critical ? 'text-sm font-bold text-red-300 tracking-wide' : 'text-sm font-semibold text-white' }}">
        {{ $mamaduck->duck_id }}
      </span>
      @php
        $activeInc = $activeIncidents[$mamaduck->duck_id] ?? null;
        $incStatus = $activeInc?->status;
        $incLabel = match($incStatus) {
          'acknowledged' => __("ACK'D"),
          'responding'   => __('RESP'),
          default        => __('OPEN'),
        };
        $incClass = match($incStatus) {
          'acknowledged' => 'bg-amber-500/20 text-amber-300 ring-amber-500/40',
          'responding'   => 'bg-blue-500/20 text-blue-300 ring-blue-500/40',
          default        => 'bg-red-500/20 text-red-300 ring-red-500/40',
        };
      @endphp
      <div class="flex items-center gap-1.5">
        <a href="/dashboard#incidents" data-incident-badge-duck="{{ $mamaduck->duck_id }}"
          class="{{ $activeInc ? '' : '!hidden' }} inline-flex items-center rounded px-1.5 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $incClass }}">{!! $incLabel !!}</a>
        <button type="button" data-status-duck="{{ $mamaduck->duck_id }}" class="rounded bg-green-500 px-2 py-1 text-xs font-semibold text-white hover:bg-green-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-500">{{ __('Online') }}</button>
      </div>
    </div>
  </div>
  <!-- Body -->
  <div class="flex-1 px-4 py-5 sm:p-6">
    {{-- Hidden anchor keeps the JS data-payload-duck hook alive for live updates --}}
    <span class="sr-only" data-payload-duck="{{ $mamaduck->duck_id }}"></span>
    <div data-card-body-duck="{{ $mamaduck->duck_id }}">
    @if (!$mamaduck->sos_from_device && !$mamaduck->sos_from_mobile && !$mamaduck->roger_from_device)
      <p class="text-sm text-gray-400 text-wrap break-words">{{ $mamaduck->display_text }}</p>
    @endif
    @if ($mamaduck->sos_from_device)
      <div class="flex items-start gap-2 rounded-md bg-red-900/50 px-3 py-2 ring-1 ring-inset ring-red-500/40">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-red-400">
          <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="text-xs font-semibold text-red-400">{{ __('SOS — Hardware Button Triggered') }}</p>
          <p class="text-xs text-red-300/80">{{ __('This SOS was sent because the physical SOS button on the device was pressed.') }}</p>
          @if ($mamaduck->gps_batt !== null || $mamaduck->gps_alt !== null || $mamaduck->gps_spd !== null || $mamaduck->gps_hdg !== null)
            <div class="mt-1.5 flex flex-col gap-1.5">
              @if ($mamaduck->gps_batt !== null)
                <div class="flex items-start gap-1.5">
                  <span class="text-xs text-gray-500 w-10 shrink-0 pt-0.5">{{ __('Device') }}</span>
                  <div class="flex flex-wrap gap-1.5 flex-1">
                  <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium {{ $mamaduck->gps_batt < 20 ? 'bg-red-800/60 text-red-300' : ($mamaduck->gps_batt < 50 ? 'bg-yellow-800/60 text-yellow-300' : 'bg-green-800/60 text-green-300') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>
                    {{ $mamaduck->gps_batt }}%
                  </span>
                  </div>
                </div>
              @endif
              @if ($mamaduck->gps_alt !== null || $mamaduck->gps_spd !== null || $mamaduck->gps_hdg !== null)
                <div class="flex items-start gap-1.5">
                  <span class="text-xs text-gray-500 w-10 shrink-0 pt-0.5">{{ __('GPS') }}</span>
                  <div class="flex flex-wrap gap-1.5 flex-1">
                  @if ($mamaduck->gps_alt !== null)
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-blue-800/60 text-blue-200">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M8 1.75a.75.75 0 0 1 .674.418l1.882 3.815 4.21.612a.75.75 0 0 1 .416 1.279l-3.046 2.97.719 4.192a.75.75 0 0 1-1.088.791L8 13.347l-3.767 1.98a.75.75 0 0 1-1.088-.79l.72-4.194L.818 7.874a.75.75 0 0 1 .416-1.28l4.21-.611L7.327 2.17A.75.75 0 0 1 8 1.75Z" clip-rule="evenodd"/></svg>
                      {{ number_format($mamaduck->gps_alt, 1) }} m
                    </span>
                  @endif
                  @if ($mamaduck->gps_spd !== null)
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-purple-800/60 text-purple-200">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M7.487 2.89a.75.75 0 1 0-1.474-.28l-.455 2.388a.75.75 0 1 0 1.474.28l.455-2.388Zm4.095.99a.75.75 0 1 0-1.06-1.06L9.22 4.122a.75.75 0 1 0 1.06 1.06l1.302-1.302ZM2.28 8a.75.75 0 1 0-.28-1.474l-2.388.455a.75.75 0 1 0 .28 1.474L2.28 8ZM8 2a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 2ZM5.122 9.22a.75.75 0 0 0 0-1.06L3.818 6.857a.75.75 0 0 0-1.06 1.06l1.304 1.303a.75.75 0 0 0 1.06 0ZM8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm3.25.75a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm-.44 3.22a.75.75 0 1 0 1.06-1.06l-1.3-1.302a.75.75 0 0 0-1.06 1.06l1.3 1.302Z" clip-rule="evenodd"/></svg>
                      {{ number_format($mamaduck->gps_spd, 1) }} km/h
                    </span>
                  @endif
                  @if ($mamaduck->gps_hdg !== null)
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-sky-800/60 text-sky-200">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M8 14a6 6 0 1 0 0-12A6 6 0 0 0 8 14ZM9.25 5.75A.75.75 0 0 0 8 5.134a.75.75 0 0 0-1.25.616v4.5a.75.75 0 0 0 1.25.616.75.75 0 0 0 1.25-.616v-4.5Z" clip-rule="evenodd"/></svg>
                      {{ number_format($mamaduck->gps_hdg, 1) }}&deg;
                    </span>
                  @endif
                  </div>
                </div>
              @endif
            </div>
          @endif
        </div>
      </div>
    @elseif ($mamaduck->sos_from_mobile)
      <div class="flex items-start gap-2 rounded-md bg-orange-900/50 px-3 py-2 ring-1 ring-inset ring-orange-500/40">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-orange-400">
          <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="text-xs font-semibold text-orange-400">{{ __('SOS — Mobile Phone Triggered') }}</p>
          <p class="text-xs text-orange-300/80">{{ __('This SOS was sent from the user\'s mobile phone application and should include GPS coordinates.') }}</p>
          @if ($mamaduck->gps_batt !== null || $mamaduck->gps_alt !== null || $mamaduck->gps_spd !== null || $mamaduck->gps_hdg !== null)
            <div class="mt-1.5 flex flex-col gap-1.5">
              @if ($mamaduck->gps_batt !== null)
                <div class="flex items-start gap-1.5">
                  <span class="text-xs text-gray-500 w-10 shrink-0 pt-0.5">{{ __('Device') }}</span>
                  <div class="flex flex-wrap gap-1.5 flex-1">
                  <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium {{ $mamaduck->gps_batt < 20 ? 'bg-red-800/60 text-red-300' : ($mamaduck->gps_batt < 50 ? 'bg-yellow-800/60 text-yellow-300' : 'bg-green-800/60 text-green-300') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path d="M2 6a2 2 0 0 1 2-2h7.5a.5.5 0 0 1 .5.5v1h.5a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H12v1a.5.5 0 0 1-.5.5H4a2 2 0 0 1-2-2V6Z"/></svg>
                    {{ $mamaduck->gps_batt }}%
                  </span>
                  </div>
                </div>
              @endif
              @if ($mamaduck->gps_alt !== null || $mamaduck->gps_spd !== null || $mamaduck->gps_hdg !== null)
                <div class="flex items-start gap-1.5">
                  <span class="text-xs text-gray-500 w-10 shrink-0 pt-0.5">{{ __('GPS') }}</span>
                  <div class="flex flex-wrap gap-1.5 flex-1">
                  @if ($mamaduck->gps_alt !== null)
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-blue-800/60 text-blue-200">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M8 1.75a.75.75 0 0 1 .674.418l1.882 3.815 4.21.612a.75.75 0 0 1 .416 1.279l-3.046 2.97.719 4.192a.75.75 0 0 1-1.088.791L8 13.347l-3.767 1.98a.75.75 0 0 1-1.088-.79l.72-4.194L.818 7.874a.75.75 0 0 1 .416-1.28l4.21-.611L7.327 2.17A.75.75 0 0 1 8 1.75Z" clip-rule="evenodd"/></svg>
                      {{ number_format($mamaduck->gps_alt, 1) }} m
                    </span>
                  @endif
                  @if ($mamaduck->gps_spd !== null)
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-purple-800/60 text-purple-200">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M7.487 2.89a.75.75 0 1 0-1.474-.28l-.455 2.388a.75.75 0 1 0 1.474.28l.455-2.388Zm4.095.99a.75.75 0 1 0-1.06-1.06L9.22 4.122a.75.75 0 1 0 1.06 1.06l1.302-1.302ZM2.28 8a.75.75 0 1 0-.28-1.474l-2.388.455a.75.75 0 1 0 .28 1.474L2.28 8ZM8 2a.75.75 0 0 1 .75.75v2.5a.75.75 0 0 1-1.5 0v-2.5A.75.75 0 0 1 8 2ZM5.122 9.22a.75.75 0 0 0 0-1.06L3.818 6.857a.75.75 0 0 0-1.06 1.06l1.304 1.303a.75.75 0 0 0 1.06 0ZM8 7a1 1 0 1 1 0 2 1 1 0 0 1 0-2Zm3.25.75a.75.75 0 0 1 .75-.75h2.5a.75.75 0 0 1 0 1.5h-2.5a.75.75 0 0 1-.75-.75Zm-.44 3.22a.75.75 0 1 0 1.06-1.06l-1.3-1.302a.75.75 0 0 0-1.06 1.06l1.3 1.302Z" clip-rule="evenodd"/></svg>
                      {{ number_format($mamaduck->gps_spd, 1) }} km/h
                    </span>
                  @endif
                  @if ($mamaduck->gps_hdg !== null)
                    <span class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 text-xs font-medium bg-sky-800/60 text-sky-200">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 shrink-0"><path fill-rule="evenodd" d="M8 14a6 6 0 1 0 0-12A6 6 0 0 0 8 14ZM9.25 5.75A.75.75 0 0 0 8 5.134a.75.75 0 0 0-1.25.616v4.5a.75.75 0 0 0 1.25.616.75.75 0 0 0 1.25-.616v-4.5Z" clip-rule="evenodd"/></svg>
                      {{ number_format($mamaduck->gps_hdg, 1) }}&deg;
                    </span>
                  @endif
                  </div>
                </div>
              @endif
            </div>
          @endif
        </div>
      </div>
    @elseif ($mamaduck->roger_from_device)
      <div class="flex items-start gap-2 rounded-md bg-green-900/50 px-3 py-2 ring-2 ring-inset ring-green-500/60">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-green-400">
          <path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 0 1 .208 1.04l-5 7.5a.75.75 0 0 1-1.154.114l-3-3a.75.75 0 0 1 1.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
        </svg>
        <div>
          <p class="text-sm font-bold text-green-300 uppercase tracking-wide">{{ __('Roger — Device Confirmed') }}</p>
          <p class="text-xs text-green-400/80">{{ __('The person holding the device triple-clicked the button to confirm they have received your message.') }}</p>
        </div>
      </div>
    @endif
    </div>
    <div data-urgency-notice-duck="{{ $mamaduck->duck_id }}">
      @if (str_starts_with(strtoupper($mamaduck->payload ?? ''), 'MSG') && $mamaduck->urgency === \App\Enums\Urgency::Critical)
        <div class="mt-2 flex items-start gap-2 rounded-md bg-red-950 px-3 py-2 ring-2 ring-inset ring-red-500 animate-pulse">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 size-4 shrink-0 text-red-400">
            <path fill-rule="evenodd" d="M6.701 2.25c.577-1 2.02-1 2.598 0l5.196 9a1.5 1.5 0 0 1-1.299 2.25H2.804a1.5 1.5 0 0 1-1.3-2.25l5.197-9ZM8 4a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3A.75.75 0 0 1 8 4Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
          </svg>
          <div>
            <p class="text-xs font-bold text-red-400 uppercase tracking-wide">{{ __('Critical — Immediate Attention Required') }}</p>
            <p class="text-xs text-red-300/80">{{ __('This message has been marked as critical urgency and must be attended to immediately.') }}</p>
          </div>
        </div>
      @endif
    </div>
    <div data-gps-warning-duck="{{ $mamaduck->duck_id }}">
    @if ($mamaduck->gps_unavailable)
      <p class="mt-2 inline-flex items-center gap-1.5 text-xs text-yellow-400">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0">
          <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />
        </svg>
        {{ __('GPS location unavailable — no satellite fix') }}
      </p>
    @elseif ($mamaduck->gps_hardware_absent)
      <p class="mt-2 inline-flex items-center gap-1.5 text-xs text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5 shrink-0">
          <path fill-rule="evenodd" d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l10.5 10.5a.75.75 0 1 0 1.06-1.06L3.28 2.22ZM7 3.064V3a1 1 0 0 1 2 0v.064A5.002 5.002 0 0 1 12.9 7.5h.35a.75.75 0 0 1 0 1.5h-.55a5.003 5.003 0 0 1-1.196 2.547l.543.543a.75.75 0 1 1-1.06 1.06l-.543-.543A5.003 5.003 0 0 1 8.75 13.9V14a.75.75 0 0 1-1.5 0v-.1a5.003 5.003 0 0 1-2.694-1.293l-.543.543a.75.75 0 0 1-1.06-1.06l.543-.543A5.003 5.003 0 0 1 2.3 9H1.75a.75.75 0 0 1 0-1.5H2.1A5.002 5.002 0 0 1 7 3.064Z" clip-rule="evenodd" />
        </svg>
        {{ __('No GPS hardware — this device cannot report location') }}
      </p>
    @endif
    </div>
    @php $mapDialogId = 'map-dialog-' . $mamaduck->id; @endphp
    <div data-map-btn-duck="{{ $mamaduck->duck_id }}" class="{{ $mamaduck->map_url ? '' : 'hidden' }}">
      <button command="show-modal" commandfor="{{ $mapDialogId }}"
         class="mt-3 inline-flex items-center gap-1.5 rounded-md bg-green-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-green-500">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5">
          <path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 0 0 .912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 0 0 .19-.153 15.588 15.588 0 0 0 2.046-2.082c1.101-1.351 2.291-3.342 2.291-5.597A5 5 0 0 0 3 7c0 2.255 1.19 4.246 2.292 5.597a15.591 15.591 0 0 0 2.046 2.082 8.916 8.916 0 0 0 .189.153l.012.01ZM8 8.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" clip-rule="evenodd" />
        </svg>
        View on Map
      </button>
    </div>
    <el-dialog>
        <dialog id="{{ $mapDialogId }}" data-map-duck="{{ $mamaduck->duck_id }}" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
          <el-dialog-backdrop class="fixed inset-0 bg-gray-900/75 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>
          <div tabindex="0" class="flex min-h-full items-center justify-center p-4 focus:outline focus:outline-0">
            <el-dialog-panel class="relative w-full max-w-2xl overflow-hidden rounded-lg bg-gray-800 shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in data-[closed]:scale-95">
              <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                <div>
                  <h3 class="text-sm font-semibold text-white">{{ $mamaduck->duck_id }} &mdash; {{ __('GPS Location') }}</h3>
                  <p class="text-xs text-gray-400 mt-0.5" data-map-subtitle>
                    {{ __('Source:') }} <span class="{{ $mamaduck->gps_from_phone ? 'text-blue-400' : 'text-green-400' }}">{{ $mamaduck->gps_source_label }}</span>
                    @if ($mamaduck->gps_sats !== null)
                      &bull; {{ $mamaduck->gps_sats }} {{ __('satellites') }}
                    @endif
                    @if ($mamaduck->gps_alt !== null)
                      &bull; {{ number_format($mamaduck->gps_alt, 1) }} m &bull; {{ number_format($mamaduck->gps_spd ?? 0, 1) }} km/h &bull; {{ number_format($mamaduck->gps_hdg ?? 0, 1) }}&deg;
                    @endif
                  </p>
                </div>
                <button command="close" commandfor="{{ $mapDialogId }}" class="text-gray-400 hover:text-white text-lg leading-none">&times;</button>
              </div>
              <div class="w-full h-96">
                <iframe
                  data-map-iframe
                  src="{{ $mamaduck->map_embed_url }}"
                  class="w-full h-full border-0"
                  allowfullscreen
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade">
                </iframe>
              </div>
              <div class="flex justify-end gap-3 px-4 py-3 border-t border-white/10">
                <a href="{{ $mamaduck->map_url }}" target="_blank" rel="noopener noreferrer" data-map-ext-link class="text-xs text-yellow-400 hover:text-yellow-300">{{ __('Open in Google Maps') }} &rarr;</a>
                <button command="close" commandfor="{{ $mapDialogId }}" class="rounded-md bg-white/10 px-3 py-1.5 text-xs font-semibold text-white hover:bg-white/20">{{ __('Close') }}</button>
              </div>
            </el-dialog-panel>
          </div>
        </dialog>
      </el-dialog>
  </div>
  <!-- Footer -->
  <div class="px-4 py-4 sm:px-6 flex items-center justify-between">
    <span class="text-sm text-white" data-timestamp-duck="{{ $mamaduck->duck_id }}">{{ $mamaduck->created_at->diffForHumans() }}</span>
    <div>
      <!-- Include this script tag or install `@tailwindplus/elements` via npm: -->
      <!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script> -->
      <button command="show-modal" commandfor="msg-dialog-{{ $mamaduck->duck_id }}" class="rounded-md bg-white/10 px-2.5 py-1.5 text-sm font-semibold text-white ring-1 ring-inset ring-white/5 hover:bg-white/20">{{ __('Message') }}</button>
      <el-dialog>
        <dialog id="msg-dialog-{{ $mamaduck->duck_id }}" aria-labelledby="dialog-title" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/50 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>

        <div tabindex="0" class="flex min-h-full items-stretch justify-center p-0 text-center focus:outline focus:outline-0 sm:items-center sm:p-4">
          <el-dialog-panel class="relative flex h-full w-full transform flex-col overflow-hidden rounded-none bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:translate-y-4 data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in sm:my-8 sm:h-auto sm:max-w-sm sm:rounded-lg sm:p-6 data-[closed]:sm:translate-y-0 data-[closed]:sm:scale-95">

<form id="message-form-{{ $mamaduck->duck_id }}" class="duck-message-form flex h-full flex-col" action="/status/send">
  @csrf
  <input type="hidden" name="duck_id" value="{{ $mamaduck->duck_id }}">
  <div class="flex-1 space-y-12 overflow-y-auto">
    <div class="border-b border-white/10 pb-3">
      <h2 class="text-base/7 font-semibold text-white">{{ __('Message') }} <span class="font-mono text-yellow-400">{{ $mamaduck->duck_id }}</span></h2>
      <p class="mt-1 text-sm/6 text-gray-400">{{ __('This messaging is on a best-effort basis') }}</p>

        <!-- Last known GPS location — updated by pollHistory() -->
        <div data-gps-duck="{{ $mamaduck->duck_id }}" class="mt-3"></div>

        <!-- Conversation history — populated by pollHistory() in app.js -->
        <div class="mt-3 h-48 overflow-y-auto rounded-md bg-white/5 p-3 space-y-2 outline outline-1 -outline-offset-1 outline-white/10"
             data-history-duck="{{ $mamaduck->duck_id }}">
          <p class="text-center text-xs text-gray-500">{{ __('Loading…') }}</p>
        </div>

        <div class="col-span-full mt-4">
          <label for="about" class="block text-sm/6 font-medium text-white">{{ __('New message') }}</label>
          <div class="mt-2">
            <textarea name="message" rows="3" maxlength="200" class="msg-textarea block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-yellow-500 sm:text-sm/6"></textarea>
          </div>
          <p class="mt-1 flex justify-end text-xs text-gray-500"><span class="char-count">0</span>&nbsp;/ 200</p>
        </div>
    </div>
  </div>

  <div class="mt-2 flex shrink-0 items-center gap-3">
            <button type="submit" command="close" commandfor="msg-dialog-{{ $mamaduck->duck_id }}" class="duck-send-message w-full flex justify-center rounded-md bg-yellow-500 px-3 py-2 text-sm font-semibold text-white hover:bg-yellow-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500">{{ __('Send Message') }}</button>
            <span class="send-status text-xs"></span>
  </div>
</form>
        </el-dialog-panel>
  </div>
    </dialog>
  </el-dialog>
    </div>
  </div>
</div>
@endforeach
    <!-- Emergency Broadcast Modal -->
    <el-dialog>
      <dialog id="broadcast-dialog" aria-labelledby="broadcast-dialog-title" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/75 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>
        <div tabindex="0" class="flex min-h-full items-stretch justify-center p-0 text-center focus:outline focus:outline-0 sm:items-center sm:p-4">
          <el-dialog-panel class="relative flex h-full w-full transform flex-col overflow-hidden rounded-none bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl outline outline-1 -outline-offset-1 outline-white/10 ring-2 ring-inset ring-red-600/60 transition-all data-[closed]:translate-y-4 data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in sm:my-8 sm:h-auto sm:max-w-md sm:rounded-lg sm:p-6 data-[closed]:sm:translate-y-0 data-[closed]:sm:scale-95">
            <div class="flex shrink-0 items-center gap-3 border-b border-white/10 pb-4 mb-4">
              <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-red-600/20 ring-1 ring-red-600/40">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5 text-red-400">
                  <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
              </div>
              <div>
                <h2 id="broadcast-dialog-title" class="text-base font-semibold text-white">{{ __('Emergency Broadcast') }}</h2>
                <p class="text-xs text-gray-400">{{ __('Message will be sent to') }} <span class="font-semibold text-red-400">{{ __('all') }}</span> {{ __('connected devices (topic 24).') }}</p>
              </div>
            </div>
            <form id="broadcast-form" class="flex h-full flex-col">
              @csrf
              <div class="flex-1 space-y-4 overflow-y-auto">
                <div>
                  <label for="broadcast-message" class="block text-sm font-medium text-white">{{ __('Broadcast message') }}</label>
                  <div class="mt-2">
                    <textarea id="broadcast-message" name="message" rows="4" maxlength="200"
                      placeholder="{{ __('Enter your emergency message…') }}"
                      class="bc-textarea block w-full rounded-md bg-white/5 px-3 py-1.5 text-sm text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-red-500"></textarea>
                  </div>
                  <p class="mt-1 flex justify-end text-xs text-gray-500"><span id="bc-char-count">0</span>&nbsp;/ 200</p>
                </div>
              </div>
              <div class="mt-5 flex shrink-0 items-center gap-3">
                <button type="submit" id="broadcast-send-btn"
                  class="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600 disabled:opacity-50 disabled:cursor-not-allowed">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                    <path d="M2.87 2.298a.75.75 0 0 0-.812.495l-2 6.5a.75.75 0 0 0 .926.926L4 9.32V10a.75.75 0 0 0 .28.585l4.5 3.5A.75.75 0 0 0 10 13.5V10.82l2.985-.897a.75.75 0 0 0 .516-.923L11.578 3.3a.75.75 0 0 0-.812-.495L8 3.6 5.234 3.3 2.87 2.298Z" />
                  </svg>
                  {{ __('Send Broadcast') }}
                </button>
                <button type="button" id="broadcast-cancel-btn"
                  class="rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20">
                  {{ __('Cancel') }}
                </button>
                <span id="broadcast-send-status" class="ml-auto text-xs"></span>
              </div>
            </form>
          </el-dialog-panel>
        </div>
      </dialog>
    </el-dialog>

    <!-- Send Message to Duck Modal -->
    <el-dialog>
      <dialog id="send-duck-dialog" aria-labelledby="send-duck-title" class="fixed inset-0 m-0 size-auto max-h-none max-w-none overflow-y-auto bg-transparent p-0 backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/50 transition-opacity data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in"></el-dialog-backdrop>
        <div tabindex="0" class="flex min-h-full items-stretch justify-center p-0 text-center focus:outline focus:outline-0 sm:items-center sm:p-4">
          <el-dialog-panel class="relative flex h-full w-full transform flex-col overflow-hidden rounded-none bg-gray-800 px-4 pb-4 pt-5 text-left shadow-xl outline outline-1 -outline-offset-1 outline-white/10 transition-all data-[closed]:translate-y-4 data-[closed]:opacity-0 data-[enter]:duration-300 data-[leave]:duration-200 data-[enter]:ease-out data-[leave]:ease-in sm:my-8 sm:h-auto sm:max-w-sm sm:rounded-lg sm:p-6 data-[closed]:sm:translate-y-0 data-[closed]:sm:scale-95">
            <form id="send-duck-form" class="duck-message-form flex h-full flex-col" action="/status/send">
              @csrf
              <div class="flex-1 space-y-12 overflow-y-auto">
                <div class="border-b border-white/10 pb-3">
                  <h2 id="send-duck-title" class="text-base/7 font-semibold text-white">{{ __('Messaging') }}</h2>
                  <p class="mt-1 text-sm/6 text-gray-400">{{ __('This messaging is on a best-effort basis') }}</p>
                  <div class="col-span-full mt-4">
                    <label class="block text-sm/6 font-medium text-white">{{ __('Duck ID') }}</label>
                    <div class="mt-2">
                      <input type="text" name="duck_id" maxlength="50" placeholder="e.g. MAMAMUHAMMAD"
                        class="block w-full rounded-md bg-white/5 px-3 py-1.5 text-sm text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-yellow-500">
                    </div>
                  </div>
                  <div class="col-span-full mt-4">
                    <label class="block text-sm/6 font-medium text-white">{{ __('New message') }}</label>
                    <div class="mt-2">
                      <textarea name="message" rows="3" maxlength="200" class="msg-textarea block w-full rounded-md bg-white/5 px-3 py-1.5 text-base text-white outline outline-1 -outline-offset-1 outline-white/10 placeholder:text-gray-500 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-yellow-500 sm:text-sm/6"></textarea>
                    </div>
                    <p class="mt-1 flex justify-end text-xs text-gray-500"><span class="char-count">0</span>&nbsp;/ 200</p>
                  </div>
                </div>
              </div>
              <div class="mt-2 flex shrink-0 items-center gap-3">
                <button type="submit" command="close" commandfor="send-duck-dialog"
                  class="duck-send-message w-full flex justify-center rounded-md bg-yellow-500 px-3 py-2 text-sm font-semibold text-white hover:bg-yellow-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow-500">
                  {{ __('Send Message') }}
                </button>
                <span class="send-status text-xs"></span>
              </div>
            </form>
          </el-dialog-panel>
        </div>
      </dialog>
    </el-dialog>

    <!-- Empty state -->
    <div id="duck-empty-state" class="col-span-full hidden py-16 text-center">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="mx-auto mb-3 size-10 text-gray-600">
        <path fill-rule="evenodd" d="M10.5 3.75a6.75 6.75 0 1 0 0 13.5 6.75 6.75 0 0 0 0-13.5ZM2.25 10.5a8.25 8.25 0 1 1 14.59 5.28l4.69 4.69a.75.75 0 1 1-1.06 1.06l-4.69-4.69A8.25 8.25 0 0 1 2.25 10.5Z" clip-rule="evenodd" />
      </svg>
      <p id="duck-empty-title" class="text-sm font-semibold text-gray-400">{{ __('No ducks found') }}</p>
      <p id="duck-empty-sub" class="mt-1 text-xs text-gray-600">{{ __('Try adjusting the search or urgency filter.') }}</p>
    </div>
</div>
</div>

<script>
  var urgencyLabels = { '0': 'Low', '1': 'Medium', '2': 'Critical' };

  function applyFilters() {
    var q          = document.getElementById('duck-search').value.trim().toLowerCase();
    var u          = document.getElementById('urgency-filter').value;
    var inc        = document.getElementById('incident-filter').value;
    var onlineOnly = document.getElementById('online-only-toggle').checked;
    var visible = 0;
    var totalCards = document.querySelectorAll('#duck-cards-container [data-duck-id]').length;

    document.querySelectorAll('#duck-cards-container [data-duck-id]').forEach(function (card) {
      var id       = card.getAttribute('data-duck-id').toLowerCase();
      var urgency  = card.getAttribute('data-urgency');
      var online   = card.getAttribute('data-online') === '1';
      var incStatus = card.getAttribute('data-incident-status') || '';

      // Online-only gate (AND — always applied when active)
      if (onlineOnly && !online) { card.style.display = 'none'; return; }

      // Incident filter (AND — always applied when active)
      if (inc !== '') {
        var incMatch = inc === 'any' ? incStatus !== '' : incStatus === inc;
        if (!incMatch) { card.style.display = 'none'; return; }
      }

      // Neither search nor urgency active — show all (that passed gates above)
      if (q === '' && u === '') { card.style.display = ''; visible++; return; }

      // Only urgency active
      if (q === '' && u !== '') { var show = urgency === u; card.style.display = show ? '' : 'none'; if (show) visible++; return; }

      // Only search active
      if (q !== '' && u === '') { var show = id.includes(q); card.style.display = show ? '' : 'none'; if (show) visible++; return; }

      // Both active — OR
      var show = id.includes(q) || urgency === u; card.style.display = show ? '' : 'none'; if (show) visible++;
    });

    var empty = document.getElementById('duck-empty-state');
    var title = document.getElementById('duck-empty-title');
    var sub   = document.getElementById('duck-empty-sub');

    var incLabels = { any: 'With Incident', open: 'Open', acknowledged: 'Acknowledged', responding: 'Responding' };

    if (visible === 0) {
      empty.classList.remove('hidden');
      if (totalCards === 0) {
        // No ducks exist at all (not a filter side-effect) — same style of
        // message as the "Online only" empty state, since there's nothing
        // for the user to adjust here.
        title.textContent = 'No ducks detected';
        sub.textContent   = 'No MamaDuck or PapaDuck has checked in yet.';
      } else if (inc !== '') {
        title.textContent = 'No ducks with ' + (incLabels[inc] || inc) + ' incident';
        sub.textContent   = 'There are no active incidents at this status.';
      } else if (onlineOnly && q === '' && u === '') {
        title.textContent = 'No online ducks';
        sub.textContent   = 'All ducks are currently offline.';
      } else if (q !== '' && u === '') {
        title.textContent = 'No ducks matching "' + q + '"';
        sub.textContent   = 'Try a different duck ID.';
      } else if (q === '' && u !== '') {
        title.textContent = 'No ducks with ' + (urgencyLabels[u] || u) + ' urgency';
        sub.textContent   = 'There are currently no active ducks at this urgency level.';
      } else {
        title.textContent = 'No ducks found';
        sub.textContent   = 'Try adjusting the search or urgency filter.';
      }
    } else {
      empty.classList.add('hidden');
    }
  }

  document.getElementById('duck-search').addEventListener('input', applyFilters);
  document.getElementById('urgency-filter').addEventListener('change', applyFilters);
  document.getElementById('incident-filter').addEventListener('change', applyFilters);
  document.getElementById('online-only-toggle').addEventListener('change', applyFilters);
  window.applyFilters = applyFilters;

  // On initial load, only show the empty-state message if there are truly no
  // ducks at all. We deliberately do NOT call applyFilters() here — some
  // browsers restore form field values (search text, the "Online only"
  // checkbox) across a plain page refresh, and running the full filter pass
  // on load would apply that leftover state and hide ducks that should be
  // shown by default. Ducks should always load unfiltered on first view.
  if (document.querySelectorAll('#duck-cards-container [data-duck-id]').length === 0) {
    document.getElementById('duck-empty-title').textContent = 'No ducks detected';
    document.getElementById('duck-empty-sub').textContent   = 'No MamaDuck or PapaDuck has checked in yet.';
    document.getElementById('duck-empty-state').classList.remove('hidden');
  }

  // ── Emergency Broadcast ──────────────────────────────────────────────────
  (function () {
    var dialog      = document.getElementById('broadcast-dialog');
    var openBtn     = document.getElementById('open-broadcast-btn');
    var cancelBtn   = document.getElementById('broadcast-cancel-btn');
    var form        = document.getElementById('broadcast-form');
    var textarea    = document.getElementById('broadcast-message');
    var charCount   = document.getElementById('bc-char-count');
    var sendBtn     = document.getElementById('broadcast-send-btn');
    var statusSpan  = document.getElementById('broadcast-send-status');
    var toast       = document.getElementById('broadcast-toast');
    var toastMsg    = document.getElementById('broadcast-toast-msg');
    var toastIcon   = document.getElementById('broadcast-toast-icon');
    var toastTimer  = null;

    function showToast(message, isError) {
      clearTimeout(toastTimer);
      toastMsg.textContent = message;
      if (isError) {
        toast.className = toast.className.replace(/bg-\S+/g, '').trim();
        toast.classList.add('bg-red-700', 'text-white');
        toastIcon.classList.remove('text-green-300');
        toastIcon.classList.add('text-red-300');
      } else {
        toast.className = toast.className.replace(/bg-\S+/g, '').trim();
        toast.classList.add('bg-green-700', 'text-white');
        toastIcon.classList.remove('text-red-300');
        toastIcon.classList.add('text-green-300');
      }
      toast.classList.remove('opacity-0', 'translate-y-2');
      toast.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
      toastTimer = setTimeout(function () {
        toast.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
        toast.classList.add('opacity-0', 'translate-y-2');
      }, 4000);
    }

    function openDialog() {
      textarea.value = '';
      charCount.textContent = '0';
      statusSpan.textContent = '';
      sendBtn.disabled = false;
      dialog.showModal();
    }

    function closeDialog() {
      dialog.close();
    }

    openBtn.addEventListener('click', openDialog);
    cancelBtn.addEventListener('click', closeDialog);
    dialog.addEventListener('click', function (e) {
      if (e.target === dialog) closeDialog();
    });

    textarea.addEventListener('input', function () {
      charCount.textContent = textarea.value.length;
      charCount.style.color = textarea.value.length >= 200 ? '#f87171' : '';
    });

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var message = textarea.value.trim();
      if (!message) {
        statusSpan.textContent = 'Message cannot be empty.';
        statusSpan.style.color = '#f87171';
        return;
      }
      sendBtn.disabled = true;
      statusSpan.textContent = 'Sending…';
      statusSpan.style.color = '#9ca3af';

      var csrfMeta  = document.querySelector('meta[name="csrf-token"]');
      var csrfToken = csrfMeta
        ? csrfMeta.getAttribute('content')
        : document.querySelector('input[name="_token"]').value;

      fetch('/status/broadcast', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ message: message }),
      })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (result.ok) {
          closeDialog();
          showToast('\u2705 ' + result.data.message, false);
        } else {
          var errMsg = result.data.message || result.data.errors?.message?.[0] || 'Failed to send broadcast.';
          statusSpan.textContent = errMsg;
          statusSpan.style.color = '#f87171';
          sendBtn.disabled = false;
        }
      })
      .catch(function () {
        statusSpan.textContent = 'Network error. Please try again.';
        statusSpan.style.color = '#f87171';
        sendBtn.disabled = false;
      });
    });
  })();

  // Character counter for message textareas
  document.querySelectorAll('.msg-textarea').forEach(function (textarea) {
    var counter = textarea.closest('.col-span-full').querySelector('.char-count');
    textarea.addEventListener('input', function () {
      counter.textContent = textarea.value.length;
      counter.style.color = textarea.value.length >= 200 ? '#f87171' : '';
    });
  });
</script>
@endsection
</x-layouts::app>
