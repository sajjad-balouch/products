<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Account;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Chart of accounts')]
class Accounts extends Component
{
    use InBusiness;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $code = '';

    public string $name = '';

    public string $type = 'expense';

    public bool $isActive = true;

    public function openForm(?int $id = null): void
    {
        $this->resetValidation();
        $account = $id ? Account::findOrFail($id) : null;
        $this->editingId = $account?->id;
        $this->code = (string) $account?->code;
        $this->name = (string) $account?->name;
        $this->type = $account?->type ?? 'expense';
        $this->isActive = $account?->is_active ?? true;
        $this->showForm = true;
    }

    public function save(): void
    {
        $account = $this->editingId ? Account::findOrFail($this->editingId) : null;
        $used = $account && $account->lines()->exists();

        $this->validate([
            'code' => ['required', 'regex:/^[0-9]{3,6}$/', Rule::unique('biz_accounts', 'code')->where('school_id', $this->business()->id)->ignore($this->editingId)],
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
        ], ['code.regex' => 'Use 3 to 6 digits, like 5250.']);

        // The type of an account with entries, or of one the system posts to, cannot change: the reports would break.
        if ($account && ($used || $account->system_key) && $account->type !== $this->type) {
            $this->addError('type', 'This account already has entries or is used by sales, so its type stays '.Account::TYPES[$account->type].'.');

            return;
        }
        if ($account?->system_key && ! $this->isActive) {
            $this->addError('name', 'Sales and collections post to this account, so it stays active.');

            return;
        }

        $values = ['code' => $this->code, 'name' => $this->name, 'type' => $this->type, 'is_active' => $this->isActive];
        $account ? $account->update($values) : Account::create($values);
        $this->showForm = false;
        $this->dispatch('toast', message: 'Account saved.');
    }

    public function render()
    {
        return view('livewire.biz.accounts', ['accounts' => $this->ledger()->accounts()->groupBy('type')]);
    }
}
