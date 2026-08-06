<?php

namespace App\Http\Controllers;

use App\Models\IncidentLog;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {}

    public function index(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        return view('reports', [
            'from' => $from->toDateString(),
            'to'   => $to->toDateString(),
        ]);
    }

    public function data(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        return response()->json($this->reportService->getSummary($from, $to));
    }

    public function incidentsList(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        $search      = $request->query('q');

        $incidents = $this->reportService->getIncidentsInRange($from, $to, $search)->map(fn ($log) => [
            'message_id'       => $log->message_id,
            'duck_id'          => $log->duck_id,
            'status'           => $log->status,
            'assigned_to_name' => $log->assignedTo?->name,
            'notes'            => $log->notes,
            'created_at'       => $log->created_at->toDateTimeString(),
            'acknowledged_at'  => $log->acknowledged_at?->toDateTimeString(),
            'resolved_at'      => $log->resolved_at?->toDateTimeString(),
            'ack_seconds'      => $log->acknowledged_at ? $log->created_at->diffInSeconds($log->acknowledged_at) : null,
            'resolve_seconds'  => ($log->resolved_at && $log->acknowledged_at) ? $log->acknowledged_at->diffInSeconds($log->resolved_at) : null,
        ])->values();

        return response()->json(['data' => $incidents, 'total' => $incidents->count()]);
    }

    /**
     * Streamed CSV export of every incident within the selected operational
     * period — the "per operational period" after-action report.
     */
    public function exportPeriodCsv(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);
        $incidents   = $this->reportService->getIncidentsInRange($from, $to, $request->query('q'));
        $filename    = 'opendms-report_' . $from->toDateString() . '_to_' . $to->toDateString() . '.csv';

        return response()->streamDownload(function () use ($incidents) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Message ID', 'Duck ID', 'Status', 'Assigned To', 'Notes', 'Created At', 'Acknowledged At', 'Resolved At', 'Ack Time (s)', 'Resolve Time (s)']);
            foreach ($incidents as $log) {
                fputcsv($out, [
                    $log->message_id,
                    $log->duck_id,
                    $log->status,
                    $log->assignedTo?->name,
                    $log->notes,
                    $log->created_at->toDateTimeString(),
                    $log->acknowledged_at?->toDateTimeString(),
                    $log->resolved_at?->toDateTimeString(),
                    $log->acknowledged_at ? $log->created_at->diffInSeconds($log->acknowledged_at) : '',
                    ($log->resolved_at && $log->acknowledged_at) ? $log->acknowledged_at->diffInSeconds($log->resolved_at) : '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Printable (browser "Save as PDF") after-action report for an
     * operational period, including summary analytics + incident list.
     */
    public function exportPeriodPrint(Request $request)
    {
        [$from, $to] = $this->resolveRange($request);

        return view('reports.print-period', [
            'from'      => $from,
            'to'        => $to,
            'summary'   => $this->reportService->getSummary($from, $to),
            'incidents' => $this->reportService->getIncidentsInRange($from, $to, $request->query('q')),
        ]);
    }

    /**
     * Streamed CSV export for a single incident: its IncidentLog details
     * plus the full relay/message timeline for its message_id.
     */
    public function exportIncidentCsv(string $messageId)
    {
        $log      = IncidentLog::with('assignedTo:id,name')->where('message_id', $messageId)->firstOrFail();
        $timeline = $this->reportService->getIncidentTimeline($messageId);
        $filename = 'opendms-incident_' . $messageId . '.csv';

        return response()->streamDownload(function () use ($log, $timeline) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Incident Report']);
            fputcsv($out, ['Message ID', $log->message_id]);
            fputcsv($out, ['Duck ID', $log->duck_id]);
            fputcsv($out, ['Status', $log->status]);
            fputcsv($out, ['Assigned To', $log->assignedTo?->name]);
            fputcsv($out, ['Notes', $log->notes]);
            fputcsv($out, ['Created At', $log->created_at->toDateTimeString()]);
            fputcsv($out, ['Acknowledged At', $log->acknowledged_at?->toDateTimeString()]);
            fputcsv($out, ['Resolved At', $log->resolved_at?->toDateTimeString()]);
            fputcsv($out, []);
            fputcsv($out, ['Message Timeline']);
            fputcsv($out, ['Topic', 'Duck ID', 'Hops', 'Path', 'Payload', 'Created At']);
            foreach ($timeline as $row) {
                fputcsv($out, [$row->topic, $row->duck_id, $row->hops, $row->path, $row->payload, $row->created_at->toDateTimeString()]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Printable (browser "Save as PDF") after-action report for a single incident.
     */
    public function exportIncidentPrint(string $messageId)
    {
        $log      = IncidentLog::with('assignedTo:id,name')->where('message_id', $messageId)->firstOrFail();
        $timeline = $this->reportService->getIncidentTimeline($messageId);

        return view('reports.print-incident', ['log' => $log, 'timeline' => $timeline]);
    }

    /**
     * Resolves the [from, to] Carbon range from query params, defaulting
     * to the last 7 days (inclusive of today).
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveRange(Request $request): array
    {
        $to   = $request->filled('to') ? Carbon::parse($request->query('to'))->endOfDay() : now();
        $from = $request->filled('from') ? Carbon::parse($request->query('from'))->startOfDay() : $to->copy()->subDays(6)->startOfDay();

        return [$from, $to];
    }
}
