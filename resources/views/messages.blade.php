<x-layouts::app :title="__('Messages')">
@section('content')
<div>
 <div class="flex justify-end items-center gap-2">
<!-- Include this script tag or install `@tailwindplus/elements` via npm: -->
<!-- <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script> -->
<el-select id="table-select" name="selected" value="20" class="block">
  <button type="button" class="grid w-full cursor-default grid-cols-1 rounded-md bg-gray-100 dark:bg-white/5 py-1.5 pl-3 pr-2 text-left text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-yellow-500 sm:text-sm/6">
    <el-selectedcontent class="col-start-1 row-start-1 truncate pr-6">{{ __('10 rows') }}</el-selectedcontent>
    <svg viewBox="0 0 16 16" fill="currentColor" data-slot="icon" aria-hidden="true" class="col-start-1 row-start-1 size-5 self-center justify-self-end text-gray-500 dark:text-gray-400 sm:size-4">
      <path d="M5.22 10.22a.75.75 0 0 1 1.06 0L8 11.94l1.72-1.72a.75.75 0 1 1 1.06 1.06l-2.25 2.25a.75.75 0 0 1-1.06 0l-2.25-2.25a.75.75 0 0 1 0-1.06ZM10.78 5.78a.75.75 0 0 1-1.06 0L8 4.06 6.28 5.78a.75.75 0 0 1-1.06-1.06l2.25-2.25a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
    </svg>
  </button>

  <el-options anchor="bottom start" popover class="m-0 max-h-60 w-[var(--button-width)] overflow-auto rounded-md bg-white dark:bg-gray-800 p-0 py-1 text-base outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 [--anchor-gap:theme(spacing.1)] data-[closed]:data-[leave]:opacity-0 data-[leave]:transition data-[leave]:duration-100 data-[leave]:ease-in data-[leave]:[transition-behavior:allow-discrete] sm:text-sm">
    </el-option>
    <el-option value="10" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('20 rows') }}</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="50" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('50 rows') }}</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="100" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('100 rows') }}</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="200" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('200 rows') }}</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="500" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('500 rows') }}</span>
      <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-yellow-400 group-focus/option:text-gray-900 group-[:not([aria-selected='true'])]/option:hidden [el-selectedcontent_&]:hidden">
        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
          <path d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
      </span>
    </el-option>
    <el-option value="1000" class="group/option relative cursor-default select-none py-2 pl-3 pr-9 text-gray-900 dark:text-white focus:bg-yellow-500 focus:text-gray-900 focus:outline-none [&:not([hidden])]:block">
      <span class="block truncate font-normal group-aria-selected/option:font-semibold">{{ __('1000 rows') }}</span>
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
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-gray-500 dark:text-gray-400">
          <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
        </svg>
      </div>
      <input id="custom-filter" type="text" name="search" class="rounded-md min-w-0 grow bg-gray-100 dark:bg-white/5 pl-9 pr-3 py-1.5 text-base text-gray-900 dark:text-white placeholder:text-gray-500 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-yellow-500 sm:text-sm/6" />
    </div>
  </div>


  <div class="-mx-4 mt-1 ring-1 ring-gray-200 dark:ring-white/15 sm:mx-0 sm:rounded-lg">
    <table id="ducks-table" class="relative min-w-full divide-y divide-gray-200 dark:divide-white/15" data-kt-datatable-table="true">
      <thead>
        <tr>
          <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 dark:text-white sm:pl-6">{{ __('DuckID') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Timestamp') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Topic') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('MessageID') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Path') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Message') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Hops') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Type') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Urgency') }}</th>
          <th scope="col" class="hidden px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white lg:table-cell">{{ __('Map') }}</th>
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
</div>
@endsection
</x-layouts::app>
