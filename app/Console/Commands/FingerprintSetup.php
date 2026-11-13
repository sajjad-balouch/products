<?php

namespace App\Console\Commands;

use App\Http\Controllers\FingerprintHelperController;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Sets up fingerprints for USB readers in one step.
 *   --mode=server  the helper runs where the website runs (a VPS, or XAMPP on this computer)
 *   --mode=local   shared hosting: each school computer with a reader runs the helper itself
 */
class FingerprintSetup extends Command
{
    protected $signature = 'fingerprint:setup {--mode=server : server or local} {--file= : the .env file to change}';

    protected $description = 'Set up fingerprint readers: writes the .env settings and the helper settings';

    public function handle(): int
    {
        $mode = (string) $this->option('mode');
        if (! in_array($mode, ['server', 'local'], true)) {
            $this->error('Use --mode=server or --mode=local.');

            return self::INVALID;
        }

        $file = (string) ($this->option('file') ?: app()->environmentFilePath());
        if (! is_file($file)) {
            $this->error("No .env file at $file.");

            return self::FAILURE;
        }

        $env = (string) file_get_contents($file);
        $values = ['FINGERPRINT_MATCHER_MODE' => $mode];

        if ($mode === 'server') {
            $current = $this->read($env, 'FINGERPRINT_MATCHER_KEY');
            // A key copied from the README, or too short, is replaced with a real secret.
            $values['FINGERPRINT_MATCHER_KEY'] = strlen($current) >= 24 && $current !== 'your-long-random-secret' ? $current : Str::random(48);
            $values['FINGERPRINT_MATCHER_URL'] = $this->read($env, 'FINGERPRINT_MATCHER_URL') ?: 'http://127.0.0.1:5100';
        }

        foreach ($values as $key => $value) {
            $env = $this->write($env, $key, $value);
        }
        file_put_contents($file, $env);

        $origin = rtrim((string) preg_replace('#^(https?://[^/]+).*$#i', '$1', (string) config('app.url')), '/');
        $helperDir = (string) (realpath(dirname((string) config('services.fingerprint.helper_exe'))) ?: dirname((string) config('services.fingerprint.helper_exe')));
        if (is_dir($helperDir)) {
            // The helper next to this website gets the key (for the server) and this website's address (for browsers).
            $settings = array_filter(['MatcherKey' => $values['FINGERPRINT_MATCHER_KEY'] ?? null, 'AllowedOrigins' => [$origin]]);
            file_put_contents($helperDir.DIRECTORY_SEPARATOR.'fingerprint-helper.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
            foreach (FingerprintHelperController::scripts() as $name => $script) {
                file_put_contents($helperDir.DIRECTORY_SEPARATOR.$name, $script);
            }
        }

        $this->call('config:clear');
        $this->info("Fingerprints set up in $mode mode.");

        if ($mode === 'server') {
            $this->line('Next, on Windows (XAMPP): double-click this once, and the helper starts now and at every sign-in:');
            $this->line(is_dir($helperDir) ? '  '.$helperDir.DIRECTORY_SEPARATOR.'install-startup.bat' : '  build the helper first, see tools/fingerprint-matcher/README.md');
            $this->line('On a Linux server run the .NET build with MATCHER_KEY set to the key now in .env.');
        } else {
            $this->line('Next: on every school computer with a reader, download the helper from Staff & finance, Attendance machines,');
            $this->line('unzip it and run install-startup.bat once. Build the helper first if it is not in '.$helperDir.'.');
        }

        return self::SUCCESS;
    }

    private function read(string $env, string $key): string
    {
        return preg_match('/^'.$key.'=(.*)$/m', $env, $m) ? trim($m[1], " \t\"'\r") : '';
    }

    private function write(string $env, string $key, string $value): string
    {
        $line = $key.'='.$value;

        return preg_match('/^'.$key.'=.*$/m', $env)
            ? preg_replace('/^'.$key.'=.*$/m', $line, $env)
            : rtrim($env, "\r\n")."\n".$line."\n";
    }
}
