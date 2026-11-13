<?php

namespace App\Livewire\Fees;

use App\Livewire\ResourcePage;
use App\Models\FeeHead;

class Heads extends ResourcePage
{
    protected function model(): string
    {
        return FeeHead::class;
    }

    protected function title(): string
    {
        return 'Fee heads';
    }

    protected function subtitle(): ?string
    {
        return 'Monthly heads go on the monthly voucher run. Set amounts per class under Fee structure.';
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'rules' => ['required', 'string', 'max:80'], 'hint' => 'For example Tuition Fee'],
            'is_monthly' => ['label' => 'Charge every month', 'type' => 'checkbox', 'rules' => ['boolean'], 'default' => true],
        ];
    }

    protected function columns(): array
    {
        return ['name' => 'Fee head', 'monthly' => ['Monthly', fn ($r) => $r->is_monthly ? 'Yes' : 'No']];
    }
}
