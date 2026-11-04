<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusStopController;
use App\Http\Controllers\Api\FareController;
use App\Http\Controllers\Api\PassChargeRequestController;
use App\Http\Controllers\Api\PassController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RideController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SmsWebhookController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\ZoneController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register'])
    ->middleware('throttle:login');
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::patch('/auth/profile', [AuthController::class, 'updateProfile']);
    Route::post('/auth/photo', [AuthController::class, 'uploadPhoto']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/rides/available', [RideController::class, 'availableRides']);
    Route::apiResource('rides', RideController::class)->except(['destroy']);
    Route::get('/rides/{ride}/nearby-drivers', [RideController::class, 'findNearbyDrivers']);
    Route::post('/rides/{ride}/rate', [RideController::class, 'rate']);
    Route::post('/rides/{ride}/location', [RideController::class, 'updateDriverLocation']);

    Route::apiResource('vehicles', VehicleController::class);
    Route::post('/vehicles/{vehicle}/photo', [VehicleController::class, 'uploadPhoto']);
    Route::post('/vehicles/{vehicle}/availability', [VehicleController::class, 'setAvailability']);

    Route::post('/payments/{ride}/process', [PaymentController::class, 'processPayment']);
    Route::get('/payments/{ride}/status', [PaymentController::class, 'getPaymentStatus']);
    Route::post('/payments/{ride}/refund', [PaymentController::class, 'refundPayment'])
        ->middleware('admin');

    Route::get('/passes/lookup', [PassController::class, 'lookup']);
    Route::post('/passes/{pass}/charge', [PassController::class, 'charge']);
    Route::post('/passes/{pass}/charge-requests', [PassChargeRequestController::class, 'store'])
        ->middleware('throttle:pass-charge');
    Route::get('/passes/charge-requests/{passChargeRequest}', [PassChargeRequestController::class, 'show']);
    Route::post('/passes/charge-requests/{passChargeRequest}/cancel', [PassChargeRequestController::class, 'cancel']);

    Route::get('/wallet', [WalletController::class, 'show']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::get('/wallet/deposits', [WalletController::class, 'deposits']);
    Route::post('/wallet/deposits', [WalletController::class, 'requestDeposit']);
    Route::get('/wallet/withdrawals', [WalletController::class, 'withdrawals']);
    Route::post('/wallet/withdrawals', [WalletController::class, 'requestWithdrawal']);
});

// Customer phone approval webhooks (SMS gateway / USSD aggregator).
// Protected by a shared secret, not by a user session.
Route::middleware(['sms.webhook', 'throttle:sms-webhook'])->group(function () {
    Route::post('/sms/inbound', [SmsWebhookController::class, 'inbound']);
    Route::post('/ussd', [SmsWebhookController::class, 'ussd']);
});

// Public read-only catalogue.
Route::get('/routes/search', [RouteController::class, 'search']);
Route::get('/routes/from-stop/{busStop}', [RouteController::class, 'fromStop']);
Route::get('/routes/{route}/stops', [RouteController::class, 'stops']);
Route::get('/routes/{route}/fare', [RouteController::class, 'segmentFare']);
Route::get('/fares/route/{routeId}', [FareController::class, 'getByRoute']);
Route::apiResource('bus-stops', BusStopController::class)->only(['index', 'show']);
Route::apiResource('routes', RouteController::class)->only(['index', 'show']);
Route::apiResource('fares', FareController::class)->only(['index', 'show']);
Route::get('/zones/by-location', [ZoneController::class, 'getZoneForLocation']);
Route::post('/zones/calculate-fare', [ZoneController::class, 'calculateFare']);
Route::apiResource('zones', ZoneController::class)->only(['index', 'show']);

Route::get('/settings', [SettingController::class, 'index']);
Route::get('/settings/{key}', [SettingController::class, 'show']);
Route::post('/settings/fare-calculation', [SettingController::class, 'fareCalculation']);

// Catalogue writes are restricted to administrators.
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('bus-stops', BusStopController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('routes', RouteController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('fares', FareController::class)->only(['store', 'update', 'destroy']);
    Route::apiResource('zones', ZoneController::class)->only(['store', 'update', 'destroy']);
});
