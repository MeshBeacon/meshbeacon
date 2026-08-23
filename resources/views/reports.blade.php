<x-layouts::app :title="__('Reports')">
@section('page-actions')
  <div class="flex flex-wrap items-center gap-2">
    <input id="report-from" type="date" value="{{ $from }}"
      class="rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-sm text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-orange-500">
    <span class="text-sm text-gray-500 dark:text-gray-400">to</span>
    <input id="report-to" type="date" value="{{ $to }}"
      class="rounded-md bg-gray-100 dark:bg-white/5 px-3 py-1.5 text-sm text-gray-900 dark:text-white outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-orange-500">
    <button id="report-apply-btn" type="button"
      class="rounded-md bg-gray-200 dark:bg-white/10 px-3 py-1.5 text-sm font-semibold text-gray-900 dark:text-white ring-1 ring-inset ring-gray-200 dark:ring-white/10 hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
      {{ __('Apply') }}
    </button>
    <a id="report-export-csv" href="{{ route('reports.export', ['from' => $from, 'to' => $to]) }}"
      class="inline-flex items-center gap-1.5 rounded-md bg-gray-200 dark:bg-white/10 px-3 py-1.5 text-sm font-semibold text-gray-900 dark:text-white ring-1 ring-inset ring-gray-200 dark:ring-white/10 hover:bg-white/20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
        <path fill-rule="evenodd" d="M8 1a.75.75 0 0 1 .75.75v6.19l2.72-2.72a.75.75 0 1 1 1.06 1.06l-4 4a.75.75 0 0 1-1.06 0l-4-4a.75.75 0 0 1 1.06-1.06l2.72 2.72V1.75A.75.75 0 0 1 8 1ZM3.5 12.75a.75.75 0 0 1 .75-.75h7.5a.75.75 0 0 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
      </svg>
      {{ __('CSV') }}
    </a>
    <a id="report-export-print" href="{{ route('reports.print', ['from' => $from, 'to' => $to]) }}" target="_blank"
      class="inline-flex items-center gap-1.5 rounded-md bg-orange-500 px-3 py-1.5 text-sm font-semibold text-gray-900 hover:bg-orange-400 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
        <path d="M5 2.5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2.5H5V2.5ZM4 6a2 2 0 0 0-2 2v3a1 1 0 0 0 1 1h1v1.5a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V12h1a1 1 0 0 0 1-1V8a2 2 0 0 0-2-2H4Zm2 6v-1h4v1H6Z" />
      </svg>
      {{ __('Print / PDF') }}
    </a>
  </div>
