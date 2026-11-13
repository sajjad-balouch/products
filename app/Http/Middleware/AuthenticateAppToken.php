<?php

namespace App\Http\Middleware;

use App\Support\AppTokens;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** The phone app's requests: a bearer token instead of a session, so no cookies and no CSRF. */
class AuthenticateAppToken
{
    public function handle(Request $request, Closure $next)
    {
        $user = AppTokens::user($request->bearerToken());
        if (! AppTokens::allowed($user)) {
            return response()->json(['message' => 'Please sign in again.'], 401);
        }
        Auth::setUser($user);

        return $next($request);
    }
}
