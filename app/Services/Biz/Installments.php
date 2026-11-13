<?php

namespace App\Services\Biz;

use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use App\Models\Biz\Employee;
use App\Models\Biz\Product;
use App\Models\Biz\Receipt;
use App\Models\Biz\ScheduleItem;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Installment sales, the usual way: markup on the amount left after the down payment,
 * equal installments with the last one taking the rounding, payments applied to the oldest
 * installment first, and markup counted as income only as the money comes in.
 *
 * Accounting: the sale debits Installment receivable with the full price and credits Sales
 * (cash price) and Unearned markup. Each collection credits the receivable and moves its share
 * of the markup from Unearned markup to Markup income.
 */
class Installments
{
    public const DEFAULTS = [
        'markup_percent' => 20, 'installments' => 12, 'frequency' => 'monthly', 'late_fee' => 0, 'grace_days' => 3, 'rounding' => 10,
        'receipt_note' => 'Please keep this receipt. Pay every installment on time.',
        'terms' => "1. The item stays the property of the seller until every installment is paid.\n2. Installments are paid on or before the due date. A late fee applies after the grace days.\n3. If installments are not paid, the seller may take the item back, and the guarantors are responsible for the amount due.\n4. The buyer will not sell, pawn or hand over the item until it is fully paid.",
    ];

    public static function settings(School $school): array
    {
        return array_merge(self::DEFAULTS, array_filter($school->business_settings ?? [], fn ($v) => $v !== null && $v !== ''));
    }

