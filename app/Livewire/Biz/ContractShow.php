<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Contract;
use App\Models\Biz\Receipt;
use App\Models\Message;
use App\Services\Biz\Installments;
use App\Support\Phone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Installment plan')]
class ContractShow extends Component
{
    use InBusiness, ShowsImpact;

    public Contract $contract;

    #[Url]
    public bool $collect = false;

    public string $amount = '';

    public string $lateFee = '0';

    public string $discount = '0';

    public string $date = '';

    public string $method = 'cash';

    public string $reference = '';

    public string $note = '';

    public ?int $voidId = null;

    public string $voidReason = '';

    public bool $showWriteOff = false;

    public string $writeOffReason = '';

    public ?int $lastReceiptId = null;

    public function mount(): void
    {
        $this->prefill();
    }

    /** The counter starts with the next installment (or all that is overdue) and the late fee owed. */
    private function prefill(): void
    {
        $contract = $this->contract->fresh(['schedule']);
        $next = $contract->nextDue();
        $overdue = $contract->overdue();
        $this->amount = (string) ($overdue > 0 ? $overdue : ($next ? $next->amount - $next->paid : 0));
        $this->lateFee = (string) Installments::suggestedLateFee($contract);
        $this->discount = '0';
        $this->date = today()->toDateString();
        $this->reference = $this->note = '';
    }

    private function paymentRules(): array
    {
        return [
            'amount' => 'required|integer|min:0',
            'lateFee' => 'nullable|integer|min:0',
            'discount' => 'nullable|integer|min:0',
            'date' => 'required|date|before_or_equal:today',
            'method' => ['required', Rule::in(array_keys(Receipt::METHODS))],
            'reference' => 'nullable|string|max:60',
            'note' => 'nullable|string|max:255',
        ];
    }

    private const PAYMENT_MESSAGES = ['date.before_or_equal' => 'A payment cannot be dated in the future.'];

    /** The payment, exactly as saving takes it. The preview runs it on a fresh copy and undoes it. */
    private function collectPayment(Installments $installments, Contract $contract): Receipt
    {
        return $installments->collect($contract, (int) $this->amount, (int) $this->lateFee, (int) $this->discount,
            $this->date, $this->method, $this->reference ?: null, $this->note ?: null);
    }

    public function takePayment(Installments $installments): void
    {
        $this->validate($this->paymentRules(), self::PAYMENT_MESSAGES, ['lateFee' => 'late fee']);

        $receipt = $this->guarded(fn () => $this->collectPayment($installments, $this->contract), 'amount');

        if ($receipt) {
            $this->lastReceiptId = $receipt->id;
            $this->collect = false;
            $this->contract->refresh();
            $this->prefill();
            $this->dispatch('toast', message: 'Received '.rs($receipt->total()).'. Receipt '.$receipt->number.'.');
        }
    }

    public function voidReceipt(Installments $installments): void
    {
        $this->validate(['voidReason' => 'required|string|max:255'], [], ['voidReason' => 'reason']);
        $receipt = Receipt::where('contract_id', $this->contract->id)->findOrFail($this->voidId);

        if ($this->guarded(fn () => $installments->void($receipt, $this->voidReason) ?? true, 'voidReason')) {
            $this->voidId = null;
            $this->voidReason = '';
            $this->contract->refresh();
            $this->prefill();
            $this->dispatch('toast', message: 'Receipt '.$receipt->number.' cancelled and its entry reversed.');
        }
    }

    public function writeOff(Installments $installments): void
    {
        abort_unless(Auth::user()->isRole('admin'), 403);
        $this->validate(['writeOffReason' => 'required|string|max:255'], [], ['writeOffReason' => 'reason']);

        if ($this->guarded(fn () => $installments->writeOff($this->contract, $this->writeOffReason) ?? true, 'writeOffReason')) {
            $this->showWriteOff = false;
            $this->contract->refresh();
            $this->dispatch('toast', message: 'Written off as a bad debt.');
        }
    }

    /** Puts a reminder in the WhatsApp outbox and returns the link that opens it now. */
    public function remind(): void
    {
        $contract = $this->contract->fresh(['schedule', 'customer']);
        $text = self::reminderText($contract, $this->business()->name);
        Message::create(['school_id' => $contract->school_id, 'created_by' => Auth::id(), 'phone' => $contract->customer->phone, 'category' => 'installment', 'body' => $text]);
        $this->dispatch('open-whatsapp', url: 'https://wa.me/'.Phone::international($contract->customer->phone).'?text='.rawurlencode($text));
    }

    public static function reminderText(Contract $contract, string $business): string
    {
        $next = $contract->nextDue();
        $overdue = $contract->overdue();
        $owed = $overdue > 0
            ? 'Aap ki '.$contract->lateInstallments().' qist(en) late hain, raqam '.rs($overdue).'.'
            : 'Aap ki agli qist '.rs($next ? $next->amount - $next->paid : 0).' '.($next?->due_on->format('j M') ?? '').' ko wajib-ul-ada hai.';

        return "Assalam o Alaikum {$contract->customer->name}. {$owed} Plan {$contract->number} ({$contract->item}). Baqi raqam ".rs($contract->outstanding()).". Baraye meharbani waqt par ada karein. Shukriya. - {$business}";
    }

    public function render()
    {
        $this->contract->load(['schedule', 'customer', 'guarantors', 'receipts.receiver']);

        return view('livewire.biz.contract-show', [
            'c' => $this->contract,
            'paid' => $this->contract->paid(),
            'outstanding' => $this->contract->outstanding(),
            'overdue' => $this->contract->overdue(),
            'next' => $this->contract->nextDue(),
            'lastReceipt' => $this->lastReceiptId ? Receipt::find($this->lastReceiptId) : null,
            'isAdmin' => Auth::user()->isRole('admin'),
            'impact' => $this->impact(),
        ]);
    }

    /** What the open box (payment, cancelling a receipt, write-off) will change. Always on fresh copies. */
    private function impact(): ?array
    {
        $installments = app(Installments::class);
        $fresh = fn () => Contract::findOrFail($this->contract->id);

        return match (true) {
            $this->voidId !== null => $this->impactOf([], fn () => $installments->void(Receipt::where('contract_id', $this->contract->id)->findOrFail($this->voidId), $this->voidReason ?: 'Preview')),
            $this->showWriteOff => $this->impactOf([], fn () => $installments->writeOff($fresh(), $this->writeOffReason ?: 'Preview')),
            $this->collect => $this->impactOf($this->paymentRules(), fn () => $this->collectPayment($installments, $fresh()), self::PAYMENT_MESSAGES, ['lateFee' => 'late fee']),
            default => null,
        };
    }
}
