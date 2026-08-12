<x-layouts::app :title="__('System Health')">
@section('content')
<div id="operations-page" data-status-url="{{ route('system-health.status') }}" class="space-y-6">
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm text-gray-400">{{ __('Runtime health and service activity') }}</p>
      <p id="operations-updated" class="mt-1 text-xs text-gray-500">{{ __('Loading status…') }}</p>
    </div>
    <div id="operations-overall" class="inline-flex w-fit items-center gap-2 rounded-full bg-white/5 px-3 py-1 text-xs font-medium text-gray-400 ring-1 ring-inset ring-white/10">
      <span class="relative flex size-2">
        <span id="operations-overall-ping" class="absolute inline-flex h-full w-full rounded-full opacity-75"></span>
        <span id="operations-overall-dot" class="relative inline-flex size-2 rounded-full bg-gray-500"></span>
      </span>
      <span id="operations-overall-label">{{ __('Checking') }}</span>
    </div>
  </div>

  <div>
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-white">{{ __('Health checks') }}</h2>
    <dl id="operations-checks" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <div class="overflow-hidden rounded-lg bg-gray-800/75 p-4 shadow ring-1 ring-inset ring-white/10">
        <div class="flex items-center justify-between gap-2">
          <dt class="truncate text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Database') }}</dt>
          <span data-check-dot="database" class="size-2 shrink-0 rounded-full bg-gray-500"></span>
        </div>
        <dd data-check="database" class="mt-2 text-sm font-medium text-white">{{ __('Checking…') }}</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-gray-800/75 p-4 shadow ring-1 ring-inset ring-white/10">
        <div class="flex items-center justify-between gap-2">
          <dt class="truncate text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Migrations') }}</dt>
          <span data-check-dot="migrations" class="size-2 shrink-0 rounded-full bg-gray-500"></span>
        </div>
        <dd data-check="migrations" class="mt-2 text-sm font-medium text-white">{{ __('Checking…') }}</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-gray-800/75 p-4 shadow ring-1 ring-inset ring-white/10">
        <div class="flex items-center justify-between gap-2">
          <dt class="truncate text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('MQTT broker') }}</dt>
          <span data-check-dot="mqtt" class="size-2 shrink-0 rounded-full bg-gray-500"></span>
        </div>
        <dd data-check="mqtt" class="mt-2 text-sm font-medium text-white">{{ __('Checking…') }}</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-gray-800/75 p-4 shadow ring-1 ring-inset ring-white/10">
        <div class="flex items-center justify-between gap-2">
          <dt class="truncate text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Queue') }}</dt>
          <span data-check-dot="queue" class="size-2 shrink-0 rounded-full bg-gray-500"></span>
        </div>
        <dd data-check="queue" class="mt-2 text-sm font-medium text-white">{{ __('Checking…') }}</dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-gray-800/75 p-4 shadow ring-1 ring-inset ring-white/10 sm:col-span-2">
        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Workers') }}</dt>
        <dd id="operations-workers" class="mt-2 space-y-1.5 text-sm font-medium text-white">
          <p class="text-gray-400">{{ __('Checking…') }}</p>
        </dd>
      </div>
    </dl>
  </div>

  <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
    <section class="rounded-lg bg-gray-800/75 p-4 shadow ring-1 ring-inset ring-white/10">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('Queue activity') }}</h2>
      <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
        <div>
          <dt class="text-gray-500">{{ __('Pending jobs') }}</dt>
          <dd id="queue-pending" class="mt-1 font-medium text-gray-200">-</dd>
        </div>
        <div>
          <dt class="text-gray-500">{{ __('Failed jobs') }}</dt>
          <dd id="queue-failed" class="mt-1 font-medium text-gray-200">-</dd>
        </div>
        <div class="col-span-2">
          <dt class="text-gray-500">{{ __('Last failure') }}</dt>
          <dd id="queue-last-failure" class="mt-1 break-words font-medium text-gray-200">-</dd>
        </div>
      </dl>
    </section>

    <section class="rounded-lg bg-gray-800/75 p-4 shadow ring-1 ring-inset ring-white/10">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-white">{{ __('MQTT activity') }}</h2>
      <dl class="mt-4 grid grid-cols-2 gap-4 text-sm">
        <div>
          <dt class="text-gray-500">{{ __('Connection') }}</dt>
          <dd id="mqtt-connection" class="mt-1 font-medium text-gray-200">-</dd>
        </div>
        <div>
          <dt class="text-gray-500">{{ __('Last message') }}</dt>
          <dd id="mqtt-last-message" class="mt-1 font-medium text-gray-200">-</dd>
        </div>
        <div class="col-span-2">
          <dt class="text-gray-500">{{ __('Last heartbeat') }}</dt>
          <dd id="mqtt-last-heartbeat" class="mt-1 font-medium text-gray-200">-</dd>
        </div>
      </dl>
    </section>
  </div>
</div>

