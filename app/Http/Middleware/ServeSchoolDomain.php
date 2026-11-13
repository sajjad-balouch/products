<?php

namespace App\Http\Middleware;

use App\Support\SiteHost;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs before routing. On a school's own address, "/" and "/about" are served as that school's
 * website pages. The rest of the software (sign in, dashboards) stays on the main address.
 */
class ServeSchoolDomain
{
    /** Paths a school website itself uses: pictures, the contact form, the admission form and so on. */
    private const SHARED = ['media/', 'site-contact/', 'apply/', 'verify/', 'locale/', 'livewire', 'build/', 'storage/', 'images/', 'up'];

    public function handle(Request $request, Closure $next): Response
    {
        $site = SiteHost::resolve($request->getHost());

        if (! $site) {
            // A free address no school has.
            abort_if(SiteHost::isFreeAddress($request->getHost()), 404);

            return $next($request);
        }

        $path = trim($request->path(), '/');

        if ($path === '.well-known/msk-site.txt') {
            return response(SiteHost::token($site), 200, ['Content-Type' => 'text/plain', 'Cache-Control' => 'no-store']);
        }

        $request->attributes->set('site_host', $site->id);
        $slug = $site->school->slug;

        // Links saved as /site/{school}/... (menus, buttons) stay on this domain, at its clean address.
        if ($path === "site/{$slug}" || Str::startsWith($path, "site/{$slug}/")) {
            $query = $request->getQueryString();

            return redirect()->to($request->getSchemeAndHttpHost().'/'.ltrim(substr($path, strlen("site/{$slug}")), '/').($query ? '?'.$query : ''), 301);
        }

        if ($path === '') {
            return $next($this->rewrite($request, "/site/{$slug}"));
        }

        if (preg_match('/^[a-z0-9-]+$/', $path) && $site->pages()->where('slug', $path)->exists()) {
            return $next($this->rewrite($request, "/site/{$slug}/{$path}"));
        }

        if (Str::startsWith($path, self::SHARED) || str_contains(basename($path), '.')) {
            return $next($request);
        }

        // Sign in, dashboards and anything else open on the main address.
        $query = $request->getQueryString();

        return redirect()->away(rtrim((string) config('app.url'), '/').'/'.$path.($query ? '?'.$query : ''));
    }

    private function rewrite(Request $request, string $path): Request
    {
        $query = $request->getQueryString();

        return $request->duplicate(server: ['REQUEST_URI' => $path.($query ? '?'.$query : '')] + $request->server->all());
    }
}
