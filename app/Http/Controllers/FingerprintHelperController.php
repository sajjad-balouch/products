<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;

/**
 * The Windows fingerprint helper as a zip, set up for this website: the program, its settings
 * allowing only this website to use it, and a script that starts it by itself at sign-in.
 * It never contains the server's matcher key.
 */
class FingerprintHelperController extends Controller
{
    public function __invoke(Request $request)
    {
        $exe = (string) config('services.fingerprint.helper_exe');
        abort_unless(is_file($exe), 404, 'The fingerprint helper has not been built on this server yet. See tools/fingerprint-matcher/README.md.');

        $origins = collect([config('app.url'), $request->getSchemeAndHttpHost()])
            ->map(fn ($url) => rtrim((string) preg_replace('#^(https?://[^/]+).*$#i', '$1', (string) $url), '/'))
            ->filter()->unique()->values()->all();

        $zipPath = tempnam(sys_get_temp_dir(), 'fph');
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::OVERWRITE);
        $zip->addFile($exe, 'FingerprintHelper/FingerprintHelper.exe');
        $zip->addFromString('FingerprintHelper/fingerprint-helper.json', json_encode(['AllowedOrigins' => $origins], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
        foreach (self::scripts() as $name => $script) {
            $zip->addFromString('FingerprintHelper/'.$name, $script);
        }
        $zip->addFromString('FingerprintHelper/README.txt', self::crlf(implode("\n", [
            'Fingerprint helper for '.config('app.name'),
            '',
            '1. Install "HID DigitalPersona Lite Client" from HID and plug in the fingerprint reader.',
            '2. Put this folder somewhere it can stay, for example C:\FingerprintHelper.',
            '3. Double-click install-startup.bat. The helper starts now and at every sign-in.',
            '4. Open the kiosk link or the Staff fingerprints page in Chrome or Edge.',
            '',
            'Only these websites may use it: '.implode(', ', $origins),
            'It listens on http://127.0.0.1:5100, this computer only.',
        ])));
        $zip->close();

        return response()->download($zipPath, 'FingerprintHelper.zip', ['Content-Type' => 'application/zip'])->deleteFileAfterSend();
    }

    /** Scripts that start the helper hidden at every Windows sign-in, and stop that again. */
    public static function scripts(): array
    {
        return ['install-startup.bat' => self::crlf(<<<'BAT'
            @echo off
            rem Starts the fingerprint helper now, and by itself every time this computer signs in.
            set "DIR=%~dp0"
            set "STARTUP=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup"
            > "%STARTUP%\FingerprintHelper.vbs" (
              echo Set sh = CreateObject("WScript.Shell"^)
              echo sh.CurrentDirectory = "%DIR%"
              echo sh.Run """%DIR%FingerprintHelper.exe""", 0, False
            )
            taskkill /IM FingerprintHelper.exe /F >nul 2>&1
            start "" wscript "%STARTUP%\FingerprintHelper.vbs"
            echo.
            echo The fingerprint helper is running, and will start by itself when this computer signs in.
            echo Keep this folder where it is. If you move it, run install-startup.bat again.
            pause
            BAT),
            'uninstall-startup.bat' => self::crlf(<<<'BAT'
            @echo off
            del "%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup\FingerprintHelper.vbs" >nul 2>&1
            taskkill /IM FingerprintHelper.exe /F >nul 2>&1
            echo The fingerprint helper is stopped and will not start by itself any more.
            pause
            BAT),
        ];
    }

    /** Windows batch files need Windows line endings. */
    private static function crlf(string $text): string
    {
        return str_replace("\n", "\r\n", str_replace("\r\n", "\n", $text))."\r\n";
    }
}
