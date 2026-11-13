# Fingerprint helper

USB fingerprint readers such as the HID DigitalPersona **U.are.U 5100** only take a picture of the finger. Recognising whose finger it is happens in this small program, with the free [SourceAFIS](https://sourceafis.machinezoo.com/) library. It keeps nothing: the school system stores the templates.

The browser reads the reader through **HID DigitalPersona Lite Client**, which must be installed on every computer with a reader. Any reader HID supports (U.are.U 4500, 5100, 5300 and others) is found by itself and reconnected when it is plugged back in.

## Pick the setup for your hosting

| Hosting | Command | Where the helper runs |
|---|---|---|
| **XAMPP on this computer** | `php artisan fingerprint:setup --mode=server` | On this computer. Double-click `dist/install-startup.bat` once. |
| **VPS** (Windows or Linux) | `php artisan fingerprint:setup --mode=server` | On the server, kept running (see below). |
| **Shared hosting** (cPanel) | `php artisan fingerprint:setup --mode=local` | On every school computer with a reader. Download it from Staff & finance, Attendance machines, unzip, double-click `install-startup.bat`. |

`fingerprint:setup` writes the `.env` settings, makes a real secret key, and writes `dist/fingerprint-helper.json` and the start-up scripts.

In **local** mode the school website's pages call the helper at `http://127.0.0.1:5100` on that computer. The helper only answers the school's own website address, so other websites cannot use it. The helper download never contains the server's key.

## Build the Windows helper

Needs the [.NET 8 SDK](https://dotnet.microsoft.com/download/dotnet/8.0). The result is one program that runs without .NET installed.

```bash
cd tools/fingerprint-matcher
dotnet publish -c Release -r win-x64 --self-contained true -p:PublishSingleFile=true -p:IncludeNativeLibrariesForSelfExtract=true -p:EnableCompressionInSingleFile=true -p:DebugType=none -o dist
```

On shared hosting, build it on your own computer and upload `dist/FingerprintHelper.exe` to the same folder on the server, so admins can download it. Or set `FINGERPRINT_HELPER_EXE` to where you put it.

## Run on a Linux VPS

```bash
dotnet publish -c Release -o publish
MATCHER_KEY=the-key-from-.env dotnet publish/FingerprintHelper.dll
```

Keep it running with a systemd service. It listens on `http://127.0.0.1:5100`, this machine only.

## Settings

The helper reads `fingerprint-helper.json` next to it, or environment variables:

| Setting | Meaning |
|---|---|
| `MatcherKey` / `MATCHER_KEY` | Secret shared with `FINGERPRINT_MATCHER_KEY` in `.env`, for the server |
| `AllowedOrigins` / `ALLOWED_ORIGINS` | School website addresses whose pages may use it, e.g. `https://myschool.com` |
| `Urls` / `ASPNETCORE_URLS` | Where to listen. Default `http://127.0.0.1:5100` |

The system's `.env`:

| Setting | Default | Meaning |
|---|---|---|
| `FINGERPRINT_MATCHER_MODE` | `server` | `server` or `local` (shared hosting) |
| `FINGERPRINT_MATCHER_URL` | `http://127.0.0.1:5100` | Where the server reaches the helper (server mode) |
| `FINGERPRINT_MATCHER_KEY` | none | The shared secret (server mode) |
| `FINGERPRINT_LOCAL_URL` | `http://127.0.0.1:5100` | Where browsers reach the helper (local mode) |
| `FINGERPRINT_MATCH_THRESHOLD` | `40` | Score needed to accept a match. Raise it if the wrong person is ever recognised |
| `FINGERPRINT_READER_DPI` | `500` | The reader's resolution. The U.are.U 5100 is 500 |

Check the helper: open `http://127.0.0.1:5100/health` from the school website's page, or `curl -H "X-Matcher-Key: <key>" http://127.0.0.1:5100/health`.
