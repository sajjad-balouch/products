<?php

namespace App\Services;

use App\Models\Site;
use App\Support\SiteHost;
use Illuminate\Support\Facades\Http;

/** Checks from the server whether a school's address points here, reaches this website, and has working SSL. */
class DomainChecker
{
    /** @return array<string, mixed> */
    public function check(Site $site, string $host): array
    {
        $ips = $this->lookup($host);
        $server = config('sites.server_ip');
        $token = SiteHost::token($site);
        $reaches = false;
        $https = false;

        foreach (['https', 'http'] as $scheme) {
            try {
                $response = Http::timeout(8)->withoutRedirecting()->get("{$scheme}://{$host}/.well-known/msk-site.txt");
                if ($response->ok() && trim($response->body()) === $token) {
                    $reaches = true;
                    $https = $scheme === 'https';
                    break;
                }
            } catch (\Throwable) {
                // Not reachable this way. The other way is tried next.
            }
        }

        return [
            'host' => $host,
            'ips' => $ips,
            'points' => $server ? in_array($server, $ips, true) : null,
            'reaches' => $reaches,
            'https' => $https,
            'at' => now()->toDateTimeString(),
        ];
    }

    /** @return array<int, string> */
    public function lookup(string $host): array
    {
        return array_values(array_unique(gethostbynamel($host) ?: []));
    }
}
