@php
  $locales = ['en' => 'EN', 'ms' => 'BM'];
  $current = app()->getLocale();
@endphp
<div class="inline-flex items-center rounded-md bg-gray-100 dark:bg-white/5 p-0.5 ring-1 ring-inset ring-gray-200 dark:ring-white/10">
  @foreach ($locales as $code => $label)
    <form method="POST" action="{{ route('locale.update', $code) }}">
      @csrf
      <button type="submit"
        class="rounded px-2 py-1 text-xs font-semibold transition-colors {{ $current === $code ? 'bg-gray-200 dark:bg-white/10 text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:text-white' }}"
        @if ($current === $code) aria-current="true" @endif>
        {{ $label }}
      </button>
    </form>
  @endforeach
</div>
