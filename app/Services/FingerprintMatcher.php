<?php

namespace App\Services;

use App\Models\FingerprintTemplate;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Talks to the fingerprint matcher program, which turns pictures into templates and compares them. */
class FingerprintMatcher
{
    /** With shared hosting the server cannot run the helper, so each school computer runs it and its browser calls it. */
    public static function local(): bool
    {
        return config('services.fingerprint.mode') === 'local';
    }

    public static function ready(): bool
    {
        return self::local() || filled(config('services.fingerprint.key'));
    }

    /** What the browser needs to talk to the helper on its own computer. */
    public static function browserSettings(): array
    {
        return [
            'local' => self::local(),
            'url' => rtrim((string) config('services.fingerprint.local_url'), '/'),
            'dpi' => (float) config('services.fingerprint.dpi'),
            'threshold' => (float) config('services.fingerprint.threshold'),
        ];
    }

    /** A template from the helper on a school computer: plain base64 of a sensible size. */
    public static function looksLikeTemplate(string $template): bool
    {
        return strlen($template) >= 40 && strlen($template) <= 40000 && base64_decode($template, true) !== false;
    }

    private function post(string $path, array $body): array
    {
        $key = (string) config('services.fingerprint.key');
        if ($key === '') {
            throw new RuntimeException('The fingerprint matcher is not set up. Add FINGERPRINT_MATCHER_KEY to the .env file.');
        }

        try {
            $response = Http::timeout(20)->withHeaders(['X-Matcher-Key' => $key])
                ->post(rtrim((string) config('services.fingerprint.url'), '/').$path, $body);
        } catch (ConnectionException) {
            throw new RuntimeException('The fingerprint matcher is not running. Start it, then try again.');
        }

        if ($response->status() === 422) {
            throw new RuntimeException('The picture was not clear. Press the finger flat on the reader and try again.');
        }
        if (! $response->successful()) {
            throw new RuntimeException('The fingerprint matcher refused the request (code '.$response->status().'). Check FINGERPRINT_MATCHER_KEY.');
        }

        return $response->json() ?? [];
    }

    /** @param string $png base64 of a PNG from the reader */
    public function extract(string $png): string
    {
        $template = $this->post('/extract', ['image' => $png, 'dpi' => config('services.fingerprint.dpi')])['template'] ?? null;

        if (! is_string($template) || $template === '') {
            throw new RuntimeException('The matcher sent nothing back.');
        }

        return $template;
    }

    /**
     * The staff member whose template the picture matches, if the score reaches the threshold.
     *
     * @param  Collection<int, FingerprintTemplate>  $templates
     * @return array{staff_id: ?int, score: float}
     */
    public function identify(string $png, Collection $templates): array
    {
        if ($templates->isEmpty()) {
            return ['staff_id' => null, 'score' => 0.0];
        }

        $result = $this->post('/identify', [
            'image' => $png,
            'dpi' => config('services.fingerprint.dpi'),
            'threshold' => config('services.fingerprint.threshold'),
            'candidates' => $templates->map(fn ($t) => ['id' => (string) $t->staff_id, 'template' => $t->template])->values()->all(),
        ]);

        return ['staff_id' => isset($result['id']) ? (int) $result['id'] : null, 'score' => (float) ($result['score'] ?? 0)];
    }
}
