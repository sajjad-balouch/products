<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\JournalEntry;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Every entry of the days chosen, in order, with its debits and credits. */
#[Title('Day book')]
class DayBook extends Component
{
    use DateRange, InBusiness;

    public function mount(): void
    {
        if (! request()->has('from')) {
            $this->from = $this->to = today()->toDateString();
        }
    }

    public function render()
    {
        $entries = JournalEntry::with('lines.account')->whereBetween('date', [$this->from, $this->to])->orderBy('date')->orderBy('id')->get();

        return view('livewire.biz.daybook', ['entries' => $entries, 'total' => $entries->sum(fn ($e) => $e->total())]);
    }
}