<script>
(() => {
  const page = document.getElementById('operations-page');
  if (!page) return;

  const statusUrl = page.dataset.statusUrl;
  const labels = {
    ok: '{{ __('Healthy') }}',
    warning: '{{ __('Attention required') }}',
    failed: '{{ __('Unavailable') }}',
    disabled: '{{ __('Disabled') }}',
    blocked: '{{ __('Blocked') }}',
    unknown: '{{ __('Unknown') }}',
    connected: '{{ __('Connected') }}',
    starting: '{{ __('Starting') }}',
    disconnected: '{{ __('Disconnected') }}',
    error: '{{ __('Error') }}',
  };

  const overallLabels = {
    ok: '{{ __('Operational') }}',
    degraded: '{{ __('Degraded') }}',
    down: '{{ __('System down') }}',
  };
  const overallStyle = {
    ok: { text: 'text-emerald-400', dot: 'bg-emerald-400', ping: 'bg-emerald-400' },
    degraded: { text: 'text-amber-400', dot: 'bg-amber-400', ping: 'bg-amber-400' },
    down: { text: 'text-red-400', dot: 'bg-red-500', ping: 'bg-red-500' },
  };
  const dotColor = {
    ok: 'bg-emerald-400',
    warning: 'bg-amber-400',
    failed: 'bg-red-500',
    disabled: 'bg-gray-500',
    blocked: 'bg-gray-500',
    unknown: 'bg-gray-500',
  };

  const formatTime = (value) => value ? new Date(value).toLocaleString() : '-';
  const setText = (id, value) => {
    const element = document.getElementById(id);
    if (element) element.textContent = value;
  };

  function renderStatus(payload) {
    const overall = document.getElementById('operations-overall');
    const overallLabel = document.getElementById('operations-overall-label');
    const overallDot = document.getElementById('operations-overall-dot');
    const overallPing = document.getElementById('operations-overall-ping');
    const overallState = payload.ready === false ? 'down' : (payload.status === 'degraded' ? 'degraded' : 'ok');
    const style = overallStyle[overallState];

    if (overall) overall.className = `inline-flex w-fit items-center gap-2 rounded-full bg-white/5 px-3 py-1 text-xs font-medium ${style.text} ring-1 ring-inset ring-white/10`;
    if (overallLabel) overallLabel.textContent = overallLabels[overallState];
    if (overallDot) overallDot.className = `relative inline-flex size-2 rounded-full ${style.dot}`;
    if (overallPing) overallPing.className = `absolute inline-flex h-full w-full rounded-full opacity-75 ${overallState === 'ok' ? `animate-ping ${style.ping}` : style.ping}`;
    setText('operations-updated', `{{ __('Updated') }} ${formatTime(payload.generated_at)}`);

    for (const [name, check] of Object.entries(payload.checks)) {
      if (name === 'workers') {
        const workersEl = document.getElementById('operations-workers');
        if (workersEl) {
          workersEl.innerHTML = '';
          for (const [worker, state] of Object.entries(check)) {
            const row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-2';
            row.innerHTML = `
              <span class="text-sm capitalize text-gray-300">${worker}</span>
              <span class="inline-flex items-center gap-1.5 text-sm font-medium text-white">
                <span class="size-2 shrink-0 rounded-full ${dotColor[state.status] || 'bg-gray-500'}"></span>
                ${labels[state.status] || state.status}
              </span>`;
            workersEl.appendChild(row);
          }
        }
        continue;
      }

      const element = document.querySelector(`[data-check="${name}"]`);
      if (element) element.textContent = labels[check.status] || check.status;

      const dot = document.querySelector(`[data-check-dot="${name}"]`);
      if (dot) dot.className = `size-2 shrink-0 rounded-full ${dotColor[check.status] || 'bg-gray-500'}`;
    }

    const queue = payload.checks.queue;
    setText('queue-pending', queue.pending === null ? '-' : String(queue.pending));
    setText('queue-failed', queue.failed === null ? '-' : String(queue.failed));
    setText('queue-last-failure', queue.last_failure
      ? `${queue.last_failure.job} | ${formatTime(queue.last_failure.failed_at)}`
      : '-');

    const mqtt = payload.checks.mqtt;
    setText('mqtt-connection', labels[mqtt.connection_status] || mqtt.connection_status);
    setText('mqtt-last-message', formatTime(mqtt.last_message_at));
    setText('mqtt-last-heartbeat', formatTime(mqtt.last_heartbeat_at));
  }

  async function refresh() {
    try {
      const response = await fetch(statusUrl, { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      renderStatus(await response.json());
    } catch (error) {
      setText('operations-updated', '{{ __('Status unavailable') }}');
      const overall = document.getElementById('operations-overall');
      const overallLabel = document.getElementById('operations-overall-label');
      const overallDot = document.getElementById('operations-overall-dot');
      const overallPing = document.getElementById('operations-overall-ping');
      if (overall) overall.className = 'inline-flex w-fit items-center gap-2 rounded-full bg-white/5 px-3 py-1 text-xs font-medium text-gray-400 ring-1 ring-inset ring-white/10';
      if (overallDot) overallDot.className = 'relative inline-flex size-2 rounded-full bg-gray-500';
      if (overallPing) overallPing.className = 'absolute inline-flex h-full w-full rounded-full opacity-75 bg-gray-500';
      if (overallLabel) overallLabel.textContent = '{{ __('Unable to check') }}';
    }
  }

  refresh();
  setInterval(refresh, 15000);
})();
</script>
@endsection
</x-layouts::app>
