<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** A cash, bank or credit sale of one or more products (retail or wholesale). */
class Invoice extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_invoices';

    protected $guarded = ['id'];

    protected $casts = ['date' => 'date', 'voided_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class, 'invoice_id')->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id')->orderBy('date')->orderBy('id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function buyer(): string
    {
        return $this->customer?->name ?? ($this->customer_name ?: 'Walk-in customer');
    }

    public function balance(): int
    {
        return $this->voided_at ? 0 : max(0, $this->total - $this->paid);
    }

    public function profit(): int
    {
        return $this->total - $this->cost;
    }

    public function salesman()
    {
        return $this->belongsTo(Employee::class, 'salesman_id');
    }
}
