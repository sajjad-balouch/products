<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Contract;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Aging of what customers owe: the overdue money of each running plan, by how long it has been late,
 * and what is not due yet. The standard way to judge recovery and risk.
 */
#[Title('Aging of dues')]
class AgingReport extends Component
{
    use InBusiness;

    public const BUCKETS = ['current' => 'Not due yet', 'd30' => '1–30 days', 'd60' => '31–60 days', 'd90' => '61–90 days', 'over' => 'Over 90 days'];

    public function render()
    {
        $today = today();
        $rows = Contract::with(['customer', 'schedule'])->where('status', 'active')->get()->map(function (Contract $c) use ($today) {
            $b = array_fill_keys(array_keys(self::BUCKETS), 0);
            foreach ($c->schedule as $s) {
                $left = $s->amount - $s->paid;
                if ($left <= 0) {
                    continue;
                }
                $late = $s->due_on->lt($today) ? (int) $s->due_on->diffInDays($today) : 0;
                $b[match (true) {
                    $late === 0 => 'current', $late <= 30 => 'd30', $late <= 60 => 'd60', $late <= 90 => 'd90', default => 'over'
                }] += $left;
            }

            return ['contract' => $c, 'buckets' => $b, 'total' => array_sum($b)];
        })->filter(fn ($r) => $r['total'] > 0)->sortByDesc(fn ($r) => $r['buckets']['over'] * 1e9 + $r['buckets']['d90'] * 1e6 + $r['buckets']['d60'] * 1e3 + $r['buckets']['d30'])->values();

        $totals = collect(array_keys(self::BUCKETS))->mapWithKeys(fn ($k) => [$k => $rows->sum(fn ($r) => $r['buckets'][$k])]);

        return view('livewire.biz.aging', ['rows' => $rows, 'totals' => $totals, 'grand' => $totals->sum()]);
    }
}
