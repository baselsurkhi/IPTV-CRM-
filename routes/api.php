<?php

use App\Http\Controllers\Api\DeviceAuthController;
use App\Http\Controllers\Api\ManagedSessionController;
use App\Http\Controllers\Api\SubscriptionStatusController;
use App\Http\Middleware\EnsureDeviceAppKey;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware([SetApiLocale::class, EnsureDeviceAppKey::class])
    ->group(function (): void {

        Route::post('/device/register', [DeviceAuthController::class, 'register'])
            ->middleware('throttle:device-register');

        Route::post('/device/session', [DeviceAuthController::class, 'session'])
            ->middleware('throttle:device-session');

        Route::middleware('auth:sanctum')
            ->get('/subscription/status', [SubscriptionStatusController::class, 'status']);
    });
Route::prefix('v1')->middleware([SetApiLocale::class, EnsureDeviceAppKey::class])->group(function () {
    Route::post('/device/direct-session', [ManagedSessionController::class, 'store'])->middleware('throttle:device-session');
    Route::delete('/device/session', [ManagedSessionController::class, 'destroy'])->middleware('auth:sanctum');
});
