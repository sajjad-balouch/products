<?php

namespace App\Livewire;

use App\Models\TransportRoute;
use Illuminate\Database\Eloquent\Builder;

class Transport extends ResourcePage
{
    protected function model(): string
    {
        return TransportRoute::class;
    }

    protected function title(): string
    {
        return 'Transport routes';
    }

    protected function subtitle(): ?string
    {
        return 'The route fee is added to the monthly voucher of every student on the route.';
    }

    protected function query(): Builder
    {
        return TransportRoute::query()->withCount('students')->orderBy('name');
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Route', 'rules' => ['required', 'string', 'max:120']],
            'vehicle_no' => ['label' => 'Vehicle no.', 'rules' => ['nullable', 'string', 'max:30']],
            'driver_name' => ['label' => 'Driver', 'rules' => ['nullable', 'string', 'max:120']],
            'driver_phone' => ['label' => 'Driver phone', 'rules' => ['nullable', 'string', 'max:20']],
            'monthly_fee' => ['label' => 'Monthly fee (Rs)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0'], 'default' => 0],
        ];
    }

    protected function columns(): array
    {
        return ['name' => 'Route', 'vehicle_no' => 'Vehicle', 'driver_name' => 'Driver', 'driver_phone' => 'Phone', 'fee' => ['Fee', fn ($r) => 'Rs '.number_format($r->monthly_fee)], 'students_count' => 'Students'];
    }
}
