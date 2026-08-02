<x-layouts::app :title="__('Dashboard')">
@section('content')
<div class="flex">
<div class="max-w w-full bg-transparent border border-white/10 rounded-base shadow-xs p-4 md:p-6">
  <div class="flex justify-between items-start">
    <div>
      <h5 id="stat-messages-today" class="text-2xl font-semibold text-heading text-white">{{ Number::forHumans($count) }}</h5>
      <p class="text-body text-gray-400">Messages today</p>
    </div>
    <div id="chart-trend" class="flex items-center px-2.5 py-0.5 font-medium text-gray-400 text-center">
      <svg class="w-5 h-5 animate-pulse" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v13m0-13 4 4m-4-4-4 4"/></svg>
      <span class="ml-1">…</span>
    </div>
  </div>
  <div id="area-chart"></div>
  <div class="grid grid-cols-1 items-center border-transparent border-t justify-between">
    <div class="flex justify-between items-center pt-4 md:pt-6">
      <!-- Button -->
      <!--
      <button id="dropdownDefaultButton" data-dropdown-toggle="lastDaysdropdown" data-dropdown-placement="bottom" class="text-sm font-medium text-body text-gray-400 hover:text-heading text-center inline-flex items-center" type="button">
          Last 7 days
          <svg class="w-4 h-4 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
      </button>
      -->
      <!-- Dropdown menu -->
      <div id="lastDaysdropdown" class="z-10 hidden bg-neutral-primary-medium border border-default-medium rounded-base shadow-lg w-44">
          <ul class="p-2 text-sm text-body font-medium" aria-labelledby="dropdownDefaultButton">
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Yesterday</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Today</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Last 7 days</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Last 30 days</a>
            </li>
            <li>
              <a href="#" class="inline-flex items-center w-full p-2 hover:bg-neutral-tertiary-medium hover:text-heading rounded">Last 90 days</a>
            </li>
          </ul>
      </div>
      <!--
      <a href="#" class="inline-flex items-center text-fg-brand bg-transparent box-border border border-transparent hover:bg-neutral-secondary-medium focus:ring-4 focus:ring-neutral-tertiary font-medium leading-5 rounded-base text-sm px-3 py-2 focus:outline-none">
        Users Report
        <svg class="w-4 h-4 ms-1.5 -me-0.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/></svg>
      </a>
      -->
    </div>
  </div>
</div>

<div class="flow-root w-140 bg-transparent border border-white/10 m-4 rounded-base shadow-xs p-6 overflow-hidden">
  <ul role="list" class="-mb-8">
  </ul>
</div>
</div>





        <div>
          <!-- <h3 class="text-base font-semibold text-white mt-6">Last 24 hours</h3> -->
          <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-white/10 sm:p-6">
              <dt class="truncate text-sm font-medium text-gray-400">PapaDucks</dt>
              <dd id="stat-papaducks" class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $papaducks }}</dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-white/10 sm:p-6">
              <dt class="truncate text-sm font-medium text-gray-400">MamaDucks</dt>
              <dd id="stat-mamaducks" class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $mamaducks }}</dd>
            </div>
            <div class="overflow-hidden rounded-lg bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-white/10 sm:p-6">
              <dt class="truncate text-sm font-medium text-gray-400">Total Messages</dt>
              <dd id="stat-total" class="mt-1 text-3xl font-semibold tracking-tight text-white">{{ $count }}</dd>
            </div>
          </dl>
        </div>

        {{-- Active Incidents panel --}}
        <div class="mt-6 mb-2">
          <div class="flex items-center gap-3 mb-3">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-white">Active Incidents</h2>
            <span id="incidents-count" class="hidden inline-flex items-center rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-500/30"></span>
          </div>
          <div id="incidents-list">
            <p class="text-xs text-gray-500 italic">Loading…</p>
          </div>
        </div>

 <div class="flex justify-end items-center gap-2">
<!-- Include this script tag or install `@tailwindplus/elements` via npm: -->
<!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script> -->
<el-select id="table-select" name="selected" value="20" class="block">
  <button type="button" class="grid w-full cursor-default grid-cols-1 rounded-md bg-white/5 py-1.5 pl-3 pr-2 text-left text-white outline outline-1 -outline-offset-1 outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500 sm:text-sm/6">
    <el-selectedcontent class="col-start-1 row-start-1 truncate pr-6">10 rows</el-selectedcontent>
    <svg viewBox="0 0 16 16" fill="currentColor" data-slot="icon" aria-hidden="true" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-400 sm:size-4">
      <path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
    </svg>
  </button>

  <el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">
    </el-option>
    <el-option value="10" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">20 rows</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="50" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">50 rows</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="100" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">100 rows</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="200" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">200 rows</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="500" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">500 rows</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="1000" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">1000 rows</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
  </el-options>
</el-select>

    <div class="relative mt-3 mb-3">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-gray-400">
          <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
        </svg>
      </div>
      <input id="custom-filter" type="text" name="search" class="rounded-md min-w-0 grow bg-white/5 pl-9 pr-3 py-1.5 text-base text-white placeholder:text-gray-500 outline outline-1 -outline-offset-1 outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-yellow-500 sm:text-sm/6" />
    </div>
  </div>


  <div class="-mx-4 mt-1 ring-1 ring-white/15 sm:mx-0 sm:rounded-lg">
    <table id="ducks-table" class="relative min-w-full divide-y divide-white/15" data-kt-datatable-table="true">
      <thead>
        <tr>
          <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-6">DuckID</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Timestamp</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Topic</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">MessageID</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Path</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Message</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Hops</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Type</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Urgency</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-white lg:table-cell">Map</th>
        </tr>
      </thead>
      <tbody>
       @foreach ($clusters as $cluster) 
        <tr>
          <td class="relative border-t border-transparent py-4 pl-4 pr-3 text-sm sm:pl-6">
            <div class="font-medium text-white">{{ $cluster->duck_id }}</div>
            <div class="mt-1 flex flex-col text-gray-400 sm:block lg:hidden">
              <span>16 GB RAM / 8 CPUs</span>
              <span class="hidden sm:inline">·</span>
              <span>512 GB SSD disk</span>
            </div>
            <div class="absolute -top-px left-6 right-0 h-px bg-white/10"></div>
          </td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->created_at }}</td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->topic }}</td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->message_id }}</td>
          <td class="border-t border-white/10 px-3 py-3.5 text-sm text-gray-400">
            @if($cluster->path)
              <div class="flex flex-wrap items-center gap-1">
                @foreach(explode(',', $cluster->path) as $index => $hop)
                  @if($index > 0)
                    <span class="text-gray-500">&#8594;</span>
                  @endif
                  <span class="rounded bg-white/10 px-1.5 py-0.5 font-mono text-xs text-gray-200">{{ trim($hop) }}</span>
                @endforeach
              </div>
            @else
              <span class="text-gray-600 italic">—</span>
            @endif
          </td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell"><div class="break-words whitespace-normal">{{ $cluster->payload }}</div></td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->hops }}</td>
          <td class="hidden border-t border-white/10 px-3 py-3.5 text-sm text-gray-400 lg:table-cell">{{ $cluster->duck_type }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
</x-layouts::app>
