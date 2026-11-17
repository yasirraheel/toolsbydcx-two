<?php

use App\Http\Controllers\Admin\FlowGuardController;
use Illuminate\Support\Facades\Route;

Route::middleware('admin')->group(function () {
    Route::get('/', [FlowGuardController::class, 'index'])->name('index');
    Route::post('/users/{id}', [FlowGuardController::class, 'update'])->name('users.update');
    Route::post('/users/{id}/revoke', [FlowGuardController::class, 'revokeSessions'])->name('users.revoke');
});
