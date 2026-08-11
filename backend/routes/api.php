<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\N8nCallbackController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('n8n/callbacks/lead-enrichment', [N8nCallbackController::class, 'leadEnrichment']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('leads', [LeadController::class, 'index']);
        Route::post('leads', [LeadController::class, 'store']);
        Route::get('leads/{lead}', [LeadController::class, 'show']);
        Route::post('leads/{lead}/retry', [LeadController::class, 'retry']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
    });
});
