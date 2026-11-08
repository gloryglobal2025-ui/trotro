<?php

use App\Http\Controllers\Admin\AdvertisementController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BusStopController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FareController;
use App\Http\Controllers\Admin\PassManagementController;
use App\Http\Controllers\Admin\RideController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\VehicleManagementController;
use App\Http\Controllers\Admin\WebsiteController as AdminWebsiteController;
use App\Http\Controllers\Admin\ZoneController;
use App\Http\Controllers\WebsiteController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Serve Flutter web app at /web
Route::get('/web', function () {
    return response()->file(public_path('web/index.html'), [
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
});

// Serve static assets for Flutter web app from /web/*
Route::get('/web/{path}', function ($path) {
    $file = public_path('web/' . $path);
    if (file_exists($file) && !str_starts_with($path, '.')) {
        return response()->file($file, [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
    abort(404);
})->where('path', '.*');

// Public marketing website. The Flutter app stays at /web.
// Served without session/cookie middleware so responses stay edge-cacheable.
Route::get('/', [WebsiteController::class, 'index'])
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ])
    ->name('website.home');

Route::get('/sitemap.xml', [WebsiteController::class, 'sitemap'])
    ->withoutMiddleware([
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
    ])
    ->name('sitemap');

Route::get('/media/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);

    $disk = Storage::disk('public');

    abort_unless($disk->exists($path), 404);

    return response()->file($disk->path($path), [
        'Access-Control-Allow-Origin' => '*',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->name('media');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.store');
    Route::post('logout', [AuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('bus-stops', BusStopController::class);
        Route::resource('routes', RouteController::class);
        Route::get('routes/{route}/stops', [RouteController::class, 'stops'])->name('routes.stops');
        Route::put('routes/{route}/stops', [RouteController::class, 'updateStops'])->name('routes.stops.update');
        Route::resource('fares', FareController::class);
        Route::resource('zones', ZoneController::class);
        Route::resource('rides', RideController::class);
        Route::get('rides/{ride}/tracking', [RideController::class, 'tracking'])->name('rides.tracking');
        Route::get('rides/{ride}/tracking-data', [RideController::class, 'trackingData'])->name('rides.tracking.data');
        Route::prefix('passes')->name('passes.')->group(function () {
            Route::get('/', [PassManagementController::class, 'index'])->name('index');
            Route::get('/create', [PassManagementController::class, 'create'])->name('create');
            Route::post('/', [PassManagementController::class, 'store'])->name('store');
            Route::get('/{pass}', [PassManagementController::class, 'show'])->name('show');
            Route::post('/{pass}/topup', [PassManagementController::class, 'topUp'])->name('topup');
            Route::post('/{pass}/status', [PassManagementController::class, 'setStatus'])->name('status');
            Route::post('/{pass}/regenerate', [PassManagementController::class, 'regenerate'])->name('regenerate');
            Route::post('/{pass}/pin', [PassManagementController::class, 'setPin'])->name('pin');
            Route::post('/{pass}/pin/remove', [PassManagementController::class, 'removePin'])->name('pin.remove');
            Route::post('/{pass}/pin/unlock', [PassManagementController::class, 'unlockPin'])->name('pin.unlock');
            Route::post('/{pass}/approval', [PassManagementController::class, 'updateApproval'])->name('approval');
        });
        Route::get('website', [AdminWebsiteController::class, 'edit'])->name('website.edit');
        Route::put('website', [AdminWebsiteController::class, 'update'])->name('website.update');
        Route::post('settings/bulk-update', [SettingController::class, 'bulkUpdate'])->name('settings.bulkUpdate');
        Route::resource('settings', SettingController::class);
        Route::prefix('vehicles')->name('vehicles.')->group(function () {
            Route::get('/', [VehicleManagementController::class, 'index'])->name('index');
            Route::get('/create', [VehicleManagementController::class, 'create'])->name('create');
            Route::post('/', [VehicleManagementController::class, 'store'])->name('store');
            Route::get('/{vehicle}/edit', [VehicleManagementController::class, 'edit'])->name('edit');
            Route::put('/{vehicle}', [VehicleManagementController::class, 'update'])->name('update');
            Route::delete('/{vehicle}', [VehicleManagementController::class, 'destroy'])->name('destroy');
            Route::get('/attribute-setup', [VehicleManagementController::class, 'attributeSetup'])->name('attribute-setup');
            Route::post('/attribute-setup/brand', [VehicleManagementController::class, 'storeBrand'])->name('attribute-setup.storeBrand');
            Route::post('/attribute-setup/attribute', [VehicleManagementController::class, 'storeAttribute'])->name('attribute-setup.storeAttribute');
            Route::get('/request/list', [VehicleManagementController::class, 'newRequests'])->name('new-requests');
            Route::post('/request/{vehicle}/approve', [VehicleManagementController::class, 'approveRequest'])->name('new-requests.approve');
            Route::post('/request/{vehicle}/reject', [VehicleManagementController::class, 'rejectRequest'])->name('new-requests.reject');
            Route::get('/update/list', [VehicleManagementController::class, 'updateRequests'])->name('update-requests');
        });
        Route::prefix('adverts')->name('adverts.')->group(function () {
            Route::get('/', [AdvertisementController::class, 'index'])->name('index');
            Route::get('/create', [AdvertisementController::class, 'create'])->name('create');
            Route::post('/', [AdvertisementController::class, 'store'])->name('store');
            Route::get('/{advert}/edit', [AdvertisementController::class, 'edit'])->name('edit');
            Route::put('/{advert}', [AdvertisementController::class, 'update'])->name('update');
            Route::post('/{advert}/toggle', [AdvertisementController::class, 'toggle'])->name('toggle');
            Route::delete('/{advert}', [AdvertisementController::class, 'destroy'])->name('destroy');
        });
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserManagementController::class, 'index'])->name('index');
            Route::get('/create', [UserManagementController::class, 'create'])->name('create');
            Route::post('/', [UserManagementController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserManagementController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
            Route::get('/verification-requests', [UserManagementController::class, 'verificationRequests'])->name('verification-requests');
            Route::post('/{user}/verification/approve', [UserManagementController::class, 'approveVerification'])->name('verification-requests.approve');
            Route::post('/{user}/verification/reject', [UserManagementController::class, 'rejectVerification'])->name('verification-requests.reject');
            Route::get('/driver-levels', [UserManagementController::class, 'driverLevels'])->name('driver-levels');
            Route::post('/driver-levels', [UserManagementController::class, 'storeDriverLevel'])->name('storeDriverLevel');
            Route::get('/customer-levels', [UserManagementController::class, 'customerLevels'])->name('customer-levels');
            Route::post('/customer-levels', [UserManagementController::class, 'storeCustomerLevel'])->name('storeCustomerLevel');
            Route::get('/withdrawals', [UserManagementController::class, 'withdrawals'])->name('withdrawals');
            Route::post('/withdrawals/{withdrawal}/approve', [UserManagementController::class, 'approveWithdrawal'])->name('withdrawals.approve');
            Route::post('/withdrawals/{withdrawal}/reject', [UserManagementController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
            Route::get('/deposits', [UserManagementController::class, 'deposits'])->name('deposits');
            Route::post('/deposits/{deposit}/approve', [UserManagementController::class, 'approveDeposit'])->name('deposits.approve');
            Route::post('/deposits/{deposit}/reject', [UserManagementController::class, 'rejectDeposit'])->name('deposits.reject');
            Route::get('/customer-wallets', [UserManagementController::class, 'customerWallets'])->name('customer-wallets');
            Route::put('/customer-wallets/{wallet}', [UserManagementController::class, 'updateWallet'])->name('customer-wallets.update');
            Route::get('/wallet-bonuses', [UserManagementController::class, 'walletBonuses'])->name('wallet-bonuses');
            Route::post('/wallet-bonuses', [UserManagementController::class, 'storeWalletBonus'])->name('wallet-bonuses.store');
            Route::post('/wallet-bonuses/grant', [UserManagementController::class, 'grantBonus'])->name('wallet-bonuses.grant');
        });
    });
});
