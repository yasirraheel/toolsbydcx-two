<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DcxFlowController;
use App\Http\Middleware\DcxExtensionAuthMiddleware;

Route::prefix('dcx-flow')->group(function () {
    Route::post('/pair', [DcxFlowController::class, 'pair']);
    
    Route::middleware(DcxExtensionAuthMiddleware::class)->group(function () {
        Route::get('/status', [DcxFlowController::class, 'status']);
        Route::post('/start', [DcxFlowController::class, 'start']);
        Route::post('/step', [DcxFlowController::class, 'step']);
        Route::post('/finish', [DcxFlowController::class, 'finish']);
        Route::post('/disconnect', [DcxFlowController::class, 'disconnect']);
    });
});
