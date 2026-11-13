<?php

use App\Http\Controllers\App\SalesAppController;
use App\Http\Middleware\AuthenticateAppToken;
use Illuminate\Support\Facades\Route;

/*
 * The salesman / owner phone app's API, under /sales/api. It signs in with a token, not a
 * session cookie, so it works from the installed Android app and after days offline.
 */
Route::post('/login', [SalesAppController::class, 'login'])->name('login')->middleware('throttle:20,1');

Route::middleware([AuthenticateAppToken::class, 'throttle:240,1'])->group(function () {
    Route::post('/logout', [SalesAppController::class, 'logout'])->name('logout');
    Route::get('/catalog', [SalesAppController::class, 'catalog'])->name('catalog');
    Route::get('/orders', [SalesAppController::class, 'orders'])->name('orders');
    Route::post('/orders', [SalesAppController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}', [SalesAppController::class, 'update'])->name('orders.update')->whereNumber('order');
});
