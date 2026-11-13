<?php

namespace App\Services\Biz;

use App\Models\Biz\Customer;
use App\Models\Biz\Employee;
use App\Models\Biz\JournalLine;
use App\Models\Biz\Product;
use App\Models\Biz\Vendor;
use App\Models\School;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Shows what an entry will change before it is saved. The real saving code runs inside a
 * transaction that is always rolled back, so the preview is exactly what saving would do:
 * the same books entry, the same stock pieces, the same customer balances.
 */
class Impact
{
    /** Plain words for accounts the system itself uses. [goes up, goes down] */
    private const WORDS = [
        'cash' => ['Cash in hand goes up', 'Cash in hand goes down'],
        'bank' => ['Bank balance goes up', 'Bank balance goes down'],
        'receivable' => ['Installment customers owe you more', 'Installment customers owe you less'],
        'trade_receivable' => ['Customers owe you more on credit (udhaar)', 'Customers owe you less on credit (udhaar)'],
        'inventory' => ['Stock value goes up', 'Stock value goes down'],
        'payable' => ['You owe suppliers more', 'You owe suppliers less'],
        'deferred_markup' => ['Markup to earn later goes up (it becomes profit as installments are paid)', 'Markup to earn later goes down'],
        'capital' => ["Owner's capital goes up", "Owner's capital goes down"],
        'drawings' => ['Money the owner took out goes up', 'Money the owner took out goes down'],
        'sales' => ['Sales income', 'Sales income goes down'],
        'markup_income' => ['Markup earned (profit)', 'Markup earned goes down'],
        'late_fee_income' => ['Late fee earned', 'Late fee earned goes down'],
        'cogs' => ['Cost of the goods sold', 'Cost of goods sold goes down'],
        'discount' => ['Discount given', 'Discount given goes down'],
        'bad_debt' => ['Loss: money that will not be recovered', 'Bad debt loss goes down'],
    ];

    /**
     * @return array{ok: bool, error: ?string, effects: array<int, array{tone: string, text: string, amount: int}>, lines: array<int, array{code: string, account: string, debit: int, credit: int, customer: ?string}>, profit: ?int, stock: array<int, array{product: string, pieces: int, cost: int}>}
     */
    public static function preview(School $school, Closure $action): array
    {
        $lastLine = (int) JournalLine::withoutGlobalScopes()->where('school_id', $school->id)->max('id');
        $before = self::stock($school);

        DB::beginTransaction();
        try {
            $action();
            $lines = JournalLine::withoutGlobalScopes()->with('account')->where('school_id', $school->id)->where('id', '>', $lastLine)->orderBy('id')->get();
            $after = self::stock($school);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return self::failed($e->getMessage());
        } catch (ValidationException $e) {
            return self::failed(collect($e->errors())->flatten()->first() ?? 'Some details are missing.');
        } catch (HttpException $e) {
            return self::failed($e->getMessage() ?: 'This cannot be saved yet.');
        } catch (Throwable $e) {
            report($e);

            return self::failed('The effect could not be worked out. Saving will still check everything.');
        } finally {
            DB::rollBack();
        }

        return self::describe($school, $lines, $before, $after);
    }

    /** Pieces in stock and their cost, per product. @return array<int, array{0: int, 1: int}> */
    private static function stock(School $school): array
    {
        return DB::table('biz_stock_units')->where('school_id', $school->id)->where('status', 'in_stock')
            ->selectRaw('product_id, COUNT(*) as n, COALESCE(SUM(unit_cost), 0) as cost')->groupBy('product_id')->get()
            ->mapWithKeys(fn ($r) => [(int) $r->product_id => [(int) $r->n, (int) $r->cost]])->all();
    }

    private static function failed(string $message): array
    {
        return ['ok' => false, 'error' => $message, 'effects' => [], 'lines' => [], 'profit' => null, 'stock' => []];
    }

