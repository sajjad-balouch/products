<?php

namespace App\Livewire\Biz\Concerns;

use Livewire\Attributes\Url;

/** From and to dates kept in the address, starting at this month. */
trait DateRange
{
    #[Url]
    public string $from = '';

    #[Url]
    public string $to = '';

    public function mountDateRange(): void
    {
        $this->from = $this->validDate($this->from) ?? today()->startOfMonth()->toDateString();
        $this->to = $this->validDate($this->to) ?? today()->toDateString();
    }

    public function updatedFrom(): void
    {
        $this->from = $this->validDate($this->from) ?? today()->startOfMonth()->toDateString();
    }

    public function updatedTo(): void
    {
        $this->to = $this->validDate($this->to) ?? today()->toDateString();
    }

    public function setRange(string $preset): void
    {
        [$this->from, $this->to] = match ($preset) {
            'today' => [today()->toDateString(), today()->toDateString()],
            'last-month' => [today()->subMonthNoOverflow()->startOfMonth()->toDateString(), today()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'year' => [today()->startOfYear()->toDateString(), today()->toDateString()],
            default => [today()->startOfMonth()->toDateString(), today()->toDateString()],
        };
    }

    private function validDate(?string $value): ?string
    {
        return $value && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
    }
}
