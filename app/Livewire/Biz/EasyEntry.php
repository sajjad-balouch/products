<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Account;
use App\Models\Biz\Employee;
use App\Models\Biz\Vendor;
use App\Models\Biz\JournalEntry;
use App\Support\Biz\EasyEntries;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "What happened?" The user picks an everyday event in plain words and types the amount;
 * the system works out the debit and credit and shows exactly what will change before saving.
 */
#[Title('New entry')]
class EasyEntry extends Component
{
    use InBusiness, ShowsImpact;

    #[Url(as: 'what')]
    public ?string $type = null;

    public string $date = '';

    public string $amount = '';

    /** cash or bank: where the money came in or went out. */
    public string $money = 'cash';

    /** The account chosen from a list, such as which expense. */
    public string $pick = '';

    public string $note = '';

    public ?int $savedId = null;

    public function mount(): void
    {
        $this->date = today()->toDateString();
        if (! EasyEntries::get($this->type)) {
            $this->type = null;
        }
    }

    public function choose(?string $type): void
    {
        $this->resetValidation();
        $this->type = EasyEntries::get($type) ? $type : null;
        $this->amount = $this->pick = $this->note = '';
        $this->money = 'cash';
        $this->savedId = null;
    }

    /** Expenses the user may choose; sales costs, discounts and bad debts are posted by the system itself. */
    private function expenseAccounts()
    {
        return Account::where('is_active', true)->where('type', 'expense')
            ->where(fn ($q) => $q->whereNull('system_key')->orWhereIn('system_key', ['salaries', 'commission']))->orderBy('code')->get();
    }

    /** The list to choose from: which expense, which vendor or which employee. id => name */
    private function choices(?string $pick)
    {
        return match ($pick) {
            'expense' => $this->expenseAccounts()->pluck('name', 'id'),
            'vendor' => Vendor::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            'employee' => Employee::where('is_active', true)->orderBy('name')->pluck('name', 'id'),
            default => collect(),
        };
    }

    private function rules(): array
    {
        $entry = EasyEntries::get($this->type);

        return [
            'type' => ['required', Rule::in(array_keys(EasyEntries::ALL))],
            'date' => 'required|date|before_or_equal:today',
            'amount' => 'required|integer|min:1|max:1000000000',
            'money' => ['required', Rule::in(['cash', 'bank'])],
            'pick' => $entry && $entry['pick'] ? ['required', Rule::in($this->choices($entry['pick'])->keys()->map(fn ($id) => (string) $id)->all())] : 'nullable',
            'note' => 'nullable|string|max:200',
        ];
    }

    private const MESSAGES = [
        'amount.required' => 'Write the amount.',
        'amount.min' => 'Write the amount.',
        'pick.required' => 'Choose from the list.',
        'date.before_or_equal' => 'An entry cannot be dated in the future.',
    ];

    /** The books entry for this event. The user never sees debit and credit unless they open the details. */
    private function post(): JournalEntry
    {
        $entry = EasyEntries::get($this->type);
        $resolve = fn (string $side) => match ($side) {
            'money' => $this->money,
            'pick' => (int) $this->pick,
            default => $side,
        };
        $amount = (int) $this->amount;
        // A vendor or employee entry is on their own account, so both lines carry their name.
        $who = match ($entry['pick']) {
            'vendor' => ['vendor_id' => (int) $this->pick],
            'employee' => ['employee_id' => (int) $this->pick],
            default => [],
        };
        $name = $who ? ' · '.$this->choices($entry['pick'])->get((int) $this->pick) : '';
        $memo = $entry['en'][0].$name.($this->note !== '' ? ': '.$this->note : '');

        return $this->ledger()->post($entry['kind'], $this->date, $memo, [
            ['account' => $resolve($entry['debit']), 'debit' => $amount] + $who,
            ['account' => $resolve($entry['credit']), 'credit' => $amount] + $who,
        ]);
    }

    public function save(): void
    {
        $this->validate($this->rules(), self::MESSAGES);
        $saved = $this->guarded(fn () => $this->post(), 'amount');
        if ($saved) {
            $this->savedId = $saved->id;
            $this->amount = $this->note = '';
            $this->dispatch('toast', message: 'Saved as '.$saved->number.'.');
        }
    }

    public function render()
    {
        $entry = EasyEntries::get($this->type);

        return view('livewire.biz.easy-entry', [
            'entry' => $entry,
            'choices' => $this->choices($entry['pick'] ?? null),
            'saved' => $this->savedId ? JournalEntry::find($this->savedId) : null,
            'impact' => $entry && $this->amount !== '' ? $this->impactOf($this->rules(), fn () => $this->post(), self::MESSAGES) : null,
        ]);
    }
}
