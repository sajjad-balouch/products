<?php

namespace App\Livewire\Fees;

use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Services\MessageService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Defaulters')]
class Defaulters extends Component
{
    public string $search = '';

    /** Open balance per family, split by how long it has been overdue. */
    public static function report(): Collection
    {
        $today = now()->startOfDay();

        return FeeVoucher::open()->whereDate('due_on', '<', $today)->with('guardian.students.section.schoolClass')->get()
            ->groupBy('guardian_id')
            ->map(function ($vouchers) use ($today) {
                $buckets = ['d30' => 0, 'd60' => 0, 'd90' => 0];

                foreach ($vouchers as $voucher) {
                    $days = (int) $voucher->due_on->diffInDays($today);
                    $buckets[$days <= 30 ? 'd30' : ($days <= 60 ? 'd60' : 'd90')] += $voucher->balance;
                }

                return ['guardian' => $vouchers->first()->guardian, 'months' => $vouchers->count(), 'total' => $vouchers->sum('balance')] + $buckets;
            })
            ->sortByDesc('total')->values();
    }

    public function remind(MessageService $messages, int $guardianId): void
    {
        $this->queueFor($messages, Guardian::findOrFail($guardianId));
        $this->dispatch('toast', message: 'Reminder added to the WhatsApp outbox.');
    }

    public function remindAll(MessageService $messages): void
    {
        $rows = static::report();
        foreach ($rows as $row) {
            $messages->feeReminder($row['guardian'], $row['total']);
        }
        $this->dispatch('toast', message: $rows->count().' reminders added to the WhatsApp outbox.');
    }

    private function queueFor(MessageService $messages, Guardian $guardian): void
    {
        $balance = FeeVoucher::where('guardian_id', $guardian->id)->open()->get()->sum('balance');
        $messages->feeReminder($guardian, $balance);
    }

    public function render()
    {
        $rows = static::report();

        if ($this->search !== '') {
            $needle = mb_strtolower($this->search);
            $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['guardian']->name.' '.$r['guardian']->phone.' '.$r['guardian']->students->pluck('name')->join(' ')), $needle));
        }

        return view('livewire.fees.defaulters', [
            'rows' => $rows,
            'total' => $rows->sum('total'),
            'old' => $rows->sum('d90'),
        ]);
    }
}
