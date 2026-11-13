<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Contract;
use App\Models\Biz\Employee;
use App\Models\Biz\Invoice;
use App\Models\Biz\JournalEntry;
use App\Models\User;
use App\Support\AppTokens;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Component;

/** One employee: salary, advances and, for a salesman, sales and commission. */
#[Title('Employee')]
class EmployeeShow extends Component
{
    use InBusiness, ShowsImpact;

    public Employee $employee;

    /** salary | advance | commission, or null when no box is open. */
    public ?string $action = null;

    public string $amount = '';

    /** Advance taken back from this salary. */
    public string $deduct = '0';

    public string $month = '';

    public string $money = 'cash';

    public string $date = '';

    public string $note = '';

    public function open(string $action): void
    {
        abort_unless(in_array($action, ['salary', 'advance', 'commission'], true), 404);
        $this->resetValidation();
        $this->action = $action;
        $this->month = today()->format('Y-m');
        $this->date = today()->toDateString();
        $this->money = 'cash';
        $this->note = '';
        $this->deduct = '0';
        $e = $this->employee;
        $this->amount = match ($action) {
            'salary' => (string) ($e->salary ?: ''),
            'commission' => (string) max(0, $this->commissionDue()),
            default => '',
        };
        if ($action === 'salary') {
            $this->deduct = (string) min($e->advance(), (int) $e->salary);
        }
    }

    private function commissionDue(): int
    {
        $from = today()->startOfMonth()->toDateString();
        $to = today()->toDateString();

        return $this->employee->salesBetween($from, $to)['commission'] - $this->employee->paid('commission', $from, $to);
    }

