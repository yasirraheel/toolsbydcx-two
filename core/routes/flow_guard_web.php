<?php

use App\Http\Controllers\User\FlowGuardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('flow-guard')->name('flow-guard.')->group(function () {
    Route::get('/session', [FlowGuardController::class, 'session'])->name('session');
    Route::post('/revoke', [FlowGuardController::class, 'revoke'])->name('revoke');
    Route::get('/download', function () {
        $file = base_path('../download/toolsbydcx-flow-guard.zip');
        abort_unless(is_file($file), 404);
        return response()->download($file, 'toolsbydcx-flow-guard.zip');
    })->name('download');
    Route::get('/watchdog-download', function () {
        $file = base_path('../download/toolsbydcx-flow-watchdog.zip');
        abort_unless(is_file($file), 404);
        return response()->download($file, 'toolsbydcx-flow-watchdog.zip');
    })->name('watchdog-download');
});
