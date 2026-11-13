<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Contract;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** The recovery list: who is late and by how much, with one-tap reminders to them and their guarantors. */
#[Title('Due and recovery')]
class Due extends Component
{
    use InBusiness;

    public const BUCKETS = ['week' => 'Due in the next 7 days', 'today' => 'Due today', 'late' => 'All late', '1-30' => 'Late 1–30 days', '31-60' => 'Late 31–60 days', '61-90' => 'Late 61–90 days', '90+' => 'Late over 90 days'];

    #[Url]
    public string $bucket = 'late';

    /** Queues reminders for every late plan in the list, for sending one by one from the outbox. */
    public function remindAll(): void
    {
        $count = 0;
        foreach ($this->rows() as $c) {
            Message::create(['school_id' => $c->school_id, 'created_by' => Auth::id(), 'phone' => $c->customer->phone, 'category' => 'installment', 'body' => ContractShow::reminderText($c, $this->business()->name)]);
            $count++;
        }
        $this->dispatch('toast', message: $count.' '.str('reminder')->plural($count).' added to the WhatsApp outbox.');
    }

    private function rows()
    {
        $today = today();

        return Contract::with(['customer', 'schedule', 'guarantors', 'receipts' => fn ($q) => $q->whereNull('voided_at')])
            ->where('status', 'active')->get()
            ->filter(function (Contract $c) use ($today) {
                $next = $c->nextDue();
                if (! $next) {
                    return false;
                }
                $late = $c->daysLate();

                return match ($this->bucket) {
                    'today' => $next->due_on->isSameDay($today),
                    'week' => $next->due_on->between($today, $today->copy()->addDays(7)),
                    '1-30' => $late >= 1 && $late <= 30,
                    '31-60' => $late >= 31 && $late <= 60,
                    '61-90' => $late >= 61 && $late <= 90,
                    '90+' => $late > 90,
                    default => $c->overdue() > 0,
                };
            })
            ->sortByDesc(fn ($c) => [$c->daysLate(), $c->overdue()])->values();
    }

    public function render()
    {
        $rows = $this->rows();

        return view('livewire.biz.due', [
            'rows' => $rows,
            'totalOverdue' => $rows->sum(fn ($c) => $c->overdue()),
            'business' => $this->business()->name,
        ]);
    }
}
