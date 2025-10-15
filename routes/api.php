<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\TranslationController;
use Illuminate\Support\Facades\Route;

// Health check (no auth)
Route::get('/health', fn () => response()->json(['ok' => true, 'time' => now()->toISOString()]));

// Secure all API endpoints with token middleware
Route::middleware(['api', \App\Http\Middleware\ApiTokenAuth::class])->group(function () {
    Route::get('/translations', [TranslationController::class, 'index']);
    Route::get('/translations/{translation}', [TranslationController::class, 'show']);
    Route::post('/translations', [TranslationController::class, 'store']);
    Route::put('/translations/{translation}', [TranslationController::class, 'update']);
    Route::patch('/translations/{translation}', [TranslationController::class, 'update']);
    Route::delete('/translations/{translation}', [TranslationController::class, 'destroy']);

    // JSON export for frontends (Vue/others)
    Route::get('/export', [ExportController::class, 'export']);
});
