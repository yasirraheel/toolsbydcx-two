<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Reseller')->name('reseller.')->middleware(['auth', 'reseller'])->group(function () {
    Route::controller('ResellerController')->group(function () {
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('pricing', 'pricing')->name('pricing');
        Route::get('transactions', 'transactions')->name('transactions');
        Route::get('deposit-history', 'depositHistory')->name('deposit.history');

        // Deposit & Wallet Recharge (Native Platform Flow)
        Route::get('deposit', 'deposit')->name('deposit');
        Route::post('deposit/insert', 'depositInsert')->name('deposit.insert');
        Route::get('deposit/confirm', 'depositConfirm')->name('deposit.confirm');
        Route::get('deposit/manual', 'manualDepositConfirm')->name('deposit.manual.confirm');
        Route::post('deposit/manual', 'manualDepositUpdate')->name('deposit.manual.update');

        // Client User Management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', 'users')->name('index');
            Route::get('create', 'createUser')->name('create');
            Route::post('store', 'storeUser')->name('store');
            Route::get('edit/{id}', 'editUser')->name('edit');
            Route::post('update/{id}', 'updateUser')->name('update');
            Route::post('extend/{id}', 'extendUser')->name('extend');
            Route::post('status/{id}', 'statusUser')->name('status');
            Route::post('delete/{id}', 'deleteUser')->name('delete');
            Route::post('logout/{id}', 'logoutUser')->name('logout');
        });
    });
});
