<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Vite;

/** The installable salesman / owner app (at /sales): its one page, its manifest and its offline worker. */
class SalesAppPageController extends Controller
{
    public static function name(): string
    {
        return (string) config('services.sales_app.name');
    }

    public function show()
    {
        return response()->view('sales-app.shell', ['appName' => self::name(), 'config' => [
            'api' => url('/sales/api'), 'sw' => route('app.sw'), 'scope' => url('/sales'), 'icon' => asset('app-icons/icon-192.png'), 'location' => true,
            'saleUrl' => url('/business/sales/new'), 'version' => '2.0',
        ]])->header('Cache-Control', 'no-cache');
    }

    public function manifest()
    {
        $icon = fn ($size, $purpose = 'any') => ['src' => asset("app-icons/icon-{$size}".($purpose === 'maskable' ? '-maskable' : '').'.png'), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => $purpose];

        return response()->json([
            'id' => route('app'),
            'name' => self::name(),
            'short_name' => (string) config('services.sales_app.short_name'),
            'description' => 'Take orders anywhere, even offline. They reach the owner straight away.',
            'start_url' => route('app'),
            'scope' => url('/sales'),
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#0b3b33',
            'theme_color' => '#0b3b33',
            'lang' => 'en',
            'icons' => [$icon(192), $icon(512), $icon(512, 'maskable')],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }

    public function worker()
    {
        $files = [Vite::asset('resources/css/app.css'), Vite::asset('resources/js/sales-app.js'), asset('app-icons/icon-192.png')];

        return response()->view('sales-app.sw', [
            'version' => substr(md5(implode('|', $files).filemtime(resource_path('views/sales-app/shell.blade.php'))), 0, 10),
            'shell' => route('app'),
            'files' => $files,
            'api' => url('/sales/api'),
        ])->header('Content-Type', 'application/javascript')->header('Cache-Control', 'no-cache')
            // The worker lives at /sales/sw.js but looks after /sales itself.
            ->header('Service-Worker-Allowed', parse_url(url('/sales'), PHP_URL_PATH) ?: '/sales');
    }

    /** Lets the Play Store app open the site full screen, without a browser bar (Trusted Web Activity). */
    public function assetLinks()
    {
        $package = config('services.sales_app.android_package');
        $prints = array_values(array_filter(array_map('trim', explode(',', (string) config('services.sales_app.android_sha256')))));

        return response()->json($package && $prints ? [[
            'relation' => ['delegate_permission/common.handle_all_urls'],
            'target' => ['namespace' => 'android_app', 'package_name' => $package, 'sha256_cert_fingerprints' => $prints],
        ]] : []);
    }
}