    private function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1|max:1000000000',
            'deduct' => $this->action === 'salary' ? 'nullable|integer|min:0|lte:amount' : 'nullable',
            'month' => $this->action === 'salary' ? 'required|date_format:Y-m' : 'nullable',
            'money' => ['required', Rule::in(['cash', 'bank'])],
            'date' => 'required|date|before_or_equal:today',
            'note' => 'nullable|string|max:150',
        ];
    }

    private const MESSAGES = ['amount.required' => 'Write the amount.', 'amount.min' => 'Write the amount.', 'deduct.lte' => 'Cannot take back more than the salary.', 'date.before_or_equal' => 'This cannot be dated in the future.'];

    private function post(): JournalEntry
    {
        $e = $this->employee;
        $amount = (int) $this->amount;
        $note = $this->note !== '' ? ': '.$this->note : '';

        if ($this->action === 'salary') {
            $deduct = (int) $this->deduct;
            if ($deduct > $e->advance()) {
                throw new InvalidArgumentException($e->name.' owes only '.rs($e->advance()).' in advances.');
            }
            $label = Carbon::createFromFormat('Y-m', $this->month)->format('F Y');

            return $this->ledger()->post('payment', $this->date, 'Salary for '.$label.' · '.$e->name.$note, array_values(array_filter([
                ['account' => 'salaries', 'debit' => $amount, 'employee_id' => $e->id],
                $deduct > 0 ? ['account' => 'staff_advance', 'credit' => $deduct, 'employee_id' => $e->id, 'memo' => 'Advance taken back'] : null,
                $amount - $deduct > 0 ? ['account' => $this->money, 'credit' => $amount - $deduct, 'employee_id' => $e->id] : null,
            ])));
        }

        [$account, $memo] = $this->action === 'advance' ? ['staff_advance', 'Advance to '] : ['commission', 'Commission to '];

        return $this->ledger()->post('payment', $this->date, $memo.$e->name.$note, [
            ['account' => $account, 'debit' => $amount, 'employee_id' => $e->id],
            ['account' => $this->money, 'credit' => $amount, 'employee_id' => $e->id],
        ]);
    }

    // ---- App login: lets the employee use the salesman phone app ----

    public bool $showLogin = false;

    public string $loginUsername = '';

    public string $loginPassword = '';

    public function openLogin(): void
    {
        $this->resetValidation();
        $user = $this->employee->user;
        $this->loginUsername = $user?->username ?? (string) $this->employee->phone;
        $this->loginPassword = '';
        $this->showLogin = true;
    }

    public function saveLogin(): void
    {
        $user = $this->employee->user;
        $this->loginUsername = Str::lower(trim($this->loginUsername));
        $this->validate([
            'loginUsername' => ['required', 'string', 'min:4', 'max:120', Rule::unique('users', 'username')->ignore($user?->id)],
            'loginPassword' => [$user ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
        ], ['loginUsername.unique' => 'Someone already uses this login. Try their phone with a 0 at the start, or an email.'], ['loginUsername' => 'login', 'loginPassword' => 'password']);

        DB::transaction(function () use ($user) {
            $data = ['username' => $this->loginUsername, 'name' => $this->employee->name, 'is_active' => true];
            if ($this->loginPassword !== '') {
                $data['password'] = Hash::make($this->loginPassword);
            }
            if ($user) {
                $user->update($data);
                if ($this->loginPassword !== '') {
                    AppTokens::revokeAll($user); // a new password signs the old phones out
                }
            } else {
                $user = User::create($data + ['school_id' => $this->business()->id, 'role' => 'salesman', 'phone' => $this->employee->phone, 'must_change_password' => false]);
                $this->employee->update(['user_id' => $user->id]);
            }
        });
        $this->showLogin = false;
        $this->employee->refresh();
        $this->dispatch('toast', message: 'App login saved. Share the login and password with '.$this->employee->name.'.');
    }

    public function switchOffLogin(): void
    {
        if ($user = $this->employee->user) {
            $user->update(['is_active' => false]);
            AppTokens::revokeAll($user);
            $this->dispatch('toast', message: 'App login switched off. Their phone is signed out.');
        }
        $this->showLogin = false;
        $this->employee->refresh();
    }

    public function switchOnLogin(): void
    {
        $this->employee->user?->update(['is_active' => true]);
        $this->employee->refresh();
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
        $e = $this->employee;
        $from = today()->startOfMonth()->toDateString();
        $to = today()->toDateString();
        $history = DB::table('biz_journal_lines as l')->join('biz_journal_entries as e', 'e.id', '=', 'l.entry_id')->join('biz_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('l.employee_id', $e->id)->whereNull('e.reversed_at')
            ->groupBy('e.id', 'e.date', 'e.number', 'e.memo')->orderByDesc('e.date')->orderByDesc('e.id')->limit(50)
            ->selectRaw("e.id, e.date, e.number, e.memo,
                SUM(CASE WHEN a.system_key = 'salaries' THEN CAST(l.debit AS SIGNED) - CAST(l.credit AS SIGNED) ELSE 0 END) as salary,
                SUM(CASE WHEN a.system_key = 'staff_advance' THEN CAST(l.debit AS SIGNED) - CAST(l.credit AS SIGNED) ELSE 0 END) as advance,
                SUM(CASE WHEN a.system_key = 'commission' THEN CAST(l.debit AS SIGNED) - CAST(l.credit AS SIGNED) ELSE 0 END) as commission")
            ->get();

        return view('livewire.biz.employee-show', [
            'e' => $e,
            'advance' => $e->advance(),
            'paidMonth' => $e->paid('salaries', $from, $to),
            'sales' => $e->is_salesman ? $e->salesBetween($from, $to) : null,
            'commissionPaid' => $e->paid('commission', $from, $to),
            'bills' => $e->is_salesman ? Invoice::where('salesman_id', $e->id)->whereNull('voided_at')->latest('date')->latest('id')->limit(10)->get() : collect(),
            'plans' => $e->is_salesman ? Contract::with('customer')->where('salesman_id', $e->id)->latest('sale_date')->latest('id')->limit(10)->get() : collect(),
            'history' => $history,
            'impact' => $this->action ? $this->impactOf($this->rules(), fn () => $this->post(), self::MESSAGES) : null,
        ]);
    }
}
