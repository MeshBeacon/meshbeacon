<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\TelegramWebhookController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Unauthenticated: called by Telegram's servers, protected by a secret path
// segment + the X-Telegram-Bot-Api-Secret-Token header (see TelegramWebhookController).
Route::post('/telegram/webhook/{secret}', [TelegramWebhookController::class, 'handle']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/timeline', [DashboardController::class, 'timeline']);
    Route::get('/dashboard/hourly', [DashboardController::class, 'hourly']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/map-pins', [DashboardController::class, 'mapPins']);
    Route::get('/dashboard/duck-health', [DashboardController::class, 'duckHealth']);
    Route::get('/dashboard/topology', [DashboardController::class, 'topology']);
    Route::post('/dashboard/sos-ack', [DashboardController::class, 'sosAck']);
    Route::get('/dashboard/incidents/stats', [DashboardController::class, 'incidentStats']);
    Route::get('/dashboard/incidents/responders', [DashboardController::class, 'responders']);
    Route::post('/dashboard/incidents/bulk-acknowledge', [DashboardController::class, 'bulkAcknowledgeIncidents']);
    Route::patch('/dashboard/incidents/{messageId}/status', [DashboardController::class, 'updateIncidentStatus']);
    Route::patch('/dashboard/incidents/{messageId}/notes', [DashboardController::class, 'updateIncidentNotes']);
    Route::patch('/dashboard/incidents/{messageId}/assign', [DashboardController::class, 'assignIncident']);
    Route::get('/dashboard/incidents', [DashboardController::class, 'incidents']);
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
});

require __DIR__.'/settings.php';