    /**
     * Works out a plan without saving it.
     *
     * @return array{markup: int, total: int, financed: int, installment: int, rows: array<int, array{number: int, due_on: Carbon, amount: int}>}
     */
    public static function plan(int $cashPrice, float $markupPercent, ?int $markupAmount, int $down, int $count, string $frequency, Carbon|string $firstDue, int $rounding = 10): array
    {
        if ($cashPrice <= 0 || $count < 1 || $down < 0 || $down > $cashPrice) {
            throw new InvalidArgumentException('Check the price, the down payment and the number of installments.');
        }

        $markup = $markupAmount ?? (int) round(($cashPrice - $down) * $markupPercent / 100);
        $total = $cashPrice + $markup;
        $financed = $total - $down;
        $rounding = max(1, $rounding);

        $installment = (int) (ceil($financed / $count / $rounding) * $rounding);
        if ($count > 1 && $installment * ($count - 1) >= $financed) {
            $installment = (int) ceil($financed / $count);
        }
        $last = $financed - $installment * ($count - 1);

        $first = Carbon::parse($firstDue)->startOfDay();
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'number' => $i + 1,
                'due_on' => match ($frequency) {
                    'weekly' => $first->copy()->addWeeks($i),
                    'fortnightly' => $first->copy()->addDays(14 * $i),
                    default => $first->copy()->addMonthsNoOverflow($i),
                },
                'amount' => $i === $count - 1 ? $last : $installment,
            ];
        }

        return ['markup' => $markup, 'total' => $total, 'financed' => $financed, 'installment' => $installment, 'rows' => $rows];
    }

    /**
     * Saves a sale, its schedule, the accounting entry and the down payment receipt.
     *
     * @param  array<string, mixed>  $data  item, item_details, sale_date, cash_price, cost_price, markup_percent, markup (optional), down_payment,
     *                                      installments, frequency, first_due_on, late_fee, grace_days, rounding, notes, method
     * @param  array<int, array<string, ?string>>  $guarantors
     */
    public function sell(School $school, Customer $customer, array $data, array $guarantors = []): Contract
    {
        $salesman = empty($data['salesman_id']) ? null : Employee::withoutGlobalScopes()->where('school_id', $school->id)->find($data['salesman_id']);
        if (! empty($data['salesman_id']) && ! $salesman) {
            throw new InvalidArgumentException('That salesman belongs to another business.');
        }
        $plan = self::plan((int) $data['cash_price'], (float) ($data['markup_percent'] ?? 0), isset($data['markup']) && $data['markup'] !== '' ? (int) $data['markup'] : null,
            (int) ($data['down_payment'] ?? 0), (int) $data['installments'], $data['frequency'], $data['first_due_on'], (int) ($data['rounding'] ?? 10));

        return DB::transaction(function () use ($school, $customer, $data, $guarantors, $plan, $salesman) {
            // From the product list: the piece sold (chosen, or the oldest in stock) sets the cost.
            $product = ! empty($data['product_id']) ? Product::withoutGlobalScopes()->where('school_id', $school->id)->findOrFail($data['product_id']) : null;
            $unit = $product ? app(Stock::class)->pick($product, ! empty($data['stock_unit_id']) ? (int) $data['stock_unit_id'] : null) : null;
            $details = array_filter(array_merge($unit?->details ?? [], $data['details'] ?? []), fn ($v) => $v !== null && $v !== '');
            if ($product && ($id = $product->identifier()) && isset($details[$id['key']])) {
                Stock::assertUnique($school, $id, [$details[$id['key']]], array_filter([$unit?->id]));
            }
            $cost = $unit ? $unit->unit_cost : (($data['cost_price'] ?? '') !== '' ? (int) $data['cost_price'] : null);
            $summary = $product ? collect($product->fields())->filter(fn ($f) => isset($details[$f['key']]))->map(fn ($f) => $f['label'].': '.$details[$f['key']])->implode("\n") : '';

            $contract = Contract::create([
                'school_id' => $school->id,
                'number' => Numbers::next($school, 'biz_contracts', 'IS-', 4),
                'customer_id' => $customer->id,
                'salesman_id' => $salesman?->id,
                'product_id' => $product?->id,
                'stock_unit_id' => $unit?->id,
                'item' => ($data['item'] ?? '') !== '' ? $data['item'] : $product?->label(),
                'item_details' => trim($summary."\n".($data['item_details'] ?? '')) ?: null,
                'details' => $details ?: null,
                'sale_date' => $data['sale_date'],
                'cash_price' => (int) $data['cash_price'],
                'cost_price' => $cost,
                'markup_percent' => (float) ($data['markup_percent'] ?? 0),
                'markup' => $plan['markup'],
                'total_price' => $plan['total'],
                'down_payment' => (int) ($data['down_payment'] ?? 0),
                'financed' => $plan['financed'],
                'installments' => (int) $data['installments'],
                'frequency' => $data['frequency'],
                'installment_amount' => $plan['installment'],
                'first_due_on' => $data['first_due_on'],
                'late_fee' => (int) ($data['late_fee'] ?? 0),
                'grace_days' => (int) ($data['grace_days'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $unit?->update(['status' => 'sold', 'contract_id' => $contract->id, 'sold_on' => $contract->sale_date, 'details' => $details ?: null]);

            foreach ($plan['rows'] as $row) {
                ScheduleItem::create(['school_id' => $school->id, 'contract_id' => $contract->id] + $row);
            }
            foreach (array_filter($guarantors, fn ($g) => trim((string) ($g['name'] ?? '')) !== '') as $g) {
                $contract->guarantors()->create(['school_id' => $school->id] + array_intersect_key($g, array_flip(['name', 'father_name', 'cnic', 'phone', 'address', 'relation'])));
            }

            $ledger = Ledger::for($school);
            $lines = [
                ['account' => 'receivable', 'debit' => $plan['total'], 'customer_id' => $customer->id],
                ['account' => 'sales', 'credit' => $contract->cash_price, 'customer_id' => $customer->id],
                ['account' => 'deferred_markup', 'credit' => $plan['markup'], 'customer_id' => $customer->id],
            ];
            if ($contract->cost_price) {
                $lines[] = ['account' => 'cogs', 'debit' => $contract->cost_price, 'memo' => 'Cost of '.$contract->item];
                $lines[] = ['account' => 'inventory', 'credit' => $contract->cost_price, 'memo' => 'Cost of '.$contract->item];
            }
            $ledger->post('sale', $contract->sale_date, $contract->number.' · '.$customer->name.' · '.$contract->item, $lines, $contract);

            if ($contract->down_payment > 0) {
                $receipt = Receipt::create([
                    'school_id' => $school->id,
                    'number' => Numbers::next($school, 'biz_receipts', 'R-', 6),
                    'contract_id' => $contract->id,
                    'date' => $contract->sale_date,
                    'is_down_payment' => true,
                    'amount' => $contract->down_payment,
                    'method' => $data['method'] ?? 'cash',
                    'received_by' => Auth::id(),
                    'note' => 'Down payment',
                ]);
                $entry = $ledger->post('collection', $contract->sale_date, 'Down payment '.$contract->number.' · '.$customer->name, [
                    ['account' => self::cashAccount($receipt->method), 'debit' => $contract->down_payment],
                    ['account' => 'receivable', 'credit' => $contract->down_payment, 'customer_id' => $customer->id],
                ], $receipt);
                $receipt->update(['journal_entry_id' => $entry->id]);
            }

            return $contract->fresh(['schedule', 'customer', 'guarantors', 'receipts']);
        });
    }

    public static function cashAccount(string $method): string
    {
        return $method === 'cash' ? 'cash' : 'bank';
    }

    /** The late fee the counter should suggest: one fee for each installment past its grace days. */
    public static function suggestedLateFee(Contract $contract, ?Carbon $on = null): int
    {
        return $contract->late_fee * $contract->lateInstallments($on);
    }

    /** Takes a payment: oldest installments first, then the accounting entry with the markup share. */
    public function collect(Contract $contract, int $amount, int $lateFee = 0, int $discount = 0, Carbon|string|null $date = null, string $method = 'cash', ?string $reference = null, ?string $note = null): Receipt
    {
        return DB::transaction(function () use ($contract, $amount, $lateFee, $discount, $date, $method, $reference, $note) {
            $contract = Contract::withoutGlobalScopes()->whereKey($contract->id)->lockForUpdate()->firstOrFail()->load('schedule', 'customer', 'school');
            $date = Carbon::parse($date ?? today());
            $outstanding = $contract->outstanding();

            if (! $contract->isOpen()) {
                throw new InvalidArgumentException('This plan is closed.');
            }
            if ($amount < 0 || $lateFee < 0 || $discount < 0 || $amount + $lateFee <= 0) {
                throw new InvalidArgumentException('Enter the amount received.');
            }
            if ($amount + $discount > $outstanding) {
                throw new InvalidArgumentException('That is more than the Rs '.number_format($outstanding).' still owed.');
            }

            $applied = $amount + $discount;
            $this->allocate($contract, $applied, $date);

            // Markup is earned in step with the money paid, so it is exactly used up by the last rupee.
            $paidAfter = $contract->paid();
            $earned = $paidAfter >= $contract->financed ? $contract->markup : intdiv($paidAfter * $contract->markup, max(1, $contract->financed));
            $markupShare = max(0, $earned - $contract->markup_recognised);

            $school = $contract->school;
            $receipt = Receipt::create([
                'school_id' => $school->id,
                'number' => Numbers::next($school, 'biz_receipts', 'R-', 6),
                'contract_id' => $contract->id,
                'date' => $date->toDateString(),
                'amount' => $amount,
                'late_fee' => $lateFee,
                'discount' => $discount,
                'method' => $method,
                'reference' => $reference,
                'note' => $note,
                'received_by' => Auth::id(),
            ]);

            $customerId = $contract->customer_id;
            $entry = Ledger::for($school)->post('collection', $date, $receipt->number.' · '.$contract->number.' · '.$contract->customer->name, [
                ['account' => self::cashAccount($method), 'debit' => $amount + $lateFee],
                ['account' => 'discount', 'debit' => $discount, 'customer_id' => $customerId],
                ['account' => 'receivable', 'credit' => $applied, 'customer_id' => $customerId],
                ['account' => 'late_fee_income', 'credit' => $lateFee, 'customer_id' => $customerId],
                ['account' => 'deferred_markup', 'debit' => $markupShare, 'memo' => 'Markup earned'],
                ['account' => 'markup_income', 'credit' => $markupShare, 'customer_id' => $customerId, 'memo' => 'Markup earned'],
            ], $receipt);
            $receipt->update(['journal_entry_id' => $entry->id]);

            $contract->markup_recognised += $markupShare;
            if ($contract->outstanding() === 0) {
                $contract->status = 'completed';
                $contract->closed_at = now();
            }
            $contract->save();

            return $receipt;
        });
    }

    /** Cancels a receipt: its entry is reversed and the installments are worked out again from the receipts left. */
    public function void(Receipt $receipt, string $reason): void
    {
        if ($receipt->voided_at || $receipt->is_down_payment) {
            throw new InvalidArgumentException($receipt->is_down_payment ? 'A down payment is part of the sale and cannot be cancelled alone.' : 'This receipt is already cancelled.');
        }

        DB::transaction(function () use ($receipt, $reason) {
            $contract = Contract::withoutGlobalScopes()->whereKey($receipt->contract_id)->lockForUpdate()->firstOrFail()->load('school');
            if ($contract->status === 'written_off') {
                throw new InvalidArgumentException('This plan was written off. Its receipts can no longer be cancelled.');
            }
            $entry = $receipt->entry()->withoutGlobalScopes()->with('lines.account')->firstOrFail();
            $markupShare = (int) $entry->lines->filter(fn ($l) => $l->account->system_key === 'deferred_markup')->sum('debit');

            Ledger::for($contract->school)->reverse($entry, $reason);
            $receipt->update(['voided_at' => now(), 'void_reason' => $reason]);

            $contract->markup_recognised = max(0, $contract->markup_recognised - $markupShare);
            $contract->status = 'active';
            $contract->closed_at = null;
            $contract->save();
            $this->reallocate($contract);
        });
    }

    /** Writes off what is left as a bad debt. The unearned markup on it is simply cancelled. */
    public function writeOff(Contract $contract, string $reason, Carbon|string|null $date = null): void
    {
        DB::transaction(function () use ($contract, $reason, $date) {
            $contract = Contract::withoutGlobalScopes()->whereKey($contract->id)->lockForUpdate()->firstOrFail()->load('schedule', 'customer', 'school');
            if (! $contract->isOpen() || $contract->outstanding() === 0) {
                throw new InvalidArgumentException('Nothing is left to write off.');
            }
            $owed = $contract->outstanding();
            $unearned = $contract->markup - $contract->markup_recognised;

            Ledger::for($contract->school)->post('journal', $date ?? today(), 'Written off '.$contract->number.' · '.$contract->customer->name.': '.$reason, [
                ['account' => 'deferred_markup', 'debit' => $unearned],
                ['account' => 'bad_debt', 'debit' => $owed - $unearned, 'customer_id' => $contract->customer_id],
                ['account' => 'receivable', 'credit' => $owed, 'customer_id' => $contract->customer_id],
            ], $contract);

            $contract->update(['status' => 'written_off', 'closed_at' => now(), 'notes' => trim(($contract->notes ? $contract->notes."\n" : '').'Written off: '.$reason)]);
        });
    }

    private function allocate(Contract $contract, int $money, Carbon $date): void
    {
        foreach ($contract->schedule as $item) {
            if ($money <= 0) {
                break;
            }
            $take = min($money, $item->amount - $item->paid);
            if ($take > 0) {
                $item->paid += $take;
                $item->paid_on = $item->paid >= $item->amount ? $date : $item->paid_on;
                $item->save();
                $money -= $take;
            }
        }
    }

    private function reallocate(Contract $contract): void
    {
        $contract->load('schedule');
        foreach ($contract->schedule as $item) {
            $item->update(['paid' => 0, 'paid_on' => null]);
        }
        $receipts = Receipt::withoutGlobalScopes()->where('contract_id', $contract->id)->whereNull('voided_at')->where('is_down_payment', false)->orderBy('date')->orderBy('id')->get();
        foreach ($receipts as $receipt) {
            $this->allocate($contract, $receipt->amount + $receipt->discount, $receipt->date);
        }
    }
}
