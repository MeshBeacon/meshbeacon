<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StatusController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/json', [DashboardController::class, 'json']);
    Route::get('/dashboard/timeline', [DashboardController::class, 'timeline']);
    Route::get('/dashboard/hourly', [DashboardController::class, 'hourly']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/status', [StatusController::class, 'index']);
    Route::post('/status/send', [StatusController::class, 'message']);
    Route::post('/status/broadcast', [StatusController::class, 'broadcast']);
    Route::get('/status/history', [StatusController::class, 'history']);
    Route::get('/gps', [StatusController::class, 'gps']);
    Route::get('/gps/json', [StatusController::class, 'gpsJson']);
    Route::post('/gps/request', [StatusController::class, 'requestGps']);
    Route::post('/gps/poll/toggle', [StatusController::class, 'toggleGpsPoll']);
});

require __DIR__.'/settings.php';
