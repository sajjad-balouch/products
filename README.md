# MSK Devs

School and college management system, built with Laravel 12 and Livewire 4. It is multi-tenant, so one installation can serve many schools. The product name is set by `APP_NAME` in `.env`. The folder, database and demo logins still use the old working name `darsgah`, which users of the live product do not see.

## Run it locally

Needs PHP 8.2+, MySQL or MariaDB, Composer and Node. XAMPP's PHP and MySQL work.

```bash
php artisan serve --host=127.0.0.1 --port=8010
```

Open http://127.0.0.1:8010. To start again with fresh demo data (this erases the `darsgah` database):

```bash
php artisan migrate:fresh --seed
```

After changing any Blade or CSS class, rebuild the styles:

```bash
npm run build
```

## Demo logins

All use the password `password`. They exist only after seeding, and must never be used on a live server.

| Role | Login |
|---|---|
| Principal / admin | admin@darsgah.test |
| Accountant | accounts@darsgah.test |
| Teacher | teacher@darsgah.test |
| Parent | 03001000001 |
| Software owner (all schools) | owner@darsgah.test |

## Tests

```bash
php artisan test
```

Tests use a separate database, `darsgah_test`, so they cannot touch your data. Create it once if you set this up on a new machine.

## What each role gets

- **Admin:** everything below, plus settings, logins and the audit log.
- **Accountant:** fees, payment proofs, cash closing, expenses, WhatsApp outbox. Can view students.
- **Teacher:** attendance, marks and diary for their own classes only, and the timetable. A teacher's login must be linked to a staff record under User logins.
- **Parent:** the portal, in English or Urdu. Their own children only.
- **Software owner:** the owner panel at `/platform`: every school's monthly fee, free months, payments, access date and lock, which features it has, its logins (reset, disable, "Sign in as") and its website. Create this login by hand on a live server.

## Modules

Students, admissions pipeline with public form, Excel (CSV) import, promotion, attendance, timetable with clash check, exams, marks, results and report cards, family fee vouchers, late fees, sibling discounts, concessions, defaulters, cash closing, expenses, payroll, library, transport, certificates with QR check, ID cards, WhatsApp outbox, early warning, audit log.

## Public websites

- `/` is the software owner's own site. Unpublish it and `/` goes back to the sign-in page.
- Each school gets its own site at `/site/{school-slug}`. It starts as a draft with sample text, and only its admin can publish it.
- **Website** in the side menu manages pages, sections, look, header, footer, contact details, menus and contact form messages.
- **Website editors** lets the admin give another login only certain areas or pages. A "Website editor" login can open nothing else.
- `php artisan db:seed --class=DemoWebsiteSeeder` fills the owner's site and the demo school's site with sample photos and text. It keeps any picture already uploaded, and only rebuilds a site nobody has edited. The photos are free Unsplash images, listed in `database/seeders/demo-images/CREDITS.md`.
- Editors cannot enter raw HTML or scripts: text is escaped, links are checked, and only JPG, PNG and WebP pictures are accepted.

## Staff attendance machines

- **ZKTeco and similar machines** push punches to `/iclock/...` once their Cloud Server setting points at this website. Add the machine on Staff & finance, Attendance machines.
- **Other machines or software** post JSON to `/api/biometric/punches` with an `X-Device-Token`.
- **USB readers such as the U.are.U 5100** work as a kiosk page on a school computer. They need the fingerprint matcher in `tools/fingerprint-matcher` running on the server; see its README.
- **Strict attendance** (Settings) accepts machines and staff check-ins only from the school's network or grounds, and locks machine times on the attendance sheet.

## Not connected yet

These need an outside account or contract, so they are not live:

- **Online payment gateway** (JazzCash, Easypaisa, 1Bill or Raast APIs). Parents pay on their own and upload proof, and the office approves it. Nothing confirms a payment automatically.
- **Automatic WhatsApp or SMS.** Messages open in WhatsApp with one tap, and a person presses send. Automatic sending needs a WhatsApp Business API or SMS provider.
- **Subscription billing.** Payments are recorded by hand in the owner panel; nothing is charged automatically. A school past its access date plus its extra days is locked (its records stay) unless locking is turned off for it.

Also not included: a native mobile app (the parent portal is a mobile web page), offline use, hostel management, student logins, and photo upload. Only the parent portal is in Urdu; the office screens are English.

## Before going live

1. Set `APP_ENV=production`, `APP_DEBUG=false` and a real `APP_URL` in `.env`, and serve the site over HTTPS.
2. Point the web server at the `public` folder.
3. Do not run the seeder. It refuses to run when `APP_ENV=production`.
4. Run `php artisan migrate --force`, `php artisan optimize` and `npm run build`.
5. Add this cron entry so late fees update every night:

   ```
   * * * * * php /path/to/darsgah/artisan schedule:run
   ```

6. Back up the database and `storage/app/private` (payment screenshots) every day.
7. Have a lawyer review terms of use and privacy, as you will hold children's data.

All money is stored as whole rupees.
