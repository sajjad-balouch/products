<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Account;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/** One vendor's account: what was bought, what was paid, what is still owed. */
#[Title('Vendor')]
class VendorShow extends Component
{
    use InBusiness, ShowsImpact;

    public Vendor $vendor;

    /**
     * The open box, or null:
     *   pay      you pay the vendor                      opening  you owed them before using the system
     *   loan     the vendor lent you cash                bill     their bill for a service, part paid now, the rest owed
     */
    public ?string $action = null;

    public const ACTIONS = ['pay', 'opening', 'loan', 'bill'];

    public string $amount = '';

    public string $money = 'cash';

    public string $date = '';

    public string $note = '';

    /** Bill: which expense it was for. */
    public string $expense = '';

    /** Bill: how much of it was paid now; the rest is owed. */
    public string $paidNow = '0';

    public function open(string $action): void
    {
        abort_unless(in_array($action, self::ACTIONS, true), 404);
        $this->resetValidation();
        $this->action = $action;
        $this->amount = $action === 'pay' ? (string) max(0, $this->vendor->balance()) : '';
        $this->money = 'cash';
        $this->date = today()->toDateString();
        $this->note = $this->expense = '';
        $this->paidNow = '0';
    }

    /** Everyday expenses a vendor's bill can be for. */
    private function expenses()
    {
        return Account::where('is_active', true)->where('type', 'expense')->whereNull('system_key')->orderBy('code')->get();
    }

    private function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1|max:1000000000',
            'money' => ['required', Rule::in(['cash', 'bank'])],
            'date' => 'required|date|before_or_equal:today',
            'note' => 'nullable|string|max:150',
            'expense' => $this->action === 'bill' ? ['required', Rule::in($this->expenses()->pluck('id')->map(fn ($id) => (string) $id)->all())] : 'nullable',
            'paidNow' => $this->action === 'bill' ? 'nullable|integer|min:0|lte:amount' : 'nullable',
        ];
    }

    private const MESSAGES = ['amount.required' => 'Write the amount.', 'amount.min' => 'Write the amount.', 'date.before_or_equal' => 'This cannot be dated in the future.',
        'expense.required' => 'Choose what the bill was for.', 'paidNow.lte' => 'Paid now cannot be more than the bill.'];

    /** The books entry for the open box. Every line names the vendor, so it shows on their account. */
    private function post(): JournalEntry
    {
        $amount = (int) $this->amount;
        $v = $this->vendor->id;
        $name = $this->vendor->name;
        $note = $this->note !== '' ? ': '.$this->note : '';

        return match ($this->action) {
            // You pay: what you owe goes down, cash goes down.
            'pay' => $this->ledger()->post('payment', $this->date, 'Paid '.$name.$note, [
                ['account' => 'payable', 'debit' => $amount, 'vendor_id' => $v],
                ['account' => $this->money, 'credit' => $amount, 'vendor_id' => $v],
            ]),
            // They lend you cash: cash goes up, and you owe them more.
            'loan' => $this->ledger()->post('receipt', $this->date, 'Cash borrowed from '.$name.$note, [
                ['account' => $this->money, 'debit' => $amount, 'vendor_id' => $v],
                ['account' => 'payable', 'credit' => $amount, 'vendor_id' => $v],
            ]),
            // Their bill: the full expense now, part paid, the rest owed (a zero line is left out).
            'bill' => $this->ledger()->post('payment', $this->date, 'Bill from '.$name.$note, [
                ['account' => (int) $this->expense, 'debit' => $amount, 'vendor_id' => $v],
                ['account' => $this->money, 'credit' => (int) $this->paidNow, 'vendor_id' => $v],
                ['account' => 'payable', 'credit' => $amount - (int) $this->paidNow, 'vendor_id' => $v],
            ]),
            // Owed before the system: set against the owner's capital.
            default => $this->ledger()->post('journal', $this->date, 'Old balance owed to '.$name.$note, [
                ['account' => 'capital', 'debit' => $amount],
                ['account' => 'payable', 'credit' => $amount, 'vendor_id' => $v],
            ]),
        };
    }

    public function save(): void
    {
        $this->validate($this->rules(), self::MESSAGES);
        if ($entry = $this->guarded(fn () => $this->post(), 'amount')) {
            $this->action = null;
            $this->dispatch('toast', message: 'Saved as '.$entry->number.'.');
        }
    }

    public function render()
    {
        // Every entry that names this vendor, oldest first, with a running balance of what is owed.
        $rows = DB::table('biz_journal_lines as l')->join('biz_journal_entries as e', 'e.id', '=', 'l.entry_id')->join('biz_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('l.vendor_id', $this->vendor->id)
            ->groupBy('e.id', 'e.date', 'e.number', 'e.memo', 'e.kind')
            ->orderBy('e.date')->orderBy('e.id')
            ->selectRaw("e.id, e.date, e.number, e.memo, e.kind,
                SUM(CASE WHEN a.system_key = 'payable' THEN l.credit ELSE 0 END) as owed_more,
                SUM(CASE WHEN a.system_key = 'payable' THEN l.debit ELSE 0 END) as paid,
                SUM(CASE WHEN a.system_key IN ('cash', 'bank') THEN l.credit ELSE 0 END) as paid_now,
                SUM(CASE WHEN a.system_key IN ('cash', 'bank') THEN l.debit ELSE 0 END) as received")
            ->get();
        $running = 0;
        foreach ($rows as $r) {
            $running += (int) $r->owed_more - (int) $r->paid;
            $r->balance = $running;
        }

        return view('livewire.biz.vendor-show', [
            'v' => $this->vendor,
            'balance' => $this->vendor->balance(),
            'rows' => $rows->reverse()->values(),
            'bought' => (int) $this->vendor->purchases()->get()->sum(fn ($p) => $p->total()),
            'purchases' => $this->vendor->purchases()->with('product')->limit(30)->get(),
            'expenses' => $this->action === 'bill' ? $this->expenses() : collect(),
            'impact' => $this->action ? $this->impactOf($this->rules(), fn () => $this->post(), self::MESSAGES) : null,
        ]);
    }
}
