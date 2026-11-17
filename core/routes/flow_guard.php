<?php

use App\Http\Controllers\Api\FlowGuardController;
use App\Http\Middleware\FlowGuardCors;
use Illuminate\Support\Facades\Route;

Route::middleware(FlowGuardCors::class)->group(function () {
    Route::options('/{any}', fn () => response('', 204))->where('any', '.*');

    Route::prefix('flow-guard')->group(function () {
        Route::post('/auth/login', [FlowGuardController::class, 'login'])->middleware('throttle:10,1');
        Route::middleware('throttle:180,1')->group(function () {
            Route::get('/status', [FlowGuardController::class, 'status']);
            Route::post('/auth/logout', [FlowGuardController::class, 'logout']);
            Route::post('/credits/reserve', [FlowGuardController::class, 'reserve']);
            Route::post('/credits/{id}/settle', [FlowGuardController::class, 'settle']);
            Route::get('/projects', [FlowGuardController::class, 'projects']);
            Route::post('/projects', [FlowGuardController::class, 'saveProject']);
            Route::delete('/projects/{externalProjectId}', [FlowGuardController::class, 'deleteProject']);
        });
    });
});
