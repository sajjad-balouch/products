<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\School;
use App\Models\SubscriptionEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Moves a school's access date forward, and keeps a record of why. */
class SubscriptionService
{
    /** Months are added from the current end date, or from today if that has passed, so nothing is lost or given twice. */
    private function extend(School $school, int $months): array
    {
        $until = $school->accessUntil();
        $from = $until && $until->gte(today()) ? $until->copy()->addDay() : today();

        return [$from, $from->copy()->addMonthsNoOverflow($months)->subDay()];
    }

    public function giveFreeMonths(School $school, int $months, ?string $note = null): SubscriptionEntry
    {
        return DB::transaction(function () use ($school, $months, $note) {
            [$from, $to] = $this->extend($school, $months);
            $school->update(['access_until' => $to]);

            return $this->entry($school, ['type' => 'free', 'months' => $months, 'from_date' => $from, 'to_date' => $to, 'note' => $note]);
        });
    }

    public function recordPayment(School $school, int $amount, int $months, string $method, Carbon $paidOn, ?string $reference = null, ?string $note = null): SubscriptionEntry
    {
        return DB::transaction(function () use ($school, $amount, $months, $method, $paidOn, $reference, $note) {
            [$from, $to] = $this->extend($school, $months);
            // A first payment moves a school off the free period onto the first plan, as a label; its features stay as they are.
            $plan = $school->plan === 'trial' ? (Plan::orderBy('sort')->value('code') ?? 'basic') : $school->plan;
            $school->update(['access_until' => $to, 'plan' => $plan, 'is_active' => true]);

            return $this->entry($school, ['type' => 'payment', 'months' => $months, 'amount' => $amount, 'from_date' => $from, 'to_date' => $to, 'paid_on' => $paidOn, 'method' => $method, 'reference' => $reference, 'note' => $note]);
        });
    }

    public function setAccessUntil(School $school, Carbon $date, ?string $note = null): SubscriptionEntry
    {
        return DB::transaction(function () use ($school, $date, $note) {
            $school->update(['access_until' => $date]);

            return $this->entry($school, ['type' => 'adjust', 'to_date' => $date, 'note' => $note]);
        });
    }

    private function entry(School $school, array $values): SubscriptionEntry
    {
        return $school->subscriptionEntries()->create($values + ['user_id' => Auth::id()]);
    }
}
