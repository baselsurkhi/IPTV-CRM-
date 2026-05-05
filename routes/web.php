<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/device-registry');
});

Route::get('/dashboard', function () {
    return redirect('/admin/device-registry');
})->middleware(['auth'])->name('dashboard');

Route::prefix('admin')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/devices', function () {
            return redirect('/admin/device-registry');
        })->name('devices.index');

        Route::get('/subscriptions', function () {
            return redirect('/admin/device-subscriptions');
        })->name('subscriptions.index');

    });

Route::middleware('auth')->group(function () {
    Route::get('/locale/{locale}', function (string $locale) {
        abort_unless(in_array($locale, config('app.supported_locales', []), true), 404);
        session(['locale' => $locale]);

        return redirect()->back();
    })->name('locale.switch');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';