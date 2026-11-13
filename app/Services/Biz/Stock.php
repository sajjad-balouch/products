<?php

namespace App\Services\Biz;

use App\Models\Biz\Product;
use App\Models\Biz\Purchase;
use App\Models\Biz\StockUnit;
use App\Models\Biz\Vendor;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Stock kept piece by piece. A purchase adds pieces at their cost and debits Stock (inventory);
 * a sale takes one piece out, and its own cost becomes the cost of goods sold.
 * When no piece is picked, the oldest one in stock goes first (FIFO).
 */
class Stock
{
    /**
     * @param  array{date: string, quantity: int, unit_cost: int, supplier?: ?string, vendor_id?: ?int, paid_by?: string, paid_now?: ?int, reference?: ?string}  $data
     *   paid_now: with cash or bank, how much was paid now; the rest is owed to the vendor
     * @param  array<int, array<string, ?string>>  $units  details of each piece, in order; fewer than the quantity is fine
     */
    public function purchase(School $school, Product $product, array $data, array $units = []): Purchase
    {
        $quantity = (int) $data['quantity'];
        $cost = (int) $data['unit_cost'];
        if ($quantity < 1 || $quantity > self::MAX_PIECES || $cost < 0) {
            throw new InvalidArgumentException('Enter between 1 and '.number_format(self::MAX_PIECES).' pieces and their cost.');
        }
        if ($product->school_id !== $school->id) {
            throw new InvalidArgumentException('That product belongs to another business.');
        }
        $vendor = empty($data['vendor_id']) ? null : Vendor::withoutGlobalScopes()->where('school_id', $school->id)->find($data['vendor_id']);
        if (! empty($data['vendor_id']) && ! $vendor) {
            throw new InvalidArgumentException('That vendor belongs to another business.');
        }
        // Part paid now in cash or bank, the rest owed to the vendor.
        $total = $quantity * $cost;
        $paidBy = $data['paid_by'] ?? 'cash';
        $paidNow = match ($paidBy) {
            'cash', 'bank' => isset($data['paid_now']) && $data['paid_now'] !== '' && $data['paid_now'] !== null ? (int) $data['paid_now'] : $total,
            default => 0,
        };
        if ($paidNow < 0 || $paidNow > $total) {
            throw new InvalidArgumentException('Paid now must be between 0 and the total, '.rs($total).'.');
        }
        if ($paidNow < $total && in_array($paidBy, ['cash', 'bank'], true) && ! $vendor) {
            throw new InvalidArgumentException('Choose the vendor you owe the rest to ('.rs($total - $paidNow).').');
        }
        if ($id = $product->identifier()) {
            self::assertUnique($school, $id, array_column($units, $id['key']));
        }

        return DB::transaction(function () use ($school, $product, $data, $units, $quantity, $cost, $vendor, $total, $paidNow) {
            $purchase = Purchase::create([
                'school_id' => $school->id,
                'product_id' => $product->id,
                'number' => Numbers::next($school, 'biz_purchases', 'PO-', 5),
                'date' => Carbon::parse($data['date'])->toDateString(),
                'supplier' => $vendor?->name ?? ($data['supplier'] ?? null),
                'vendor_id' => $vendor?->id,
                'quantity' => $quantity,
                'unit_cost' => $cost,
                'paid_by' => $data['paid_by'] ?? 'cash',
                'reference' => $data['reference'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Many pieces (a box of chargers) go in together, a few hundred rows at a time.
            $rows = [];
            $now = now();
            for ($i = 0; $i < $quantity; $i++) {
                $details = array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : $v, $units[$i] ?? []), fn ($v) => $v !== null && $v !== '');
                $rows[] = ['school_id' => $school->id, 'product_id' => $product->id, 'purchase_id' => $purchase->id, 'unit_cost' => $cost, 'details' => $details ? json_encode($details) : null, 'status' => 'in_stock', 'created_at' => $now, 'updated_at' => $now];
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                StockUnit::insert($chunk);
            }

            if ($quantity * $cost > 0) {
                $entry = Ledger::for($school)->post($purchase->paid_by === 'opening' ? 'journal' : 'payment', $purchase->date, $purchase->number.' · '.$quantity.' × '.$product->label().($purchase->supplier ? ' from '.$purchase->supplier : ''), [
                    ['account' => 'inventory', 'debit' => $total, 'memo' => $product->label()],
                    ...match ($purchase->paid_by) {
                        'credit' => [['account' => 'payable', 'credit' => $total, 'memo' => $purchase->supplier, 'vendor_id' => $vendor?->id]],
                        'opening' => [['account' => 'capital', 'credit' => $total, 'memo' => $purchase->supplier, 'vendor_id' => $vendor?->id]],
                        // Cash or bank: what was paid now, and the rest owed to the vendor (zero lines are dropped).
                        default => [
                            ['account' => $purchase->paid_by === 'bank' ? 'bank' : 'cash', 'credit' => $paidNow, 'memo' => $purchase->supplier, 'vendor_id' => $vendor?->id],
                            ['account' => 'payable', 'credit' => $total - $paidNow, 'memo' => $purchase->supplier, 'vendor_id' => $vendor?->id],
                        ],
                    },
                ], $purchase);
                $purchase->update(['journal_entry_id' => $entry->id]);
            }

            return $purchase->load('units');
        });
    }

    public const MAX_PIECES = 5000;

    /**
     * An IMEI, engine no. or serial no. can belong to one piece only, across the whole business.
     *
     * @param  array{key: string, label: string}  $field
     * @param  array<int, ?string>  $values
     */
    public static function assertUnique(School $school, array $field, array $values, array $exceptUnitIds = []): void
    {
        $values = array_values(array_filter(array_map(fn ($v) => trim((string) $v), $values), fn ($v) => $v !== ''));
        $repeated = array_keys(array_filter(array_count_values($values), fn ($n) => $n > 1));
        if ($repeated) {
            throw new InvalidArgumentException($field['label'].' '.$repeated[0].' is written twice.');
        }
        if (! $values) {
            return;
        }

        $taken = StockUnit::withoutGlobalScopes()->with('product')->where('school_id', $school->id)
            ->whereNotIn('id', $exceptUnitIds)->whereIn('details->'.$field['key'], $values)->first();
        if ($taken) {
            throw new InvalidArgumentException($field['label'].' '.$taken->details[$field['key']].' is already on a piece of '.$taken->product->label().' ('.($taken->status === 'sold' ? 'sold' : 'in stock').').');
        }
    }

    /**
     * Finds pieces by any detail (IMEI, engine no., serial no.), in stock or sold.
     *
     * @return Collection<int, StockUnit>
     */
    public static function find(School $school, string $term)
    {
        $term = trim($term);
        if (mb_strlen($term) < 3) {
            return collect();
        }

        return StockUnit::withoutGlobalScopes()->with(['product.type', 'purchase', 'contract.customer', 'invoiceLine.invoice.customer'])
            ->where('school_id', $school->id)->where('details', 'like', '%'.addcslashes($term, '%_\\').'%')
            ->latest('id')->limit(20)->get();
    }

    /**
     * Pieces for an invoice line: the ones chosen, or else the oldest in stock. Locked until the sale is saved.
     *
     * @return Collection<int, StockUnit>
     */
    public function take(Product $product, int $quantity, array $unitIds = [])
    {
        $query = StockUnit::withoutGlobalScopes()->where('school_id', $product->school_id)->where('product_id', $product->id)->where('status', 'in_stock')->lockForUpdate();

        if ($unitIds) {
            $units = $query->whereIn('id', $unitIds)->get();
            if ($units->count() !== count(array_unique($unitIds)) || $units->count() !== $quantity) {
                throw new InvalidArgumentException('Some chosen pieces of '.$product->label().' are no longer in stock, or the count does not match the quantity.');
            }

            return $units;
        }

        $units = $query->orderBy('id')->limit($quantity)->get();
        if ($units->count() < $quantity) {
            throw new InvalidArgumentException('Only '.$units->count().' '.$product->label().' in stock.');
        }

        return $units;
    }

    /** The piece to sell: the one chosen, or else the oldest in stock. Locked until the sale is saved. */
    public function pick(Product $product, ?int $unitId = null): ?StockUnit
    {
        $query = StockUnit::withoutGlobalScopes()->where('school_id', $product->school_id)->where('product_id', $product->id)->lockForUpdate();

        if ($unitId) {
            $unit = $query->whereKey($unitId)->first();
            if (! $unit || $unit->status !== 'in_stock') {
                throw new InvalidArgumentException('That piece is no longer in stock. Choose another.');
            }

            return $unit;
        }

        return $query->where('status', 'in_stock')->orderBy('id')->first();
    }

    /** @return array{count: int, value: int} pieces in stock and what they cost */
    public static function onHand(Product $product): array
    {
        $row = StockUnit::withoutGlobalScopes()->where('product_id', $product->id)->where('status', 'in_stock')
            ->selectRaw('COUNT(*) as n, COALESCE(SUM(unit_cost),0) as v')->first();

        return ['count' => (int) $row->n, 'value' => (int) $row->v];
    }
}
