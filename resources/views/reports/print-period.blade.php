<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>OpenDMS After-Action Report &mdash; {{ $from->toDateString() }} to {{ $to->toDateString() }}</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #111827; margin: 2rem; }
    h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
    h2 { font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: #4b5563; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid #d1d5db; padding-bottom: 0.25rem; }
    .subtitle { color: #6b7280; margin-bottom: 1.5rem; }
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.75rem; }
    .stat { border: 1px solid #d1d5db; border-radius: 6px; padding: 0.75rem; }
    .stat dt { font-size: 0.75rem; color: #6b7280; }
    .stat dd { font-size: 1.25rem; font-weight: 600; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
    th, td { border: 1px solid #d1d5db; padding: 0.4rem 0.6rem; font-size: 0.8rem; text-align: left; }
    th { background: #f3f4f6; }
    .no-print { margin-bottom: 1.5rem; }
    @media print {
      .no-print { display: none; }
      body { margin: 0.5in; }
    }
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()">Print / Save as PDF</button>
  </div>

  <h1>OpenDMS After-Action Report</h1>
  <p class="subtitle">Operational period: {{ $from->toFormattedDateString() }} &mdash; {{ $to->toFormattedDateString() }}</p>

  <h2>Message &amp; Relay Summary</h2>
  <dl class="stats">
    <div class="stat"><dt>Total Messages</dt><dd>{{ $summary['message_volume']['total'] }}</dd></div>
    <div class="stat"><dt>Relay Reliability</dt><dd>{{ $summary['hop_distribution']['relay_reliability_pct'] }}%</dd></div>
    <div class="stat"><dt>Avg. Hops per Message</dt><dd>{{ $summary['hop_distribution']['avg_hops'] }}</dd></div>
    <div class="stat"><dt>Relayed Messages</dt><dd>{{ $summary['hop_distribution']['relayed_messages'] }}</dd></div>
  </dl>

  <h2>SOS Response-Time Analytics</h2>
  <dl class="stats">
    <div class="stat"><dt>Total Incidents</dt><dd>{{ $summary['sos_analytics']['total_incidents'] }}</dd></div>
    <div class="stat"><dt>Open / Resolved</dt><dd>{{ $summary['sos_analytics']['open_incidents'] }} / {{ $summary['sos_analytics']['resolved_incidents'] }}</dd></div>
    <div class="stat"><dt>Avg. Time to Acknowledge</dt><dd>{{ $summary['sos_analytics']['avg_ack_seconds'] !== null ? gmdate('H:i:s', $summary['sos_analytics']['avg_ack_seconds']) : '&mdash;' }}</dd></div>
    <div class="stat"><dt>Avg. Time to Resolve</dt><dd>{{ $summary['sos_analytics']['avg_resolve_seconds'] !== null ? gmdate('H:i:s', $summary['sos_analytics']['avg_resolve_seconds']) : '&mdash;' }}</dd></div>
  </dl>

  <h2>Message Volume by Device</h2>
  <table>
    <thead>
      <tr><th>Duck ID</th><th>Type</th><th>Messages</th></tr>
    </thead>
    <tbody>
      @forelse ($summary['message_volume']['by_duck'] as $duck)
      <tr>
        <td>{{ $duck['duck_id'] }}</td>
        <td>{{ $duck['label'] }}</td>
        <td>{{ $duck['total'] }}</td>
      </tr>
      @empty
      <tr><td colspan="3">No messages in this period.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Relay Hop Distribution</h2>
  <table>
    <thead>
      <tr><th>Hops</th><th>Messages</th></tr>
    </thead>
    <tbody>
      @forelse ($summary['hop_distribution']['labels'] as $i => $label)
      <tr>
        <td>{{ $label }}</td>
        <td>{{ $summary['hop_distribution']['data'][$i] }}</td>
      </tr>
      @empty
      <tr><td colspan="2">No relay data in this period.</td></tr>
      @endforelse
    </tbody>
  </table>

  <h2>Incidents ({{ $incidents->count() }})</h2>
  <table>
    <thead>
      <tr>
        <th>Duck</th><th>Status</th><th>Assigned</th><th>Created</th><th>Acknowledged</th><th>Resolved</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($incidents as $log)
      <tr>
        <td>{{ $log->duck_id }}</td>
        <td>{{ ucfirst($log->status) }}</td>
        <td>{{ $log->assignedTo?->name ?? '&mdash;' }}</td>
        <td>{{ $log->created_at->toDateTimeString() }}</td>
        <td>{{ $log->acknowledged_at?->toDateTimeString() ?? '&mdash;' }}</td>
        <td>{{ $log->resolved_at?->toDateTimeString() ?? '&mdash;' }}</td>
      </tr>
      @empty
      <tr><td colspan="6">No incidents in this period.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
