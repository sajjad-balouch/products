<?php

namespace App\Livewire\Fees;

use App\Models\FeeVoucher;
use App\Models\Payment;
use App\Services\FeeService;
use Carbon\Carbon;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Vouchers and collection')]
class Vouchers extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $month = '';

    public ?int $openId = null;

    public int $amount = 0;

    public string $method = 'cash';

    public string $reference = '';

    public string $paid_on = '';

    public string $reason = '';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status', 'month'], true)) {
            $this->resetPage();
        }
    }

    public function open(int $id): void
    {
        $voucher = FeeVoucher::findOrFail($id);
        $this->resetValidation();
        $this->openId = $id;
        $this->amount = $voucher->balance;
        $this->method = 'cash';
        $this->reference = '';
        $this->reason = '';
        $this->paid_on = now()->toDateString();
    }

    public function collect(FeeService $fees): void
    {
        $this->validate([
            'amount' => 'required|integer|min:1',
            'method' => 'required|in:cash,bank,jazzcash,easypaisa,raast,cheque',
            'reference' => 'nullable|string|max:100',
            'paid_on' => 'required|date|before_or_equal:today',
        ]);

        $this->attempt(function () use ($fees) {
            $payment = $fees->recordPayment(FeeVoucher::findOrFail($this->openId), $this->amount, $this->method, $this->reference ?: null, Carbon::parse($this->paid_on));
            $this->dispatch('toast', message: "Receipt {$payment->receipt_no} saved.");
            $this->amount = FeeVoucher::findOrFail($this->openId)->balance;
        });
    }

    public function voidPayment(FeeService $fees, int $paymentId): void
    {
        $this->attempt(function () use ($fees, $paymentId) {
            $payment = Payment::where('fee_voucher_id', $this->openId)->findOrFail($paymentId);
            $fees->voidPayment($payment, $this->reason);
            $this->reason = '';
            $this->dispatch('toast', message: 'Payment voided.');
        });
    }

    public function waive(FeeService $fees): void
    {
        $this->attempt(function () use ($fees) {
            $fees->waiveLateFee(FeeVoucher::findOrFail($this->openId), $this->reason);
            $this->reason = '';
            $this->dispatch('toast', message: 'Late fee waived.');
        });
    }

    public function voidVoucher(FeeService $fees): void
    {
        $this->attempt(function () use ($fees) {
            $fees->voidVoucher(FeeVoucher::findOrFail($this->openId), $this->reason);
            $this->openId = null;
            $this->reason = '';
            $this->dispatch('toast', message: 'Voucher voided.');
        });
    }

    private function attempt(callable $action): void
    {
        try {
            $action();
        } catch (InvalidArgumentException $e) {
            $this->addError('action', $e->getMessage());
        }
    }

    public function render()
    {
        $vouchers = FeeVoucher::query()->with(['guardian.students'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->month !== '', fn ($q) => $q->whereDate('month', $this->month.'-01'))
            ->when($this->search !== '', function ($q) {
                $q->where(fn ($s) => $s->where('voucher_no', 'like', "%{$this->search}%")
                    ->orWhereHas('guardian', fn ($g) => $g->where('name', 'like', "%{$this->search}%")->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhereHas('students', fn ($st) => $st->where('name', 'like', "%{$this->search}%")->orWhere('admission_no', 'like', "%{$this->search}%"))));
            })
            ->latest('month')->latest('id')->paginate(25);

        return view('livewire.fees.vouchers', [
            'vouchers' => $vouchers,
            'current' => $this->openId ? FeeVoucher::with(['guardian', 'lines.student', 'payments.receivedBy'])->find($this->openId) : null,
        ]);
    }
}
