<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DcxFlowController;
use App\Http\Middleware\DcxExtensionAuthMiddleware;

Route::prefix('dcx-flow')->group(function () {
    Route::get('/uninstall', [DcxFlowController::class, 'uninstall'])->middleware('throttle:10,1,dcx-uninstall');
    Route::post('/pair', [DcxFlowController::class, 'pair'])->middleware('throttle:10,1,dcx-pair');
    
    Route::middleware(['dcx.extension.auth', 'throttle:120,1,dcx-session'])->group(function () {
        Route::get('/status', [DcxFlowController::class, 'status']);
        Route::post('/start', [DcxFlowController::class, 'start']);
        Route::post('/step', [DcxFlowController::class, 'step']);
        Route::post('/finish', [DcxFlowController::class, 'finish']);
        Route::post('/disconnect', [DcxFlowController::class, 'disconnect']);
    });
});
