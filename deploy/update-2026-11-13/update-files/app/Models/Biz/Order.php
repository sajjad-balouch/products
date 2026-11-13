<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** An order a salesman took in the field. The owner accepts it and turns it into a sale. */
class Order extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_orders';

    protected $guarded = ['id'];

    protected $casts = ['ordered_at' => 'datetime', 'seen_at' => 'datetime', 'lat' => 'float', 'lng' => 'float'];

    public const STATUSES = ['new' => 'New', 'accepted' => 'Accepted', 'delivered' => 'Delivered (sale made)', 'cancelled' => 'Cancelled'];

    public const URDU = ['new' => 'Naya', 'accepted' => 'Manzoor', 'delivered' => 'Sale ban gayi', 'cancelled' => 'Cancel'];

    public function lines()
    {
        return $this->hasMany(OrderLine::class, 'order_id')->orderBy('id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function salesman()
    {
        return $this->belongsTo(Employee::class, 'salesman_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['new', 'accepted'], true);
    }

    /** For the phone app. */
    public function toApp(): array
    {
        return [
            'id' => $this->id, 'uuid' => $this->client_uuid, 'number' => $this->number, 'status' => $this->status,
            'customer_id' => $this->customer_id, 'customer' => $this->customer?->name, 'customer_phone' => $this->customer?->phone,
            'salesman' => $this->salesman?->name, 'total' => (int) $this->total, 'notes' => $this->notes,
            'price_list' => $this->price_list, 'ordered_at' => $this->ordered_at->toIso8601String(),
            'invoice' => $this->invoice?->number, 'cancel_reason' => $this->cancel_reason,
            'lines' => $this->lines->map(fn ($l) => ['product_id' => $l->product_id, 'label' => $l->label, 'quantity' => (int) $l->quantity, 'unit_price' => (int) $l->unit_price])->all(),
        ];
    }
}
