<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Contract;
use App\Models\Biz\Invoice;
use App\Models\Biz\InvoicePayment;
use App\Models\Biz\Receipt;
use App\Models\Biz\ScheduleItem;
use App\Models\Biz\StockUnit;
use App\Support\BusinessKinds;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    use InBusiness;

    public function render()
    {
        $school = $this->business();
        $today = today();
        $monthStart = $today->copy()->startOfMonth();
        $ledger = $this->ledger();
        // Installments and down payments, plus money taken on cash / wholesale bills that were not cancelled.
        $received = fn ($from, $to) => (int) Receipt::whereNull('voided_at')->whereBetween('date', [$from->toDateString(), $to->toDateString()])->sum(DB::raw('amount + late_fee'))
            + (int) InvoicePayment::whereBetween('date', [$from->toDateString(), $to->toDateString()])->whereHas('invoice', fn ($q) => $q->whereNull('voided_at'))->sum('amount');

        $active = Contract::with(['customer', 'schedule'])->where('status', 'active')->get();
        $dueThisMonth = ScheduleItem::whereBetween('due_on', [$monthStart->toDateString(), $today->copy()->endOfMonth()->toDateString()])
            ->whereIn('contract_id', $active->pluck('id'))->selectRaw('COALESCE(SUM(amount),0) as due, COALESCE(SUM(paid),0) as paid')->first();

        $trend = collect(range(5, 0))->map(function ($ago) use ($today, $received) {
            $month = $today->copy()->startOfMonth()->subMonthsNoOverflow($ago);

            return ['label' => $month->format('M'), 'amount' => $received($month, $month->copy()->endOfMonth())];
        });

        $hour = now()->hour;

        return view('livewire.biz.dashboard', [
            'greeting' => $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening'),
            'todayCash' => $received($today, $today),
            'monthCash' => $received($monthStart, $today),
            'dueMonth' => (int) $dueThisMonth->due,
            'paidMonth' => (int) $dueThisMonth->paid,
            'overdue' => (int) $active->sum(fn ($c) => $c->overdue()),
            'lateCount' => $active->filter(fn ($c) => $c->overdue() > 0)->count(),
            'receivable' => $ledger->balanceOf('receivable'),
            'activeCount' => $active->count(),
            'udhaar' => $ledger->balanceOf('trade_receivable'),
            'kind' => $kind = BusinessKinds::of($school),
            'focus' => BusinessKinds::focus($kind),
            'salesMonth' => (int) Invoice::whereNull('voided_at')->whereBetween('date', [$monthStart->toDateString(), $today->toDateString()])->sum('total'),
            'stock' => StockUnit::where('status', 'in_stock')->selectRaw('COUNT(*) as pieces, COALESCE(SUM(unit_cost),0) as value')->first(),
            'salesToday' => (int) Invoice::whereNull('voided_at')->whereDate('date', $today)->sum('total'),
            'cash' => $ledger->balanceOf('cash'),
            'bank' => $ledger->balanceOf('bank'),
            'profit' => $ledger->profitLoss($monthStart->toDateString(), $today->toDateString())['profit'],
            'dueToday' => $active->filter(fn ($c) => ($n = $c->nextDue()) && $n->due_on->lte($today) && $c->daysLate() === 0)->take(8),
            'topLate' => $active->filter(fn ($c) => $c->overdue() > 0)->sortByDesc(fn ($c) => $c->daysLate())->take(6),
            'recent' => Receipt::with('contract.customer')->whereNull('voided_at')->latest('id')->limit(6)->get(),
            'trend' => $trend,
            'isAdmin' => Auth::user()->isRole('admin'),
            'newBusiness' => ! Contract::exists() && ! Invoice::exists(),
        ]);
    }
}
