<?php

namespace App\Services\Biz;

use App\Models\Biz\Account;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\JournalLine;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Double-entry book-keeping for one business. Every entry balances (debits = credits),
 * entries are never edited or deleted, and a mistake is undone by a reversing entry.
 */
class Ledger
{
    public function __construct(private School $school) {}

    public static function for(School $school): self
    {
        return new self($school);
    }

    /** The account that sales and collections use for a job, such as "cash" or "receivable". */
    public function account(string $key): Account
    {
        return Account::withoutGlobalScopes()->where('school_id', $this->school->id)->where('system_key', $key)->firstOrFail();
    }

    /**
     * @param  array<int, array{account: Account|string|int, debit?: int, credit?: int, customer_id?: ?int, vendor_id?: ?int, employee_id?: ?int, memo?: ?string}>  $lines
     */
    public function post(string $kind, Carbon|string $date, ?string $memo, array $lines, ?Model $source = null): JournalEntry
    {
        abort_unless(isset(JournalEntry::KINDS[$kind]), 422);

        $rows = collect($lines)->map(function ($line) {
            // An account, its id, its code ("5300") or its system key ("cash").
            $account = match (true) {
                $line['account'] instanceof Account => $line['account'],
                is_int($line['account']) => Account::withoutGlobalScopes()->where('school_id', $this->school->id)->findOrFail($line['account']),
                ctype_digit($line['account']) => Account::withoutGlobalScopes()->where('school_id', $this->school->id)->where('code', $line['account'])->firstOrFail(),
                default => $this->account($line['account']),
            };
            if ($account->school_id !== $this->school->id) {
                throw new InvalidArgumentException('That account belongs to another business.');
            }
            foreach (['vendor_id' => 'biz_vendors', 'employee_id' => 'biz_employees'] as $column => $table) {
                if (! empty($line[$column]) && ! DB::table($table)->where('id', $line[$column])->where('school_id', $this->school->id)->exists()) {
                    throw new InvalidArgumentException('That '.($column === 'vendor_id' ? 'vendor' : 'employee').' belongs to another business.');
                }
            }

            return ['account' => $account, 'debit' => (int) ($line['debit'] ?? 0), 'credit' => (int) ($line['credit'] ?? 0), 'customer_id' => $line['customer_id'] ?? null, 'vendor_id' => $line['vendor_id'] ?? null, 'employee_id' => $line['employee_id'] ?? null, 'memo' => $line['memo'] ?? null];
        })->filter(fn ($l) => $l['debit'] > 0 || $l['credit'] > 0)->values();

        if ($rows->contains(fn ($l) => $l['debit'] < 0 || $l['credit'] < 0 || ($l['debit'] > 0 && $l['credit'] > 0))) {
            throw new InvalidArgumentException('Each line is either a debit or a credit.');
        }
        $debits = $rows->sum('debit');
        if ($rows->count() < 2 || $debits === 0 || $debits !== $rows->sum('credit')) {
            throw new InvalidArgumentException('Debits and credits must be equal and more than zero.');
        }

        return DB::transaction(function () use ($kind, $date, $memo, $rows, $source) {
            $entry = JournalEntry::create([
                'school_id' => $this->school->id,
                'number' => Numbers::next($this->school, 'biz_journal_entries', JournalEntry::KINDS[$kind][1].'-', 5, ['kind' => $kind]),
                'date' => Carbon::parse($date)->toDateString(),
                'kind' => $kind,
                'memo' => $memo,
                'source_type' => $source ? $source::class : null,
                'source_id' => $source?->getKey(),
                'created_by' => Auth::id(),
            ]);

            foreach ($rows as $row) {
                JournalLine::create([
                    'school_id' => $this->school->id,
                    'entry_id' => $entry->id,
                    'account_id' => $row['account']->id,
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                    'customer_id' => $row['customer_id'],
                    'vendor_id' => $row['vendor_id'],
                    'employee_id' => $row['employee_id'],
                    'memo' => $row['memo'],
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    /** Undoes an entry with its mirror image, dated today unless given. */
    public function reverse(JournalEntry $entry, string $reason, Carbon|string|null $date = null): JournalEntry
    {
        if ($entry->school_id !== $this->school->id || $entry->reversed_at || $entry->kind === 'reversal') {
            throw new InvalidArgumentException('This entry cannot be reversed.');
        }

        return DB::transaction(function () use ($entry, $reason, $date) {
            $reversal = $this->post('reversal', $date ?? today(), 'Reversal of '.$entry->number.': '.$reason, $entry->lines->map(fn ($l) => [
                'account' => $l->account_id, 'debit' => $l->credit, 'credit' => $l->debit, 'customer_id' => $l->customer_id, 'vendor_id' => $l->vendor_id, 'employee_id' => $l->employee_id, 'memo' => $l->memo,
            ])->all(), $entry->source);
            $reversal->update(['reversal_of' => $entry->id]);
            $entry->update(['reversed_at' => now()]);

            return $reversal;
        });
    }

    /**
     * Debit and credit totals per account for entries in the date range.
     *
     * @return Collection<int, object{account_id: int, debit: int, credit: int}>
     */
    public function totals(?string $from = null, ?string $to = null): Collection
    {
        return DB::table('biz_journal_lines as l')
            ->join('biz_journal_entries as e', 'e.id', '=', 'l.entry_id')
            ->where('l.school_id', $this->school->id)
            ->when($from, fn ($q) => $q->where('e.date', '>=', $from))
            ->when($to, fn ($q) => $q->where('e.date', '<=', $to))
            ->groupBy('l.account_id')
            ->selectRaw('l.account_id, SUM(l.debit) as debit, SUM(l.credit) as credit')
            ->get()->keyBy('account_id')
            ->map(fn ($r) => (object) ['account_id' => (int) $r->account_id, 'debit' => (int) $r->debit, 'credit' => (int) $r->credit]);
    }

    /** @return Collection<int, Account> every account, with balance (in its own direction), debit and credit set */
    public function accounts(?string $from = null, ?string $to = null): Collection
    {
        $totals = $this->totals($from, $to);

        return Account::withoutGlobalScopes()->where('school_id', $this->school->id)->orderBy('code')->get()->each(function (Account $a) use ($totals) {
            $t = $totals[$a->id] ?? (object) ['debit' => 0, 'credit' => 0];
            $a->total_debit = $t->debit;
            $a->total_credit = $t->credit;
            $a->balance = $a->isDebitNature() ? $t->debit - $t->credit : $t->credit - $t->debit;
        });
    }

    public function balanceOf(string $key, ?string $to = null): int
    {
        $account = $this->account($key);
        $t = $this->totals(null, $to)[$account->id] ?? (object) ['debit' => 0, 'credit' => 0];

        return $account->isDebitNature() ? $t->debit - $t->credit : $t->credit - $t->debit;
    }

    /** @return array{income: Collection, expense: Collection, total_income: int, total_expense: int, profit: int} */
    public function profitLoss(string $from, string $to): array
    {
        $accounts = $this->accounts($from, $to);
        $income = $accounts->where('type', 'income')->filter(fn ($a) => $a->balance !== 0)->values();
        $expense = $accounts->where('type', 'expense')->filter(fn ($a) => $a->balance !== 0)->values();

        return [
            'income' => $income,
            'expense' => $expense,
            'total_income' => (int) $income->sum('balance'),
            'total_expense' => (int) $expense->sum('balance'),
            'profit' => (int) ($income->sum('balance') - $expense->sum('balance')),
        ];
    }

    /** Assets = liabilities + equity, with the profit not yet closed shown inside equity. */
    public function balanceSheet(string $asOf): array
    {
        $accounts = $this->accounts(null, $asOf);
        $earnings = (int) ($accounts->where('type', 'income')->sum('balance') - $accounts->where('type', 'expense')->sum('balance'));
        $pick = fn ($type) => $accounts->where('type', $type)->filter(fn ($a) => $a->balance !== 0)->values();

        $assets = $pick('asset');
        $liabilities = $pick('liability');
        $equity = $pick('equity');

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'earnings' => $earnings,
            'total_assets' => (int) $assets->sum('balance'),
            'total_liabilities' => (int) $liabilities->sum('balance'),
            'total_equity' => (int) $equity->sum('balance') + $earnings,
        ];
    }

    /** Lines of one account in a range, with the balance carried in from before and a running balance. */
    public function statement(Account $account, string $from, string $to): array
    {
        $before = DB::table('biz_journal_lines as l')->join('biz_journal_entries as e', 'e.id', '=', 'l.entry_id')
            ->where('l.account_id', $account->id)->where('e.date', '<', $from)
            ->selectRaw('COALESCE(SUM(l.debit),0) as d, COALESCE(SUM(l.credit),0) as c')->first();
        $sign = $account->isDebitNature() ? 1 : -1;
        $running = $opening = $sign * ((int) $before->d - (int) $before->c);

        $lines = JournalLine::withoutGlobalScopes()->with(['entry', 'customer'])
            ->select('biz_journal_lines.*')
            ->join('biz_journal_entries as e', 'e.id', '=', 'biz_journal_lines.entry_id')
            ->where('biz_journal_lines.account_id', $account->id)
            ->whereBetween('e.date', [$from, $to])
            ->orderBy('e.date')->orderBy('e.id')->orderBy('biz_journal_lines.id')
            ->get()
            ->each(function ($line) use (&$running, $sign) {
                $running += $sign * ($line->debit - $line->credit);
                $line->running = $running;
            });

        return ['opening' => $opening, 'lines' => $lines, 'closing' => $running];
    }
}
