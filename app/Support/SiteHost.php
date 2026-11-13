<?php

namespace App\Support;

use App\Models\Site;

/** Finds which school website a web address belongs to: a free address on our domain, or the school's own domain. */
class SiteHost
{
    /** "HTTPS://www.School.edu.pk/about" becomes "school.edu.pk". */
    public static function normalize(?string $host): string
    {
        $host = strtolower(trim((string) $host));
        $host = preg_replace('#^[a-z]+://#', '', $host);
        $host = preg_replace('#[/?\#:].*$#', '', $host);

        return preg_replace('/^www\./', '', rtrim($host, '.'));
    }

    public static function baseDomain(): ?string
    {
        return config('sites.base_domain') ? self::normalize(config('sites.base_domain')) : null;
    }

    /** Addresses where the software itself runs, never a school website. */
    public static function isAppHost(string $host): bool
    {
        $host = self::normalize($host);

        return in_array($host, array_filter(['localhost', '127.0.0.1', '::1', '[::1]', self::normalize(parse_url((string) config('app.url'), PHP_URL_HOST)), self::baseDomain()]), true);
    }

    /** school-name.our-domain, as opposed to our own address or a school's own domain. */
    public static function isFreeAddress(?string $host): bool
    {
        $host = self::normalize($host);
        $base = self::baseDomain();

        return $base && ! self::isAppHost($host) && str_ends_with($host, '.'.$base);
    }

    public static function resolve(?string $host): ?Site
    {
        $host = self::normalize($host);

        if ($host === '' || self::isAppHost($host)) {
            return null;
        }

        $base = self::baseDomain();
        if ($base && str_ends_with($host, '.'.$base)) {
            $name = substr($host, 0, -strlen('.'.$base));

            return str_contains($name, '.') ? null : Site::whereNotNull('school_id')->where('subdomain', $name)->first();
        }

        return Site::whereNotNull('school_id')->where('domain', $host)->where('domain_status', '!=', 'none')->first();
    }

    /** What /.well-known/msk-site.txt answers on a school's address, so a check can prove the address reaches this site. */
    public static function token(Site $site): string
    {
        return substr(hash_hmac('sha256', 'site-domain:'.$site->id, (string) config('app.key')), 0, 32);
    }
}
