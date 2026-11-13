<?php

namespace App\Services\Biz;

use App\Models\Biz\Customer;
use App\Models\Biz\Employee;
use App\Models\Biz\Invoice;
use App\Models\Biz\Order;
use App\Models\Biz\OrderLine;
use App\Models\Biz\Product;
use App\Models\School;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orders from the salesman app. Saving is safe to repeat: the phone gives every order its own
 * id, so when a weak signal makes it send the same order twice, it is stored once.
 */
class Orders
{
    /**
     * @param  array{uuid: string, customer_id?: ?int, new_customer?: ?array{name: string, phone?: ?string, address?: ?string}, price_list?: string,
     *               notes?: ?string, ordered_at?: ?string, lat?: ?float, lng?: ?float, lines: array<int, array{product_id: int, quantity: int, unit_price: int}>}  $data
     */
    public function place(School $school, User $by, array $data): Order
    {
        $uuid = (string) ($data['uuid'] ?? '');
        if (! preg_match('/^[0-9a-f-]{36}$/i', $uuid)) {
            throw new InvalidArgumentException('This order has no id from the phone.');
        }
        if ($existing = Order::withoutGlobalScopes()->where('school_id', $school->id)->where('client_uuid', $uuid)->first()) {
            return $existing;
        }

        $lines = collect($data['lines'] ?? [])->filter(fn ($l) => (int) ($l['quantity'] ?? 0) > 0)->values();
        if ($lines->isEmpty() || $lines->count() > 200) {
            throw new InvalidArgumentException('An order needs at least one product.');
        }
        $products = Product::withoutGlobalScopes()->where('school_id', $school->id)->whereIn('id', $lines->pluck('product_id'))->get()->keyBy('id');
        foreach ($lines as $l) {
            if (! isset($products[(int) $l['product_id']])) {
                throw new InvalidArgumentException('A product in this order is no longer in the list.');
            }
            if ((int) $l['quantity'] > 100000 || (int) ($l['unit_price'] ?? 0) < 0 || (int) ($l['unit_price'] ?? 0) > 1000000000) {
                throw new InvalidArgumentException('Check the quantity and price.');
            }
        }

        $salesman = Employee::withoutGlobalScopes()->where('school_id', $school->id)->where('user_id', $by->id)->first();

        return DB::transaction(function () use ($school, $by, $data, $uuid, $lines, $products, $salesman) {
            $customer = null;
            if (! empty($data['customer_id'])) {
                $customer = Customer::withoutGlobalScopes()->where('school_id', $school->id)->find($data['customer_id'])
                    ?? throw new InvalidArgumentException('That customer is not in the list.');
            } elseif (! empty($data['new_customer']['name'])) {
                $new = $data['new_customer'];
                $phone = trim((string) ($new['phone'] ?? ''));
                // The same shop added again from the field is matched by phone, not duplicated.
                $customer = ($phone !== '' ? Customer::withoutGlobalScopes()->where('school_id', $school->id)->where('phone', Phone::normalize($phone))->first() : null)
                    ?? Customer::create([
                        'school_id' => $school->id,
                        'code' => Numbers::next($school, 'biz_customers', 'C-', 4),
                        'name' => mb_substr(trim($new['name']), 0, 120),
                        'phone' => $phone !== '' ? Phone::normalize($phone) : '',
                        'address' => mb_substr(trim((string) ($new['address'] ?? '')), 0, 255) ?: null,
                        'notes' => 'Added from the salesman app'.($salesman ? ' by '.$salesman->name : ''),
                    ]);
            }
            if (! $customer) {
                throw new InvalidArgumentException('Choose the customer for this order.');
            }

            $order = Order::create([
                'school_id' => $school->id,
                'number' => Numbers::next($school, 'biz_orders', 'SO-', 5),
                'client_uuid' => $uuid,
                'customer_id' => $customer?->id,
                'salesman_id' => $salesman?->id,
                'user_id' => $by->id,
                'price_list' => ($data['price_list'] ?? 'wholesale') === 'retail' ? 'retail' : 'wholesale',
                'notes' => isset($data['notes']) ? mb_substr(trim((string) $data['notes']), 0, 500) ?: null : null,
                'ordered_at' => ! empty($data['ordered_at']) ? Carbon::parse($data['ordered_at'])->min(now()) : now(),
                'lat' => isset($data['lat']) && is_numeric($data['lat']) ? $data['lat'] : null,
                'lng' => isset($data['lng']) && is_numeric($data['lng']) ? $data['lng'] : null,
                'status' => 'new',
            ]);
            $total = 0;
            foreach ($lines as $l) {
                $product = $products[(int) $l['product_id']];
                $qty = (int) $l['quantity'];
                $price = (int) ($l['unit_price'] ?? ($order->price_list === 'wholesale' && $product->wholesale_price ? $product->wholesale_price : $product->sale_price));
                OrderLine::create(['school_id' => $school->id, 'order_id' => $order->id, 'product_id' => $product->id, 'label' => $product->label(), 'quantity' => $qty, 'unit_price' => $price]);
                $total += $qty * $price;
            }
            $order->update(['total' => $total]);

            return $order->load('lines', 'customer', 'salesman');
        });
    }

    public function accept(Order $order): Order
    {
        if ($order->status !== 'new') {
            throw new InvalidArgumentException('Only a new order can be accepted.');
        }
        $order->update(['status' => 'accepted', 'seen_at' => $order->seen_at ?? now()]);

        return $order;
    }

    public function cancel(Order $order, string $reason): Order
    {
        if (! $order->isOpen()) {
            throw new InvalidArgumentException('This order is already '.strtolower(Order::STATUSES[$order->status]).'.');
        }
        $order->update(['status' => 'cancelled', 'cancel_reason' => mb_substr(trim($reason), 0, 255) ?: 'Cancelled']);

        return $order;
    }

    /** The sale made from this order. */
    public function delivered(Order $order, Invoice $invoice): Order
    {
        if (! $order->isOpen()) {
            throw new InvalidArgumentException('This order is already closed.');
        }
        $order->update(['status' => 'delivered', 'invoice_id' => $invoice->id, 'seen_at' => $order->seen_at ?? now()]);

        return $order;
    }
}
