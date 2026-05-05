<?php

use App\Http\Controllers\Api\DeviceAuthController;
use App\Http\Middleware\EnsureDeviceAppKey;
use App\Http\Middleware\SetApiLocale;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SubscriptionStatusController;

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