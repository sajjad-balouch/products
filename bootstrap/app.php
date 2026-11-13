<?php

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ServeSchoolDomain;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // The phone app's JSON API: token sign-in, no session or CSRF (see routes/app.php).
        then: function () {
            \Illuminate\Support\Facades\Route::middleware('api')->prefix('sales/api')->name('app.api.')->group(base_path('routes/app.php'));
            // Your own SMS gateway phone (MSK SMS Gateway app): a gw_ token, no session or CSRF.
            \Illuminate\Support\Facades\Route::middleware(['api', 'throttle:120,1'])->prefix('sms-gateway')->name('sms-gateway.')->group(function () {
                \Illuminate\Support\Facades\Route::post('pull', [\App\Http\Controllers\GatewayDeviceController::class, 'pull'])->name('pull');
                \Illuminate\Support\Facades\Route::post('report', [\App\Http\Controllers\GatewayDeviceController::class, 'report'])->name('report');
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['role' => EnsureRole::class]);
        // Before routing, so a school's own domain can serve its website at / and /about.
        $middleware->append(ServeSchoolDomain::class);
        $middleware->web(append: [SetLocale::class, EnsureAccountActive::class]);
        $middleware->redirectUsersTo(fn () => route(auth()->user()->homeRoute()));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
