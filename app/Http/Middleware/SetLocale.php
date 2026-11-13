<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale', 'en');

        App::setLocale(in_array($locale, ['en', 'ur'], true) ? $locale : 'en');

        return $next($request);
    }
}
