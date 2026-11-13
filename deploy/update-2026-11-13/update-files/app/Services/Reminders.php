<?php

namespace App\Services;

use App\Models\Biz\Contract;
use App\Models\FeeVoucher;
use App\Models\Message;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Automatic reminders, once a day: some days before a fee or installment is due, on the day,
 * and every few days while it is late. Each reminder is sent once (its key is remembered),
 * through your SMS system when it is set up, otherwise it waits in the WhatsApp outbox.
 */
class Reminders
{
    public const DEFAULTS = [
        'enabled' => false,
        'days_before' => [3, 1],
        'on_due' => true,
        'late_every' => 3,   // days between reminders while late; 0 = none
        'late_times' => 3,   // how many late reminders at most
        'fee' => 'Assalam o Alaikum {name}. {students} ki fee {amount} ki aakhri tareekh {due_date} hai. Baraye meharbani waqt par ada karein. Shukriya. - {school}',
        'fee_late' => 'Assalam o Alaikum {name}. {students} ki fee {amount} {days_late} din se baqi hai (aakhri tareekh {due_date} thi). Baraye meharbani jald ada karein. - {school}',
        'installment' => 'Assalam o Alaikum {name}. Aap ki qist {amount} ({plan}) {due_date} ko wajib-ul-ada hai. Baqi raqam {balance}. Shukriya. - {business}',
        'installment_late' => 'Assalam o Alaikum {name}. Aap ki qist {amount} ({plan}) {days_late} din se late hai. Baraye meharbani foran ada karein. Baqi raqam {balance}. - {business}',
    ];

    public const PLACEHOLDERS = [
        'school' => ['{name}' => 'parent', '{students}' => 'children', '{amount}' => 'fee due', '{due_date}' => 'last date', '{days_late}' => 'days late', '{school}' => 'school name'],
        'business' => ['{name}' => 'customer', '{amount}' => 'installment due', '{due_date}' => 'due date', '{days_late}' => 'days late', '{plan}' => 'plan number', '{balance}' => 'still owed', '{business}' => 'business name'],
    ];

    public function __construct(private Sms $sms) {}

    public static function settings(School $school): array
    {
        return array_merge(self::DEFAULTS, $school->reminder_settings ?? []);
    }

    /** Which reminder, if any, is due today for something due on $due: "before-3", "due", "late-6". */
    public static function stage(array $s, Carbon $due, Carbon $today): ?string
    {
        $days = (int) $today->copy()->startOfDay()->diffInDays($due->copy()->startOfDay(), false);
        if ($days > 0) {
            return in_array($days, array_map('intval', $s['days_before']), true) ? 'before-'.$days : null;
        }
        if ($days === 0) {
            return $s['on_due'] ? 'due' : null;
        }
        $late = -$days;
        $every = (int) $s['late_every'];

        return $every > 0 && $late % $every === 0 && intdiv($late, $every) <= (int) $s['late_times'] ? 'late-'.$late : null;
    }

    /**
     * The reminders a school or business should get today, before anything is sent.
     *
     * @return Collection<int, array{key: string, phone: string, body: string, category: string, guardian_id: ?int, customer_id: ?int}>
     */
    public function due(School $school, Carbon $today): Collection
    {
        $s = self::settings($school);

        return $school->isBusiness() ? $this->installments($school, $s, $today) : $this->fees($school, $s, $today);
    }

    private function fill(string $template, array $values): string
    {
        return trim(strtr($template, $values));
    }

