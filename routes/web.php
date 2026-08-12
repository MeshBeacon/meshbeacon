<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MessageLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\PreventDashboardWritesWhenReadonly;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect()->route('login');
})->name('home');

// Unauthenticated: called by Telegram's servers, protected by a secret path
// segment + the X-Telegram-Bot-Api-Secret-Token header (see TelegramWebhookController).
Route::post('/telegram/webhook/{secret}', [TelegramWebhookController::class, 'handle']);

// Available to guests too, so the login page can be switched before authenticating.
Route::post('/locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

// Machine-facing health routes do not need a session. Keeping them outside
// session startup lets liveness remain useful while the database is down.
$healthMiddleware = [
    'web',
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\SetLocale::class,
];

Route::get('/health/live', [HealthController::class, 'live'])
    ->withoutMiddleware($healthMiddleware)
    ->name('health.live');

Route::get('/health/ready', [HealthController::class, 'ready'])
    ->withoutMiddleware($healthMiddleware)
    ->name('health.ready');

Route::get('/metrics', [HealthController::class, 'metrics'])
    ->withoutMiddleware($healthMiddleware)
    ->name('metrics');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/kiosk', [DashboardController::class, 'kiosk']);
    Route::get('/dashboard/timeline', [DashboardController::class, 'timeline']);
    Route::get('/dashboard/hourly', [DashboardController::class, 'hourly']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/map-pins', [DashboardController::class, 'mapPins']);
    Route::get('/dashboard/duck-health', [DashboardController::class, 'duckHealth']);
    Route::get('/dashboard/topology', [DashboardController::class, 'topology']);
    Route::get('/dashboard/incidents/stats', [DashboardController::class, 'incidentStats']);
    Route::get('/dashboard/incidents/responders', [DashboardController::class, 'responders']);
    Route::get('/dashboard/incidents', [DashboardController::class, 'incidents']);

    // Incident-dispatch write actions: blocked on a read-only (central
    // aggregator) instance — see docs/HYBRID_DEPLOYMENT.md.
    Route::middleware(PreventDashboardWritesWhenReadonly::class)->group(function () {
        Route::post('/dashboard/sos-ack', [DashboardController::class, 'sosAck']);
        Route::post('/dashboard/incidents/bulk-acknowledge', [DashboardController::class, 'bulkAcknowledgeIncidents']);
        Route::patch('/dashboard/incidents/{messageId}/status', [DashboardController::class, 'updateIncidentStatus']);
        Route::patch('/dashboard/incidents/{messageId}/notes', [DashboardController::class, 'updateIncidentNotes']);
        Route::patch('/dashboard/incidents/{messageId}/assign', [DashboardController::class, 'assignIncident']);
    });
    Route::get('/status', [StatusController::class, 'index']);
    Route::post('/status/send', [StatusController::class, 'message']);
    Route::post('/status/broadcast', [StatusController::class, 'broadcast']);
    Route::get('/status/history', [StatusController::class, 'history']);
    Route::get('/gps', [StatusController::class, 'gps']);
    Route::get('/gps/json', [StatusController::class, 'gpsJson']);
    Route::get('/gps/history/{duckId}', [StatusController::class, 'gpsHistory']);
    Route::post('/gps/request', [StatusController::class, 'requestGps']);
    Route::post('/gps/poll/toggle', [StatusController::class, 'toggleGpsPoll']);
    Route::post('/gps/poll/interval', [StatusController::class, 'setGpsPollInterval']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/data', [ReportController::class, 'data']);
    Route::get('/reports/incidents', [ReportController::class, 'incidentsList']);
    Route::get('/reports/export', [ReportController::class, 'exportPeriodCsv'])->name('reports.export');
    Route::get('/reports/print', [ReportController::class, 'exportPeriodPrint'])->name('reports.print');
    Route::get('/reports/incidents/{messageId}/export', [ReportController::class, 'exportIncidentCsv']);
    Route::get('/reports/incidents/{messageId}/print', [ReportController::class, 'exportIncidentPrint']);

    Route::get('/messages', [MessageLogController::class, 'index']);
    Route::get('/messages/json', [MessageLogController::class, 'json']);

    Route::get('/operations', [HealthController::class, 'operations'])->name('operations');
    Route::get('/operations/status', [HealthController::class, 'operationsStatus'])->name('operations.status');
});

require __DIR__.'/settings.php';
