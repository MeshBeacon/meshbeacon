<x-layouts::app :title="__('Operations')">
@section('content')
<div id="operations-page" data-status-url="{{ route('operations.status') }}" class="space-y-6">
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p class="text-sm text-gray-400">{{ __('Runtime health and service activity') }}</p>
      <p id="operations-updated" class="mt-1 text-xs text-gray-500">{{ __('Loading status…') }}</p>
    </div>
    <div id="operations-overall" class="inline-flex w-fit items-center gap-2 rounded-md bg-white/5 px-3 py-2 text-sm font-medium text-gray-300 ring-1 ring-inset ring-white/10">
      <span class="size-2 rounded-full bg-gray-500"></span>
      <span>{{ __('Checking') }}</span>
    </div>
  </div>

  <dl id="operations-checks" class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
    <div class="rounded-lg bg-gray-900/50 p-4 ring-1 ring-inset ring-white/10">
      <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Database') }}</dt>
      <dd data-check="database" class="mt-2 text-sm text-gray-300">{{ __('Checking…') }}</dd>
    </div>
    <div class="rounded-lg bg-gray-900/50 p-4 ring-1 ring-inset ring-white/10">
      <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Migrations') }}</dt>
      <dd data-check="migrations" class="mt-2 text-sm text-gray-300">{{ __('Checking…') }}</dd>
    </div>
    <div class="rounded-lg bg-gray-900/50 p-4 ring-1 ring-inset ring-white/10">
      <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('MQTT broker') }}</dt>
      <dd data-check="mqtt" class="mt-2 text-sm text-gray-300">{{ __('Checking…') }}</dd>
    </div>
    <div class="rounded-lg bg-gray-900/50 p-4 ring-1 ring-inset ring-white/10">
      <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Queue') }}</dt>
      <dd data-check="queue" class="mt-2 text-sm text-gray-300">{{ __('Checking…') }}</dd>
    </div>
    <div class="rounded-lg bg-gray-900/50 p-4 ring-1 ring-inset ring-white/10 sm:col-span-2">
      <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Workers') }}</dt>
      <dd data-check="workers" class="mt-2 text-sm text-gray-300">{{ __('Checking…') }}</dd>
    </div>
  </dl>

  <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
    <section class="rounded-lg bg-gray-900/50 p-4 ring-1 ring-inset ring-white/10">
      <h2 class="text-sm font-semibold text-white">{{ __('Queue activity') }}</h2>
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

    <section class="rounded-lg bg-gray-900/50 p-4 ring-1 ring-inset ring-white/10">
      <h2 class="text-sm font-semibold text-white">{{ __('MQTT activity') }}</h2>
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
  };

  const formatTime = (value) => value ? new Date(value).toLocaleString() : '-';
  const setText = (id, value) => {
    const element = document.getElementById(id);
    if (element) element.textContent = value;
  };

  function renderStatus(payload) {
    const overall = document.getElementById('operations-overall');
    const overallLabel = overall?.querySelector('span:last-child');
    const overallDot = overall?.querySelector('span:first-child');
    const overallOk = payload.status === 'ok';

    if (overallLabel) overallLabel.textContent = overallOk ? '{{ __('Operational') }}' : '{{ __('Degraded') }}';
    if (overallDot) overallDot.className = `size-2 rounded-full ${overallOk ? 'bg-emerald-400' : 'bg-amber-400'}`;
    setText('operations-updated', `{{ __('Updated') }} ${formatTime(payload.generated_at)}`);

    for (const [name, check] of Object.entries(payload.checks)) {
      const element = document.querySelector(`[data-check="${name}"]`);
      if (!element) continue;
      if (name === 'workers') {
        element.textContent = Object.entries(check).map(([worker, state]) => `${worker}: ${labels[state.status] || state.status}`).join(' | ');
      } else {
        element.textContent = labels[check.status] || check.status;
      }
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
      const overallLabel = document.querySelector('#operations-overall span:last-child');
      if (overallLabel) overallLabel.textContent = '{{ __('Unable to check') }}';
    }
  }

  refresh();
  setInterval(refresh, 15000);
})();
</script>
@endsection
</x-layouts::app>