    private function fees(School $school, array $s, Carbon $today): Collection
    {
        $vouchers = FeeVoucher::withoutGlobalScopes()->where('school_id', $school->id)->open()
            ->with(['guardian.students' => fn ($q) => $q->withoutGlobalScopes()->where('status', 'active')])->get()
            ->filter(fn ($v) => $v->balance > 0 && $v->guardian && $v->guardian->phone);

        $out = collect();
        foreach ($vouchers->groupBy('guardian_id') as $list) {
            // The family's reminder follows its oldest unpaid voucher; the amount is everything due by then.
            $first = $list->sortBy('due_on')->first();
            if (! $stage = self::stage($s, $first->due_on, $today)) {
                continue;
            }
            $guardian = $first->guardian;
            $amount = $list->filter(fn ($v) => $v->due_on->lte($first->due_on->copy()->endOfMonth()))->sum('balance');
            $late = str_starts_with($stage, 'late');
            $out->push([
                'key' => 'fee:'.$guardian->id.':'.$first->due_on->toDateString().':'.$stage,
                'phone' => $guardian->phone,
                'category' => 'fee',
                'guardian_id' => $guardian->id,
                'customer_id' => null,
                'body' => $this->fill($s[$late ? 'fee_late' : 'fee'], [
                    '{name}' => $guardian->name,
                    '{students}' => $guardian->students->pluck('name')->map(fn ($n) => explode(' ', $n)[0])->join(', ', ' aur ') ?: 'Aap ke bachon',
                    '{amount}' => 'Rs '.number_format($amount),
                    '{due_date}' => $first->due_on->format('j M'),
                    '{days_late}' => (string) (int) $first->due_on->diffInDays($today),
                    '{school}' => $school->name,
                ]),
            ]);
        }

        return $out;
    }

    private function installments(School $school, array $s, Carbon $today): Collection
    {
        $contracts = Contract::withoutGlobalScopes()->where('school_id', $school->id)->where('status', 'active')->with(['customer', 'schedule'])->get();

        $out = collect();
        foreach ($contracts as $c) {
            $item = $c->schedule->sortBy('due_on')->first(fn ($i) => $i->amount > $i->paid);
            if (! $item || ! $c->customer?->phone || ! ($stage = self::stage($s, $item->due_on, $today))) {
                continue;
            }
            $late = str_starts_with($stage, 'late');
            $amount = $late ? $c->overdue($today) : $item->amount - $item->paid;
            $out->push([
                'key' => 'inst:'.$c->id.':'.$item->id.':'.$stage,
                'phone' => $c->customer->phone,
                'category' => 'installment',
                'guardian_id' => null,
                'customer_id' => $c->customer_id,
                'body' => $this->fill($s[$late ? 'installment_late' : 'installment'], [
                    '{name}' => $c->customer->name,
                    '{amount}' => 'Rs '.number_format(max($amount, $item->amount - $item->paid)),
                    '{due_date}' => $item->due_on->format('j M'),
                    '{days_late}' => (string) (int) $item->due_on->diffInDays($today),
                    '{plan}' => $c->number,
                    '{balance}' => 'Rs '.number_format($c->outstanding()),
                    '{business}' => $school->name,
                ]),
            ]);
        }

        return $out;
    }

    /**
     * Today's reminders for one school or business: each one is saved once and sent.
     *
     * @return array{sent: int, failed: int, waiting: int, skipped: int}
     */
    public function run(School $school, ?Carbon $today = null): array
    {
        $today ??= today();
        $count = ['sent' => 0, 'failed' => 0, 'waiting' => 0, 'skipped' => 0];
        if (! self::settings($school)['enabled'] || ! $school->is_active || $school->isLocked()) {
            return $count;
        }
        $automatic = Sms::enabledFor($school);

        foreach ($this->due($school, $today) as $r) {
            if (Message::withoutGlobalScopes()->where('school_id', $school->id)->where('dedupe_key', $r['key'])->exists()) {
                $count['skipped']++;

                continue;
            }
            $message = Message::withoutGlobalScopes()->create([
                'school_id' => $school->id, 'guardian_id' => $r['guardian_id'], 'customer_id' => $r['customer_id'],
                'phone' => $r['phone'], 'category' => $r['category'], 'channel' => 'whatsapp',
                'body' => $r['body'], 'status' => 'queued', 'dedupe_key' => $r['key'],
            ]);
            if (! $automatic) {
                $count['waiting']++;

                continue;
            }
            $result = $this->sms->deliver($message);
            // A message in line for the gateway phone counts as sent: the phone sends it within a minute.
            $count[$result['ok'] ? 'sent' : 'failed']++;
        }

        return $count;
    }

    /** Every school and business that has reminders on. */
    public function runAll(?Carbon $today = null): array
    {
        $total = ['schools' => 0, 'sent' => 0, 'failed' => 0, 'waiting' => 0, 'skipped' => 0];
        foreach (School::where('is_active', true)->whereNotNull('reminder_settings')->get() as $school) {
            if (! self::settings($school)['enabled']) {
                continue;
            }
            $total['schools']++;
            foreach ($this->run($school, $today) as $k => $n) {
                $total[$k] += $n;
            }
        }

        return $total;
    }
}
