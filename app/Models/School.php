<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class School extends Model
{
    /** Parts of the system the owner can switch off per school, with the routes each one covers. */
    public const FEATURES = [
        'admissions' => ['Admissions and online apply form', ['admissions', 'apply']],
        'attendance' => ['Attendance', ['attendance.*']],
        'diary' => ['Daily diary', ['diary']],
        'timetable' => ['Timetable', ['timetable']],
        'exams' => ['Exams, marks and results', ['exams.*', 'print.report-card']],
        'fees' => ['Fees and vouchers', ['fees.*', 'print.voucher', 'print.receipt', 'proofs.image']],
        'finance' => ['Expenses and finance report', ['finance.*']],
        'staff' => ['Staff records and attendance', ['staff.index', 'staff.attendance', 'staff.attendance.register']],
        'payroll' => ['Payroll and salary slips', ['staff.payroll', 'print.payslip']],
        'biometric' => ['Biometric attendance machines', ['staff.devices', 'staff.fingerprints', 'staff.fingerprint-helper']],
        'certificates' => ['Certificates and ID cards', ['certificates', 'print.certificate', 'print.id-cards', 'cards', 'print.cards', 'cards.photo', 'cards.asset']],
        'library' => ['Library', ['library.*']],
        'transport' => ['Transport', ['transport']],
        'messages' => ['WhatsApp messages', ['messages.*']],
        'sms' => ['Automatic SMS / WhatsApp (reminders, alerts)', []],
        'risk' => ['Early warning', ['risk']],
        'portal' => ['Parent portal', ['portal']],
        'website' => ['Public website', ['website.*', 'site.school', 'site.school.page']],
        'installments' => ['Business: installment sales and recovery', ['biz.customer*', 'biz.contract*', 'biz.collect', 'biz.due', 'biz.reports.collections', 'biz.reports.aging', 'biz.product*', 'biz.reports.profit', 'biz.print.receipt', 'biz.print.statement', 'biz.print.agreement']],
        'accounts' => ['Business: accounts, vouchers and reports', ['biz.accounts', 'biz.vouchers', 'biz.entry', 'biz.vendor*', 'biz.employee*', 'biz.reports.salesmen', 'biz.ledger', 'biz.daybook', 'biz.reports.trial', 'biz.reports.pl', 'biz.reports.bs', 'biz.print.voucher']],
        'sales' => ['Business: cash and wholesale sales', ['biz.sale*', 'biz.print.invoice', 'biz.order*']],
    ];

    /** The features that make sense for a business. Every other feature is a school one. */
    public const BUSINESS_FEATURES = ['installments', 'sales', 'accounts', 'messages', 'sms', 'website'];

    public const PRODUCTS = ['school' => 'School management', 'business' => 'Business (installments and accounts)'];

    /** @return array<string, array{0: string, 1: array<int, string>}> the features of one product */
    public static function featuresFor(string $product): array
    {
        return array_filter(self::FEATURES, fn ($key) => $product === 'business'
            ? in_array($key, self::BUSINESS_FEATURES, true)
            : ! in_array($key, ['installments', 'sales', 'accounts'], true), ARRAY_FILTER_USE_KEY);
    }

    public function product(): string
    {
        return $this->isBusiness() ? 'business' : 'school';
    }

    /** The only screens a business (rather than a school) uses. */
    public const BUSINESS_ROUTES = ['biz.*', 'app', 'app.*', 'messages.*', 'users', 'audit', 'website.*', 'site.*', 'logout', 'password.change', 'subscription.expired', 'impersonation.stop', 'locale', '*livewire.*'];

    protected $guarded = [];

    protected $casts = [
        'trial_ends_at' => 'date',
        'access_until' => 'date',
        'is_active' => 'boolean',
        'absent_alerts' => 'boolean',
        'lock_when_due' => 'boolean',
        'features_off' => 'array',
        'staff_self_check_in' => 'boolean',
        'strict_attendance' => 'boolean',
        'card_settings' => 'array',
        'business_settings' => 'array',
        'reminder_settings' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function planModel()
    {
        return $this->belongsTo(Plan::class, 'plan', 'code');
    }

    public function planName(): string
    {
        return $this->plan === 'trial' ? 'Free period' : ($this->planModel?->name ?? ucfirst($this->plan));
    }

    /** A WhatsApp message asking the school to renew, ready for the owner to send. Null without a phone number. */
    public function reminderUrl(): ?string
    {
        if (! $this->phone || ! $this->accessUntil()) {
            return null;
        }

        $date = $this->accessUntil()->format('j M Y');
        $app = config('app.name');
        $text = match ($this->status()) {
            'grace', 'expired' => "Assalam o Alaikum. Your {$app} subscription for {$this->name} ended on {$date}.",
            default => "Assalam o Alaikum. Your {$app} subscription for {$this->name} ends on {$date}.",
        };
        if ($this->monthly_fee) {
            $text .= ' Monthly fee: Rs '.number_format($this->monthly_fee).'.';
        }
        $text .= ' Please renew to keep using the system without a break. Thank you.';

        return 'https://wa.me/'.Phone::international($this->phone).'?text='.rawurlencode($text);
    }

    public function subscriptionEntries()
    {
        return $this->hasMany(SubscriptionEntry::class)->latest('id');
    }

    public function accessUntil(): ?Carbon
    {
        return $this->access_until ?? $this->trial_ends_at;
    }

    /** suspended, trial, active, grace (past the date but not locked yet), expired, or open (no end date). */
    public function status(): string
    {
        $until = $this->accessUntil();

        return match (true) {
            ! $this->is_active => 'suspended',
            ! $until => 'open',
            $until->gte(today()) => $this->plan === 'trial' ? 'trial' : 'active',
            $until->copy()->addDays($this->grace_days)->gte(today()) => 'grace',
            default => 'expired',
        };
    }

    /** Past the date and the grace days, with locking turned on. */
    public function isLocked(): bool
    {
        return $this->status() === 'expired' && $this->lock_when_due;
    }

    public function daysLeft(): ?int
    {
        $until = $this->accessUntil();

        return $until ? (int) today()->diffInDays($until, false) : null;
    }

    public function site()
    {
        return $this->hasOne(Site::class);
    }

    public function hasFeature(string $feature): bool
    {
        return ! in_array($feature, $this->features_off ?? [], true);
    }

    public static function featureForRoute(?string $route): ?string
    {
        foreach (self::FEATURES as $key => [, $patterns]) {
            if ($route && Str::is($patterns, $route)) {
                return $key;
            }
        }

        return null;
    }

    public function isBusiness(): bool
    {
        return $this->type === 'business';
    }

    public function allowsRoute(?string $route): bool
    {
        // A business sees only its own screens, and a school never sees the business ones.
        if ($route && ($this->isBusiness() ? ! Str::is(self::BUSINESS_ROUTES, $route) : Str::startsWith($route, 'biz.'))) {
            return false;
        }

        $feature = self::featureForRoute($route);

        return ! $feature || $this->hasFeature($feature);
    }
}
