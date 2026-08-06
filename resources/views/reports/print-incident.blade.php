<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ __('OpenDMS Incident Report') }} &mdash; {{ $log->message_id }}</title>
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #111827; margin: 2rem; }
    h1 { font-size: 1.5rem; margin-bottom: 0.25rem; }
    h2 { font-size: 1rem; text-transform: uppercase; letter-spacing: 0.05em; color: #4b5563; margin-top: 2rem; margin-bottom: 0.5rem; border-bottom: 1px solid #d1d5db; padding-bottom: 0.25rem; }
    .subtitle { color: #6b7280; margin-bottom: 1.5rem; }
    dl.details { display: grid; grid-template-columns: 160px 1fr; row-gap: 0.4rem; }
    dl.details dt { font-weight: 600; color: #4b5563; }
    dl.details dd { margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
    th, td { border: 1px solid #d1d5db; padding: 0.4rem 0.6rem; font-size: 0.8rem; text-align: left; word-break: break-word; }
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
    <button onclick="window.print()">{{ __('Print / Save as PDF') }}</button>
  </div>

  <h1>{{ __('OpenDMS Incident Report') }}</h1>
  <p class="subtitle">{{ __('Message ID:') }} {{ $log->message_id }}</p>

  <h2>{{ __('Incident Details') }}</h2>
  <dl class="details">
    <dt>{{ __('Duck ID') }}</dt><dd>{{ $log->duck_id }}</dd>
    <dt>{{ __('Status') }}</dt><dd>{{ ucfirst($log->status) }}</dd>
    <dt>{{ __('Assigned To') }}</dt><dd>{{ $log->assignedTo?->name ?? '&mdash;' }}</dd>
    <dt>{{ __('Notes') }}</dt><dd>{{ $log->notes ?? '&mdash;' }}</dd>
    <dt>{{ __('Created At') }}</dt><dd>{{ $log->created_at->toDateTimeString() }}</dd>
    <dt>{{ __('Acknowledged At') }}</dt><dd>{{ $log->acknowledged_at?->toDateTimeString() ?? '&mdash;' }}</dd>
    <dt>{{ __('Resolved At') }}</dt><dd>{{ $log->resolved_at?->toDateTimeString() ?? '&mdash;' }}</dd>
    <dt>{{ __('Time to Acknowledge') }}</dt>
    <dd>{{ $log->acknowledged_at ? gmdate('H:i:s', $log->created_at->diffInSeconds($log->acknowledged_at)) : '&mdash;' }}</dd>
    <dt>{{ __('Time to Resolve') }}</dt>
    <dd>{{ ($log->resolved_at && $log->acknowledged_at) ? gmdate('H:i:s', $log->acknowledged_at->diffInSeconds($log->resolved_at)) : '&mdash;' }}</dd>
  </dl>

  <h2>{{ __('Message & Relay Timeline') }}</h2>
  <table>
    <thead>
      <tr><th>{{ __('Topic') }}</th><th>{{ __('Duck') }}</th><th>{{ __('Hops') }}</th><th>{{ __('Path') }}</th><th>{{ __('Payload') }}</th><th>{{ __('Time') }}</th></tr>
    </thead>
    <tbody>
      @forelse ($timeline as $row)
      <tr>
        <td>{{ $row->topic }}</td>
        <td>{{ $row->duck_id }}</td>
        <td>{{ $row->hops }}</td>
        <td>{{ $row->path ?? '&mdash;' }}</td>
        <td>{{ $row->payload ?? '&mdash;' }}</td>
        <td>{{ $row->created_at->toDateTimeString() }}</td>
      </tr>
      @empty
      <tr><td colspan="6">{{ __('No timeline data found for this message.') }}</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
