<?php

namespace App\Services\Biz;

use App\Models\Biz\Customer;
use App\Models\Biz\Employee;
use App\Models\Biz\Invoice;
use App\Models\Biz\InvoiceLine;
use App\Models\Biz\InvoicePayment;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\Product;
use App\Models\Biz\StockUnit;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cash, bank and credit (udhaar) sales: retail or wholesale, many products on one invoice.
 * Each line takes its pieces out of stock (chosen ones, or the oldest first), so the cost
 * and profit are those of the very pieces sold.
 *
 * Accounting: the sale credits Sales with the invoice total; what was paid is debited to cash or bank,
 * the rest to Customer credit. The cost of the pieces moves from Stock to Cost of goods sold.
 */
class Sales
{
    /**
     * @param  array<int, array{product_id: int, quantity: int, unit_price: int, unit_ids?: array<int, int>}>  $lines
     */
    public function create(School $school, ?Customer $customer, ?string $walkIn, string $date, array $lines, int $discount = 0, int $paid = 0, string $method = 'cash', ?string $notes = null, ?int $salesmanId = null): Invoice
    {
        if ($salesmanId && ! Employee::withoutGlobalScopes()->where('school_id', $school->id)->whereKey($salesmanId)->exists()) {
            throw new InvalidArgumentException('That salesman belongs to another business.');
        }
        $lines = array_values(array_filter($lines, fn ($l) => (int) ($l['quantity'] ?? 0) > 0 && ! empty($l['product_id'])));
        if (! $lines) {
            throw new InvalidArgumentException('Add at least one product.');
        }
        if ($customer && $customer->school_id !== $school->id) {
            throw new InvalidArgumentException('That customer belongs to another business.');
        }

        $subtotal = array_sum(array_map(fn ($l) => (int) $l['quantity'] * (int) $l['unit_price'], $lines));
        $total = $subtotal - $discount;
        if ($discount < 0 || $total < 0) {
            throw new InvalidArgumentException('The discount cannot be more than the bill.');
        }
        if ($paid < 0 || $paid > $total) {
            throw new InvalidArgumentException('The amount paid cannot be more than the bill of '.rs($total).'.');
        }
        if ($paid < $total && ! $customer) {
            throw new InvalidArgumentException('Choose the customer to give credit (udhaar) to. A walk-in buyer pays in full.');
        }

        return DB::transaction(function () use ($school, $customer, $walkIn, $date, $lines, $discount, $paid, $method, $notes, $subtotal, $total, $salesmanId) {
            $invoice = Invoice::create([
                'school_id' => $school->id,
                'number' => Numbers::next($school, 'biz_invoices', 'INV-', 5),
                'date' => Carbon::parse($date)->toDateString(),
                'customer_id' => $customer?->id,
                'customer_name' => $customer ? null : ($walkIn ?: null),
                'salesman_id' => $salesmanId,
                'subtotal' => $subtotal, 'discount' => $discount, 'total' => $total, 'paid' => $paid,
                'notes' => $notes, 'created_by' => Auth::id(),
            ]);

            $stock = app(Stock::class);
            $cost = 0;
            foreach ($lines as $l) {
                $product = Product::withoutGlobalScopes()->with('type')->where('school_id', $school->id)->findOrFail($l['product_id']);
                $units = $stock->take($product, (int) $l['quantity'], array_map('intval', $l['unit_ids'] ?? []));
                $id = $product->identifier();
                $line = InvoiceLine::create([
                    'school_id' => $school->id, 'invoice_id' => $invoice->id, 'product_id' => $product->id,
                    'quantity' => (int) $l['quantity'], 'unit_price' => (int) $l['unit_price'],
                    'total' => (int) $l['quantity'] * (int) $l['unit_price'], 'cost' => (int) $units->sum('unit_cost'),
                    'details' => $id ? $units->map(fn ($u) => $u->details[$id['key']] ?? null)->filter()->implode(', ') ?: null : null,
                ]);
                StockUnit::withoutGlobalScopes()->whereIn('id', $units->pluck('id'))->update(['status' => 'sold', 'invoice_line_id' => $line->id, 'sold_on' => $invoice->date]);
                $cost += $line->cost;
            }
            $invoice->update(['cost' => $cost]);

            $entry = Ledger::for($school)->post('sale', $invoice->date, $invoice->number.' · '.$invoice->buyer(), [
                ['account' => Installments::cashAccount($method), 'debit' => $paid],
                ['account' => 'trade_receivable', 'debit' => $total - $paid, 'customer_id' => $customer?->id],
                ['account' => 'sales', 'credit' => $total, 'customer_id' => $customer?->id],
                ['account' => 'cogs', 'debit' => $cost],
                ['account' => 'inventory', 'credit' => $cost],
            ], $invoice);
            $invoice->update(['journal_entry_id' => $entry->id]);

            if ($paid > 0) {
                InvoicePayment::create(['school_id' => $school->id, 'invoice_id' => $invoice->id, 'date' => $invoice->date, 'amount' => $paid, 'method' => $method, 'journal_entry_id' => $entry->id, 'received_by' => Auth::id()]);
            }

            return $invoice->load('lines.product', 'payments');
        });
    }

    /** Money received later against a credit (udhaar) invoice. */
    public function receive(Invoice $invoice, int $amount, string $date, string $method = 'cash', ?string $reference = null): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $amount, $date, $method, $reference) {
            $invoice = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->firstOrFail()->load('school', 'customer');
            if ($invoice->voided_at) {
                throw new InvalidArgumentException('This invoice is cancelled.');
            }
            if ($amount < 1 || $amount > $invoice->balance()) {
                throw new InvalidArgumentException('Enter up to the '.rs($invoice->balance()).' still owed.');
            }

            $entry = Ledger::for($invoice->school)->post('collection', $date, 'Payment for '.$invoice->number.' · '.$invoice->buyer(), [
                ['account' => Installments::cashAccount($method), 'debit' => $amount],
                ['account' => 'trade_receivable', 'credit' => $amount, 'customer_id' => $invoice->customer_id],
            ], $invoice);
            $invoice->increment('paid', $amount);

            return InvoicePayment::create(['school_id' => $invoice->school_id, 'invoice_id' => $invoice->id, 'date' => Carbon::parse($date)->toDateString(), 'amount' => $amount, 'method' => $method, 'reference' => $reference, 'journal_entry_id' => $entry->id, 'received_by' => Auth::id()]);
        });
    }

    /** Cancels a sale: its entries are reversed and the pieces go back into stock. */
    public function void(Invoice $invoice, string $reason): void
    {
        DB::transaction(function () use ($invoice, $reason) {
            $invoice = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->firstOrFail()->load('school', 'lines', 'payments');
            if ($invoice->voided_at) {
                throw new InvalidArgumentException('This invoice is already cancelled.');
            }

            $ledger = Ledger::for($invoice->school);
            $entryIds = collect([$invoice->journal_entry_id])->merge($invoice->payments->pluck('journal_entry_id'))->filter()->unique();
            foreach (JournalEntry::withoutGlobalScopes()->with('lines')->whereIn('id', $entryIds)->whereNull('reversed_at')->get() as $entry) {
                $ledger->reverse($entry, 'Invoice '.$invoice->number.' cancelled: '.$reason);
            }

            StockUnit::withoutGlobalScopes()->whereIn('invoice_line_id', $invoice->lines->pluck('id'))->update(['status' => 'in_stock', 'invoice_line_id' => null, 'sold_on' => null]);
            $invoice->update(['voided_at' => now(), 'void_reason' => $reason]);
        });
    }
}
