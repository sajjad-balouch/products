<?php

namespace App\Providers;

use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Re-check role and account on every Livewire action, not only on page load.
        Livewire::addPersistentMiddleware([EnsureRole::class, EnsureAccountActive::class, SetLocale::class]);
    }
}
