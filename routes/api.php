<?php

use App\Http\Controllers\Api\IngestController;
use App\Http\Middleware\VerifyCentralDmsToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Only route today is the hybrid-deployment ingestion endpoint used by
| field-node SyncRecordToCloud jobs to push records to a central server.
| See docs/HYBRID_DEPLOYMENT.md.
|
*/

Route::post('/ingest', [IngestController::class, 'store'])
    ->middleware(VerifyCentralDmsToken::class);