@endsection
@section('content')
<div class="flex flex-col gap-6">

  {{-- Summary stat cards --}}
  <div>
    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-3">{{ __('Message & Relay Summary') }}</h2>
    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" id="report-message-stats">
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Messages') }}</dt>
        <dd id="stat-total-messages" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Relay Reliability') }}</dt>
        <dd id="stat-relay-reliability" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. Hops per Message') }}</dt>
        <dd id="stat-avg-hops" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Relayed Messages') }}</dt>
        <dd id="stat-relayed-messages" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
    </dl>
  </div>

  <div>
    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white mb-3">{{ __('SOS Response-Time Analytics') }}</h2>
    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" id="report-sos-stats">
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Total Incidents') }}</dt>
        <dd id="stat-total-incidents" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Open / Resolved') }}</dt>
        <dd id="stat-open-resolved" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. Time to Acknowledge') }}</dt>
        <dd id="stat-avg-ack" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('Avg. Time to Resolve') }}</dt>
        <dd id="stat-avg-resolve" class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">&mdash;</dd>
      </div>
    </dl>
  </div>

  {{-- Charts --}}
  <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Message Volume by Device') }}</h3>
      <div id="chart-message-volume" class="h-64"></div>
    </div>
    <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
      <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Relay Hop Distribution') }}</h3>
      <div id="chart-hop-distribution" class="h-64"></div>
    </div>
  </div>

  {{-- Message volume by device type --}}
  <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800/75 px-4 py-5 shadow ring-1 ring-inset ring-gray-200 dark:ring-white/10 sm:p-6">
    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-3">{{ __('Message Volume by Device Type') }}</h3>
    <dl id="report-device-type-stats" class="grid grid-cols-1 gap-4 sm:grid-cols-3 text-sm text-gray-600 dark:text-gray-300">
      <div class="flex items-center justify-between rounded-md bg-gray-100 dark:bg-white/5 px-4 py-3">
        <dt>{{ __('No data') }}</dt>
      </div>
    </dl>
  </div>

  {{-- Incidents table --}}
  <div>
    <div class="flex items-center justify-between mb-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-900 dark:text-white">{{ __('Incidents in Period') }}</h2>
      <span id="report-incidents-count" class="text-sm text-gray-500 dark:text-gray-400"></span>
    </div>
    <div class="relative mb-3 max-w-sm">
      <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4 text-gray-500 dark:text-gray-400">
          <path fill-rule="evenodd" d="M9.965 11.026a5 5 0 1 1 1.06-1.06l2.755 2.754a.75.75 0 1 1-1.06 1.06l-2.755-2.754ZM10.5 7a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0Z" clip-rule="evenodd" />
        </svg>
      </div>
      <input id="report-incidents-search" type="text" placeholder="{{ __('Search duck, notes, assignee…') }}"
        class="w-full rounded-md min-w-0 bg-gray-100 dark:bg-white/5 pl-9 pr-3 py-1.5 text-sm text-gray-900 dark:text-white placeholder:text-gray-500 outline outline-1 -outline-offset-1 outline-gray-200 dark:outline-white/10 focus:outline-2 focus:-outline-offset-2 focus:outline-orange-500">
    </div>
    <div class="overflow-x-auto rounded-lg ring-1 ring-gray-200 dark:ring-white/10">
      <table class="min-w-full divide-y divide-white/10">
        <thead class="bg-white dark:bg-gray-800/75">
          <tr>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Duck') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Assigned') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Notes') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Created') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Ack Time') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Resolve Time') }}</th>
            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Export') }}</th>
          </tr>
        </thead>
        <tbody id="report-incidents-body" class="divide-y divide-white/5 bg-white dark:bg-gray-800/40">
          <tr><td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500">{{ __('Loading…') }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script type="module">
  const fromInput    = document.getElementById('report-from');
  const toInput      = document.getElementById('report-to');
  const applyBtn     = document.getElementById('report-apply-btn');
  const searchInput  = document.getElementById('report-incidents-search');
  const exportCsvEl  = document.getElementById('report-export-csv');
  const exportPdfEl  = document.getElementById('report-export-print');

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function formatDuration(seconds) {
    if (seconds === null || seconds === undefined) return '&mdash;';
    if (seconds < 60) return seconds + 's';
    if (seconds < 3600) return Math.round(seconds / 60) + 'm';
    return (seconds / 3600).toFixed(1) + 'h';
  }

  let volumeChart = null;
  let hopChart = null;

  function renderCharts(summary) {
    const brandColor = getComputedStyle(document.documentElement).getPropertyValue('--color-fg-brand').trim() || '#1447E6';

    const topDucks   = summary.message_volume.by_duck.slice(0, 10);
    const volumeOpts = {
      chart: { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
      series: [{ name: 'Messages', data: topDucks.map(d => d.total) }],
      xaxis: {
        categories: topDucks.map(d => d.duck_id),
        labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: { labels: { style: { colors: '#9ca3af' } } },
      colors: [brandColor],
      grid: { borderColor: 'rgba(255,255,255,0.05)' },
      dataLabels: { enabled: false },
      noData: { text: 'No messages in this period', style: { color: '#9ca3af' } },
    };

    const hopOpts = {
      chart: { type: 'bar', height: '100%', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
      series: [{ name: 'Messages', data: summary.hop_distribution.data }],
      xaxis: {
        categories: summary.hop_distribution.labels,
        labels: { style: { colors: '#9ca3af', fontSize: '11px' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: { labels: { style: { colors: '#9ca3af' } } },
      colors: ['#f59e0b'],
      grid: { borderColor: 'rgba(255,255,255,0.05)' },
      dataLabels: { enabled: false },
      noData: { text: 'No relay data in this period', style: { color: '#9ca3af' } },
    };

    if (typeof ApexCharts === 'undefined') return;

    if (!volumeChart) {
      volumeChart = new ApexCharts(document.getElementById('chart-message-volume'), volumeOpts);
      volumeChart.render();
    } else {
      volumeChart.updateOptions(volumeOpts);
    }

    if (!hopChart) {
      hopChart = new ApexCharts(document.getElementById('chart-hop-distribution'), hopOpts);
      hopChart.render();
    } else {
      hopChart.updateOptions(hopOpts);
    }
  }

  function renderDeviceTypeStats(byDuckType) {
    const container = document.getElementById('report-device-type-stats');
    const entries = Object.entries(byDuckType || {});
    if (!entries.length) {
      container.innerHTML = '<div class="flex items-center justify-between rounded-md bg-gray-100 dark:bg-white/5 px-4 py-3"><dt>No data</dt></div>';
      return;
    }
    container.innerHTML = entries.map(([label, count]) => `
      <div class="flex items-center justify-between rounded-md bg-gray-100 dark:bg-white/5 px-4 py-3">
        <dt>${label}</dt>
        <dd class="font-semibold text-gray-900 dark:text-white">${count}</dd>
      </div>
    `).join('');
  }

  async function loadReportData(from, to) {
    try {
      const res = await fetch(`/reports/data?from=${from}&to=${to}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const summary = await res.json();

      document.getElementById('stat-total-messages').textContent    = summary.message_volume.total;
      document.getElementById('stat-relay-reliability').textContent = summary.hop_distribution.relay_reliability_pct + '%';
      document.getElementById('stat-avg-hops').textContent           = summary.hop_distribution.avg_hops;
      document.getElementById('stat-relayed-messages').textContent   = summary.hop_distribution.relayed_messages;

      document.getElementById('stat-total-incidents').textContent = summary.sos_analytics.total_incidents;
      document.getElementById('stat-open-resolved').textContent   = summary.sos_analytics.open_incidents + ' / ' + summary.sos_analytics.resolved_incidents;
      document.getElementById('stat-avg-ack').innerHTML            = formatDuration(summary.sos_analytics.avg_ack_seconds);
      document.getElementById('stat-avg-resolve').innerHTML        = formatDuration(summary.sos_analytics.avg_resolve_seconds);

      renderCharts(summary);
      renderDeviceTypeStats(summary.message_volume.by_duck_type);
    } catch (e) {
      console.error('Failed to load report data:', e);
    }
  }

  async function loadIncidents(from, to, q) {
    const body = document.getElementById('report-incidents-body');
    try {
      const params = new URLSearchParams({ from, to });
      if (q) params.set('q', q);
      const res = await fetch(`/reports/incidents?${params.toString()}`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();

      document.getElementById('report-incidents-count').textContent = json.total + ' incident(s)';

      if (!json.data.length) {
        body.innerHTML = `<tr><td colspan="8" class="px-3 py-4 text-center text-sm text-gray-500">${q ? 'No incidents match your search.' : 'No incidents in this period.'}</td></tr>`;
        return;
      }

      body.innerHTML = json.data.map(inc => `
        <tr>
          <td class="px-3 py-2 text-sm text-gray-900 dark:text-white">${inc.duck_id}</td>
          <td class="px-3 py-2 text-sm capitalize text-gray-600 dark:text-gray-300">${inc.status}</td>
          <td class="px-3 py-2 text-sm text-gray-600 dark:text-gray-300">${inc.assigned_to_name ?? '&mdash;'}</td>
          <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate" title="${escapeHtml(inc.notes)}">${inc.notes ? escapeHtml(inc.notes) : '&mdash;'}</td>
          <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">${inc.created_at}</td>
          <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">${formatDuration(inc.ack_seconds)}</td>
          <td class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">${formatDuration(inc.resolve_seconds)}</td>
          <td class="px-3 py-2 text-right text-sm">
            <a href="/reports/incidents/${encodeURIComponent(inc.message_id)}/export" class="text-orange-600 dark:text-orange-400 hover:text-orange-500 dark:text-orange-300">CSV</a>
            <span class="text-gray-600">&middot;</span>
            <a href="/reports/incidents/${encodeURIComponent(inc.message_id)}/print" target="_blank" class="text-orange-600 dark:text-orange-400 hover:text-orange-500 dark:text-orange-300">Print</a>
          </td>
        </tr>
      `).join('');
    } catch (e) {
      console.error('Failed to load incidents:', e);
      body.innerHTML = '<tr><td colspan="8" class="px-3 py-4 text-center text-sm text-red-600 dark:text-red-400">Failed to load incidents.</td></tr>';
    }
  }

  function updateExportLinks(from, to) {
    exportCsvEl.href = `/reports/export?from=${from}&to=${to}`;
    exportPdfEl.href = `/reports/print?from=${from}&to=${to}`;
  }

  function refresh() {
    const from = fromInput.value;
    const to   = toInput.value;
    updateExportLinks(from, to);
    loadReportData(from, to);
    loadIncidents(from, to, searchInput.value.trim());
  }

  let searchDebounce = null;
  searchInput.addEventListener('input', () => {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
      loadIncidents(fromInput.value, toInput.value, searchInput.value.trim());
    }, 300);
  });

  applyBtn.addEventListener('click', refresh);
  refresh();
</script>
@endsection
</x-layouts::app>
