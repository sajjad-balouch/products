<div class="mx-auto max-w-3xl">
    <x-page-header title="Collect payment" subtitle="Type the customer's name, phone, CNIC or plan number, then take the payment." />

    <div class="card-pad">
        <div class="relative">
            <x-icon name="users" class="pointer-events-none absolute start-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" />
            <input class="input py-3 ps-11 text-lg" type="search" placeholder="Name, 0300…, CNIC or IS-0001" wire:model.live.debounce.300ms="search" autofocus aria-label="Find customer or plan">
        </div>

        @if (strlen(trim($search)) >= 2)
            <ul class="mt-4 space-y-2">
                @forelse ($contracts as $c)
                    @php($overdue = $c->overdue())
                    @php($next = $c->nextDue())
                    <li wire:key="c-{{ $c->id }}" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                        <div class="min-w-0">
                            <div class="font-semibold">{{ $c->customer->name }} <span class="text-sm font-normal text-slate-500">{{ $c->customer->phone }}</span></div>
                            <div class="text-sm text-slate-600">{{ $c->number }} · {{ $c->item }}</div>
                            <div class="mt-1 flex flex-wrap gap-x-4 text-xs">
                                <span>Owed <strong>{{ rs($c->outstanding()) }}</strong></span>
                                @if ($overdue)<span class="text-red-700">Overdue <strong>{{ rs($overdue) }}</strong> · {{ $c->daysLate() }} days</span>
                                @elseif ($next)<span class="text-slate-500">Next {{ rs($next->amount - $next->paid) }} on {{ $next->due_on->format('j M') }}</span>@endif
                            </div>
                        </div>
                        <a class="btn btn-primary" href="{{ route('biz.contract', ['contract' => $c, 'collect' => 1]) }}" wire:navigate><x-icon name="cash" class="h-4 w-4" /> Take payment</a>
                    </li>
                @empty
                    <li class="p-6 text-center text-sm text-slate-500">No running plan found.</li>
                @endforelse
            </ul>
        @else
            <p class="mt-4 text-center text-sm text-slate-500">Start typing to find a customer.</p>
        @endif
    </div>
</div>
