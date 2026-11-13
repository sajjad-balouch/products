<?php

use App\Models\School;
use App\Services\FeeService;
use App\Services\SiteBuilder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('fees:late', function (FeeService $fees) {
    $total = 0;
    foreach (School::where('is_active', true)->get() as $school) {
        $total += $fees->applyLateFees($school);
    }
    $this->info("Late fee updated on $total vouchers.");
})->purpose('Recalculate late fees on overdue vouchers for every school');

Schedule::command('fees:late')->dailyAt('00:30');

Artisan::command('site:business-page', function (SiteBuilder $builder) {
    $page = $builder->addBusinessProductPage($builder->platform());
    $this->info($page ? 'Added the "Installments & accounts" page and its menu links.' : 'The page is already there. Nothing changed.');
})->purpose('Add the installments and accounts product page to the owner website, if it is missing');

Artisan::command('app:install {username : Login for the platform owner, e.g. an email address} {--name=Platform Owner} {--password= : Leave empty to make a random one}', function (SiteBuilder $builder) {
    $username = Illuminate\Support\Str::lower(trim($this->argument('username')));
    if (App\Models\User::where('role', 'super_admin')->exists()) {
        $this->warn('A platform owner already exists. Nothing changed. Use the password page to change it.');

        return 1;
    }
    $password = $this->option('password') ?: Illuminate\Support\Str::password(14, symbols: false);
    App\Models\User::create([
        'name' => $this->option('name'), 'username' => $username, 'email' => str_contains($username, '@') ? $username : null,
        'role' => 'super_admin', 'password' => Illuminate\Support\Facades\Hash::make($password), 'must_change_password' => true,
    ]);
    $builder->addBusinessProductPage($builder->platform());
    $this->info("Platform owner created. Login: $username");
    $this->line("Password (change it on first login): $password");

    return 0;
})->purpose('First setup on a new server: creates the platform owner and the MSK Devs website');

Artisan::command('reminders:send {--school= : Only this school or business id} {--date= : Pretend today is this date (YYYY-MM-DD)}', function (App\Services\Reminders $reminders) {
    $date = $this->option('date') ? Illuminate\Support\Carbon::parse($this->option('date')) : today();
    if ($id = $this->option('school')) {
        $result = $reminders->run(School::findOrFail($id), $date);
        $this->info("Sent {$result['sent']}, failed {$result['failed']}, waiting in outbox {$result['waiting']}, already sent {$result['skipped']}.");

        return;
    }
    $t = $reminders->runAll($date);
    $this->info("{$t['schools']} schools/businesses: sent {$t['sent']}, failed {$t['failed']}, waiting in outbox {$t['waiting']}, already sent {$t['skipped']}.");
})->purpose('Send today\'s fee and installment reminders by SMS');

// Every morning: fee and installment reminders (time in .env: SMS_REMINDER_TIME).
Schedule::command('reminders:send')->dailyAt(config('services.sms.reminder_time', '09:00'))->withoutOverlapping();
