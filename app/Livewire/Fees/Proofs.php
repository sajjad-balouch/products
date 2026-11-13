<?php

namespace App\Livewire\Fees;

use App\Models\PaymentProof;
use App\Services\FeeService;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Payment proofs')]
class Proofs extends Component
{
    public array $notes = [];

    public function approve(FeeService $fees, int $id): void
    {
        $this->run(function () use ($fees, $id) {
            $payment = $fees->approveProof(PaymentProof::findOrFail($id));
            $this->dispatch('toast', message: "Approved. Receipt {$payment->receipt_no} saved.");
        });
    }

    public function reject(FeeService $fees, int $id): void
    {
        $this->run(function () use ($fees, $id) {
            $fees->rejectProof(PaymentProof::findOrFail($id), (string) ($this->notes[$id] ?? ''));
            $this->dispatch('toast', message: 'Proof rejected. The parent can see the reason.');
        });
    }

    private function run(callable $action): void
    {
        try {
            $action();
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage(), error: true);
        }
    }

    public function render()
    {
        return view('livewire.fees.proofs', [
            'pending' => PaymentProof::with(['voucher', 'guardian'])->where('status', 'pending')->oldest()->get(),
            'recent' => PaymentProof::with(['voucher', 'guardian'])->where('status', '!=', 'pending')->latest('reviewed_at')->limit(15)->get(),
        ]);
    }
}
