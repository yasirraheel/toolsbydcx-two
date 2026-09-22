<?php

use Illuminate\Support\Facades\Route;

Route::get('/clear', function(){
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
});

Route::get('/login', function () {
    $notify[] = ['error', 'Your session has expired or you have been logged out remotely. Please login again.'];
    return redirect()->route('user.login')->withNotify($notify);
})->name('login');
Route::get('cron', 'CronController@cron')->name('cron');
Route::get('cron/cookie-check', 'CronController@cookieCheck')->name('cron.cookie.check');



// Extension API Routes (Session-based via normal auth)
Route::options('api/extension/{any?}', function() {
    return response('', 200)
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Admin-Key, Accept')
        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
})->where('any', '.*');

Route::prefix('api/extension')->name('api.extension.')->namespace('Api')->group(function () {
    Route::get('version', 'ExtensionController@version')->name('version');
    Route::post('login', 'ExtensionController@mobileLogin')->name('mobile.login');

    // Admin Master Cookie Sync Routes (Authorized via X-Admin-Key)
    Route::post('admin-sync', 'AdminSyncApiController@sync')->name('admin.sync');
    Route::get('admin-sync/accounts', 'AdminSyncApiController@accounts')->name('admin.sync.accounts');

    Route::middleware('auth')->group(function () {
        Route::get('me', 'ExtensionController@me')->name('me');
        Route::get('platforms', 'ExtensionController@platforms')->name('platforms');
        Route::get('cookies/{platformId}/{accountId?}', 'ExtensionController@getCookies')->name('cookies');
    });
});


// User Support Ticket (Disabled)
Route::prefix('ticket')->name('ticket.')->group(function () {
    Route::any('/', function () { return auth()->check() ? redirect()->route('user.home') : redirect()->route('home'); })->name('index');
    Route::any('new', function () { return auth()->check() ? redirect()->route('user.home') : redirect()->route('home'); })->name('open');
    Route::any('create', function () { return auth()->check() ? redirect()->route('user.home') : redirect()->route('home'); })->name('store');
    Route::any('view/{ticket}', function () { return auth()->check() ? redirect()->route('user.home') : redirect()->route('home'); })->name('view');
    Route::any('reply/{id}', function () { return auth()->check() ? redirect()->route('user.home') : redirect()->route('home'); })->name('reply');
    Route::any('close/{id}', function () { return auth()->check() ? redirect()->route('user.home') : redirect()->route('home'); })->name('close');
    Route::any('download/{attachment_id}', function () { return auth()->check() ? redirect()->route('user.home') : redirect()->route('home'); })->name('download');
});


Route::controller('WebController')->group(function () {
    // Account Listing
    Route::get('/account-listing', 'accountListing')->name('account.listing');
    Route::get('/account-listing/{slug}/{id}', 'accountListingDetails')->name('account.listing.details');
});

Route::controller('SiteController')->group(function () {
    Route::get('/contact', 'contact')->name('contact');
    Route::post('/contact', 'contactSubmit');
    Route::get('/change/{lang?}', 'changeLanguage')->name('lang');
    Route::get('cookie-policy', 'cookiePolicy')->name('cookie.policy');
    Route::get('/cookie/accept', 'cookieAccept')->name('cookie.accept');
    
    Route::get('extension/download/{filename?}', 'downloadExtension')->name('extension.download');

    Route::post('subscribe', 'subscribe')->name('subscribe');
    Route::get('blog/{slug}', 'blogDetails')->name('blog.details');
    Route::get('blogs', 'blogs')->name('blogs');
    Route::get('buy-accounts', 'buyAccounts')->name('buy.account');
    Route::get('plans', 'plans')->name('plans');



    Route::get('policy/{slug}', 'policyPages')->name('policy.pages');

    Route::get('placeholder-image/{size}', 'placeholderImage')->withoutMiddleware('maintenance')->name('placeholder.image');
    Route::get('maintenance-mode','maintenance')->withoutMiddleware('maintenance')->name('maintenance');
    Route::get('edge-only', 'edgeOnly')->name('edge.only');
    Route::get('/{slug}', 'pages')->name('pages');
    Route::get('/', 'index')->name('home');
});
