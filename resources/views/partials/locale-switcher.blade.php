@php
  $locales = ['en' => 'EN', 'ms' => 'BM'];
  $current = app()->getLocale();
@endphp
<div class="inline-flex items-center rounded-md bg-white/5 p-0.5 ring-1 ring-inset ring-white/10">
  @foreach ($locales as $code => $label)
    <form method="POST" action="{{ route('locale.update', $code) }}">
      @csrf
      <button type="submit"
        class="rounded px-2 py-1 text-xs font-semibold transition-colors {{ $current === $code ? 'bg-white/10 text-white' : 'text-gray-400 hover:text-white' }}"
        @if ($current === $code) aria-current="true" @endif>
        {{ $label }}
      </button>
    </form>
  @endforeach
</div>
