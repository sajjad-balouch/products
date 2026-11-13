<div>
    <x-page-header title="Installment plans" subtitle="Every installment sale, with what has been paid and what is still owed.">
        <a class="btn btn-primary" href="{{ route('biz.contracts.new') }}" wire:navigate><x-icon name="plus" class="h-4 w-4" /> New installment sale</a>
    </x-page-header>

    <div class="card">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-3">
            <input class="input max-w-xs" type="search" placeholder="Customer, phone, item or IS-0001" wire:model.live.debounce.300ms="search" aria-label="Search">
            <select class="input w-auto" wire:model.live="status" aria-label="Status">
                <option value="active">Running</option>
                <option value="completed">Completed</option>
                <option value="written_off">Written off</option>
                <option value="all">All</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Plan</th><th>Customer</th><th>Item</th><th class="text-end">Installment</th><th class="text-end">Owed</th><th>Next due</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($contracts as $c)
                    @php($next = $c->nextDue())
                    <tr wire:key="k-{{ $c->id }}">
                        <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.contract', $c) }}" wire:navigate>{{ $c->number }}</a><div class="text-xs text-slate-500">{{ $c->sale_date->format('j M Y') }}</div></td>
                        <td>{{ $c->customer->name }}<div class="text-xs text-slate-500">{{ $c->customer->phone }}</div></td>
                        <td>{{ $c->item }}</td>
                        <td class="text-end">{{ rs($c->installment_amount) }}<div class="text-xs text-slate-500">× {{ $c->installments }}</div></td>
                        <td class="text-end font-medium">{{ rs($c->outstanding()) }}</td>
                        <td>{{ $c->isOpen() && $next ? $next->due_on->format('j M Y') : '—' }}</td>
                        <td><x-biz-status :contract="$c" /></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-500">No plans here.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $contracts->links() }}</div>
    </div>
</div>
