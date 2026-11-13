<?php

namespace App\Livewire\Fees;

use App\Models\AuditLog;
use App\Models\CashClosing;
use App\Models\Payment;
use App\Services\FeeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daily cash closing')]
class Closing extends Component
{
    public string $date = '';

    public ?int $counted = null;

    public string $note = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function close(FeeService $fees): void
    {
        $this->validate([
            'date' => 'required|date|before_or_equal:today',
            'counted' => 'required|integer|min:0',
            'note' => 'nullable|string|max:190',
        ]);

        $schoolId = Auth::user()->school_id;
        $date = Carbon::parse($this->date);

        if ($fees->isDayClosed($schoolId, $date)) {
            $this->addError('date', 'This date is already closed.');

            return;
        }

        $expected = $fees->expectedCash($schoolId, $date);
        $difference = $this->counted - $expected;

        if ($difference !== 0 && trim($this->note) === '') {
            $this->addError('note', 'The count does not match. Explain the difference.');

            return;
        }

        CashClosing::create([
            'date' => $date,
            'expected' => $expected,
            'counted' => $this->counted,
            'difference' => $difference,
            'note' => $this->note ?: null,
            'closed_by' => Auth::id(),
        ]);
        AuditLog::record('cash_closed', null, null, null, "{$date->toDateString()}: expected Rs $expected, counted Rs {$this->counted}");

        $this->reset('counted', 'note');
        $this->dispatch('toast', message: 'Day closed. Cash receipts for this date are now locked.');
    }

    public function render(FeeService $fees)
    {
        $schoolId = Auth::user()->school_id;
        $date = rescue(fn () => Carbon::parse($this->date), now(), false);

        return view('livewire.fees.closing', [
            'expected' => $fees->expectedCash($schoolId, $date),
            'isClosed' => $fees->isDayClosed($schoolId, $date),
            'payments' => Payment::valid()->whereDate('paid_on', $date)->with(['voucher.guardian', 'receivedBy'])->latest('id')->get(),
            'closings' => CashClosing::with('closedBy')->latest('date')->limit(15)->get(),
        ]);
    }
}
