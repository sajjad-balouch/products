<?php

namespace App\Livewire\Platform;

use App\Livewire\Platform\Concerns\OwnerOnly;
use App\Models\School;
use App\Models\Student;
use App\Models\SubscriptionEntry;
use Livewire\Attributes\Title;
use Livewire\Component;

/** The software owner's overview of every customer school and the money coming in. */
#[Title('Owner dashboard')]
class Dashboard extends Component
{
    use OwnerOnly;

    public function render()
    {
        $schools = School::orderBy('name')->get();
        $byStatus = $schools->groupBy(fn ($s) => $s->status());
        $live = $schools->whereNotIn('id', $byStatus->get('suspended', collect())->pluck('id'));
        $payments = SubscriptionEntry::where('type', 'payment');

        return view('livewire.platform.dashboard', [
            'total' => $schools->count(),
            'counts' => $byStatus->map->count(),
            'students' => Student::withoutGlobalScopes()->where('status', 'active')->count(),
            'expected' => $live->sum('monthly_fee'),
            'thisMonth' => (clone $payments)->whereBetween('paid_on', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
            'lastMonth' => (clone $payments)->whereBetween('paid_on', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('amount'),
            // Ending within two weeks, in grace, or locked: the ones to call.
            'attention' => $schools->filter(fn ($s) => in_array($s->status(), ['grace', 'expired'], true) || (in_array($s->status(), ['trial', 'active'], true) && $s->daysLeft() <= 14))
                ->sortBy(fn ($s) => $s->accessUntil()?->timestamp ?? PHP_INT_MAX)->values(),
            'recent' => SubscriptionEntry::with('school')->latest('id')->take(8)->get(),
        ]);
    }
}
