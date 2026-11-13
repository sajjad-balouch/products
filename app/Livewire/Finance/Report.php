<?php

namespace App\Livewire\Finance;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Payroll;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Income and expense')]
class Report extends Component
{
    public function render()
    {
        $months = collect(range(11, 0))->map(function ($back) {
            $month = now()->startOfMonth()->subMonths($back);
            $range = [$month->copy(), $month->copy()->endOfMonth()];

            $income = (int) Payment::valid()->whereBetween('paid_on', $range)->sum('amount');
            $expense = (int) Expense::whereBetween('spent_on', $range)->sum('amount');
            $salaries = (int) Payroll::whereDate('month', $month)->whereNotNull('paid_on')->sum('net');

            return ['month' => $month, 'income' => $income, 'expense' => $expense, 'salaries' => $salaries, 'net' => $income - $expense - $salaries];
        })->reverse()->values();

        return view('livewire.finance.report', ['months' => $months]);
    }
}