    private static function describe(School $school, $lines, array $before, array $after): array
    {
        $customers = Customer::withoutGlobalScopes()->where('school_id', $school->id)
            ->whereIn('id', $lines->pluck('customer_id')->filter()->unique())->pluck('name', 'id');

        $effects = [];
        $profit = 0;
        foreach ($lines->groupBy('account_id') as $group) {
            $account = $group->first()->account;
            // Assets and expenses grow with a debit; the rest grow with a credit.
            $net = (int) $group->sum('debit') - (int) $group->sum('credit');
            $growth = in_array($account->type, ['asset', 'expense'], true) ? $net : -$net;
            if ($growth === 0) {
                continue;
            }
            if ($account->type === 'income') {
                $profit += $growth;
            } elseif ($account->type === 'expense') {
                $profit -= $growth;
            }

            [$up, $down] = self::WORDS[$account->system_key] ?? [$account->name.' goes up', $account->name.' goes down'];
            $tone = match (true) {
                $account->type === 'income' => $growth > 0 ? 'good' : 'bad',
                $account->type === 'expense' => $growth > 0 ? 'bad' : 'good',
                default => 'info',
            };
            $effects[] = ['tone' => $tone, 'text' => $growth > 0 ? $up : $down, 'amount' => abs($growth)];
        }

        // Who owes what afterwards: only what customers owe (receivables), not the sale or markup lines that also name them.
        $owed = $lines->filter(fn ($l) => $l->customer_id && $l->account->type === 'asset');
        foreach ($owed->groupBy('customer_id') as $id => $group) {
            $net = (int) $group->sum('debit') - (int) $group->sum('credit');
            if ($net !== 0 && isset($customers[$id])) {
                $effects[] = ['tone' => 'info', 'text' => $customers[$id].($net > 0 ? ' will owe you more' : ' will owe you less'), 'amount' => abs($net)];
            }
        }

        // What is owed to each vendor, and what each employee owes back (advances).
        $vendors = Vendor::withoutGlobalScopes()->whereIn('id', $lines->pluck('vendor_id')->filter()->unique())->pluck('name', 'id');
        foreach ($lines->filter(fn ($l) => $l->vendor_id && $l->account->system_key === 'payable')->groupBy('vendor_id') as $id => $group) {
            $net = (int) $group->sum('credit') - (int) $group->sum('debit');
            if ($net !== 0 && isset($vendors[$id])) {
                $effects[] = ['tone' => 'info', 'text' => 'You will owe '.$vendors[$id].($net > 0 ? ' more' : ' less'), 'amount' => abs($net)];
            }
        }
        $employees = Employee::withoutGlobalScopes()->whereIn('id', $lines->pluck('employee_id')->filter()->unique())->pluck('name', 'id');
        foreach ($lines->filter(fn ($l) => $l->employee_id && $l->account->system_key === 'staff_advance')->groupBy('employee_id') as $id => $group) {
            $net = (int) $group->sum('debit') - (int) $group->sum('credit');
            if ($net !== 0 && isset($employees[$id])) {
                $effects[] = ['tone' => 'info', 'text' => $employees[$id].($net > 0 ? ' will owe you more (advance)' : ' will owe you less (advance)'), 'amount' => abs($net)];
            }
        }

        $products = Product::withoutGlobalScopes()->whereIn('id', array_keys($before + $after))->get()->keyBy('id');
        $stock = [];
        foreach (array_keys($before + $after) as $id) {
            $pieces = ($after[$id][0] ?? 0) - ($before[$id][0] ?? 0);
            $cost = ($after[$id][1] ?? 0) - ($before[$id][1] ?? 0);
            if ($pieces !== 0 || $cost !== 0) {
                $stock[] = ['product' => $products[$id]?->label() ?? 'Product', 'pieces' => $pieces, 'cost' => $cost];
            }
        }

        return [
            'ok' => true,
            'error' => null,
            'effects' => $effects,
            'lines' => $lines->map(fn ($l) => [
                'code' => (string) $l->account->code, 'account' => $l->account->name,
                'debit' => (int) $l->debit, 'credit' => (int) $l->credit, 'customer' => $customers[$l->customer_id] ?? $vendors[$l->vendor_id] ?? $employees[$l->employee_id] ?? null,
            ])->all(),
            'profit' => $lines->contains(fn ($l) => in_array($l->account->type, ['income', 'expense'], true)) ? $profit : null,
            'stock' => $stock,
        ];
    }
}
