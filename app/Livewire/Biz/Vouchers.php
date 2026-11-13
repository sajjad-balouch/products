<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Account;
use App\Models\Biz\JournalEntry;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Manual vouchers: a payment (money out, e.g. rent), a receipt (money in, e.g. capital)
 * or a journal (anything else, including opening balances). Posted vouchers are reversed, never edited.
 */
#[Title('Vouchers')]
class Vouchers extends Component
{
    use InBusiness, ShowsImpact, WithPagination;

    #[Url]
    public string $kind = '';

    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public ?string $newKind = null;

    public string $date = '';

    public string $memo = '';

    public ?int $moneyAccount = null;

    public array $lines = [];

    public ?int $reverseId = null;

    public string $reverseReason = '';

    public function mount(): void
    {
        $this->from = $this->from ?: today()->startOfMonth()->toDateString();
        $this->to = $this->to ?: today()->toDateString();
    }

    public function updated($name): void
    {
        if (in_array($name, ['kind', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function open(string $kind): void
    {
        abort_unless(in_array($kind, ['payment', 'receipt', 'journal'], true), 404);
        $this->resetValidation();
        $this->newKind = $kind;
        $this->date = today()->toDateString();
        $this->memo = '';
        $this->moneyAccount = $this->ledger()->account('cash')->id;
        $this->lines = $kind === 'journal' ? [$this->blank(), $this->blank()] : [$this->blank()];
    }

    private function blank(): array
    {
        // side: up or down. Nobody has to know which of those is a debit; see entryLines().
        return ['account' => '', 'amount' => '', 'side' => 'up', 'memo' => ''];
    }

    public function addLine(): void
    {
        $this->lines[] = $this->blank();
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    private const MESSAGES = ['lines.*.account.required' => 'Choose an account.', 'lines.*.amount.required' => 'Enter the amount.', 'date.before_or_equal' => 'A voucher cannot be dated in the future.'];

    private function rules(): array
    {
        $journal = $this->newKind === 'journal';

        return [
            'date' => 'required|date|before_or_equal:today',
            'memo' => 'required|string|max:255',
            'moneyAccount' => $journal ? 'nullable' : ['required', Rule::in(Account::where('type', 'asset')->pluck('id')->all())],
            'lines' => 'required|array|min:1|max:30',
            'lines.*.account' => ['required', Rule::in(Account::where('is_active', true)->pluck('id')->all())],
            'lines.*.amount' => 'required|integer|min:1',
            'lines.*.side' => $journal ? ['required', Rule::in(['up', 'down'])] : 'nullable',
            'lines.*.memo' => 'nullable|string|max:120',
        ];
    }

    /**
     * The debit and credit lines the voucher posts; payments and receipts add the cash or bank side.
     * A journal line says if its account goes up or down: assets and expenses go up with a debit,
     * everything else (what is owed, capital, income) goes up with a credit.
     */
    private function entryLines(): array
    {
        $types = Account::pluck('type', 'id');
        $lines = collect($this->lines)->map(fn ($l) => ['account' => (int) $l['account'], 'memo' => $l['memo'] ?: null] + match ($this->newKind) {
            'payment' => ['debit' => (int) $l['amount']],
            'receipt' => ['credit' => (int) $l['amount']],
            default => self::debitSide($types[(int) $l['account']] ?? null, $l['side'] ?? 'up') ? ['debit' => (int) $l['amount']] : ['credit' => (int) $l['amount']],
        })->all();

        if ($this->newKind !== 'journal') {
            $total = collect($lines)->sum(fn ($l) => ($l['debit'] ?? 0) + ($l['credit'] ?? 0));
            $lines[] = ['account' => $this->moneyAccount] + ($this->newKind === 'payment' ? ['credit' => $total] : ['debit' => $total]);
        }

        return $lines;
    }

    /** True when making an account of this type go up (or down) is a debit. */
    public static function debitSide(?string $type, string $side): bool
    {
        return in_array($type, ['asset', 'expense'], true) === ($side === 'up');
    }

    public function post(): void
    {
        $this->validate($this->rules(), self::MESSAGES, ['memo' => 'description']);

        $entry = $this->guarded(fn () => $this->ledger()->post($this->newKind, $this->date, $this->memo, $this->entryLines()), 'lines');
        if ($entry) {
            $this->newKind = null;
            $this->dispatch('toast', message: 'Voucher '.$entry->number.' posted.');
        }
    }

    public function reverse(): void
    {
        $this->validate(['reverseReason' => 'required|string|max:200'], [], ['reverseReason' => 'reason']);
        $entry = JournalEntry::with('lines')->findOrFail($this->reverseId);

        // Sales and collections are undone from their plan, so the installments stay right.
        if (in_array($entry->kind, ['sale', 'collection'], true)) {
            $this->addError('reverseReason', 'Cancel this from the installment plan (cancel the receipt) instead.');

            return;
        }

        if ($this->guarded(fn () => $this->ledger()->reverse($entry, $this->reverseReason), 'reverseReason')) {
            $this->reverseId = null;
            $this->reverseReason = '';
            $this->dispatch('toast', message: $entry->number.' reversed.');
        }
    }

    public function render()
    {
        return view('livewire.biz.vouchers', [
            'entries' => JournalEntry::with('lines.account')
                ->when($this->kind, fn ($q) => $q->where('kind', $this->kind))
                ->whereBetween('date', [$this->from ?: '1900-01-01', $this->to ?: '2999-12-31'])
                ->latest('date')->latest('id')->paginate(30),
            'accounts' => Account::where('is_active', true)->orderBy('code')->get(),
            'accountTypes' => Account::pluck('type', 'id'),
            // Cash, bank and other assets you can pay from; not the receivable or stock.
            'moneyAccounts' => Account::where('is_active', true)->where('type', 'asset')
                ->where(fn ($q) => $q->whereNull('system_key')->orWhereIn('system_key', ['cash', 'bank']))->orderBy('code')->get(),
            'impact' => match (true) {
                $this->reverseId !== null => $this->impactOf([], function () {
                    $entry = JournalEntry::with('lines')->findOrFail($this->reverseId);
                    if (in_array($entry->kind, ['sale', 'collection'], true)) {
                        throw new \InvalidArgumentException('Cancel this from the installment plan (cancel the receipt) instead.');
                    }

                    return $this->ledger()->reverse($entry, $this->reverseReason ?: 'Preview');
                }),
                $this->newKind !== null => $this->impactOf($this->rules(), fn () => $this->ledger()->post($this->newKind, $this->date, $this->memo ?: 'Preview', $this->entryLines()), self::MESSAGES, ['memo' => 'description']),
                default => null,
            },
        ]);
    }
}
