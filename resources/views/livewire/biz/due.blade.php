<div>
    <x-page-header title="Due and recovery" subtitle="Who should pay now. Remind the customer or a guarantor on WhatsApp, or take the payment.">
        @if ($rows->isNotEmpty() && $bucket !== 'week')
            <button class="btn" wire:click="remindAll" wire:confirm="Add a reminder for all {{ $rows->count() }} customers to the WhatsApp outbox?"><x-icon name="message" class="h-4 w-4" /> Remind all {{ $rows->count() }}</button>
        @endif
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-1.5">
        @foreach (\App\Livewire\Biz\Due::BUCKETS as $key => $label)
            <button wire:click="$set('bucket', '{{ $key }}')" @class(['rounded-full px-3 py-1.5 text-sm ring-1', 'bg-brand-600 text-white ring-brand-600' => $bucket === $key, 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50' => $bucket !== $key])>{{ $label }}</button>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3 text-sm">
            <span><strong>{{ $rows->count() }}</strong> {{ str('plan')->plural($rows->count()) }}</span>
            @if ($totalOverdue)<span>Overdue <strong class="text-red-700">{{ rs($totalOverdue) }}</strong></span>@endif
        </div>
        <table class="tbl">
            <thead><tr><th>Customer</th><th>Plan</th><th class="text-end">Overdue</th><th>Late</th><th>Last paid</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $c)
                @php($last = $c->receipts->sortBy('date')->last())
                @php($text = rawurlencode(\App\Livewire\Biz\ContractShow::reminderText($c, $business)))
                <tr wire:key="d-{{ $c->id }}">
                    <td>
                        <a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.customer', $c->customer) }}" wire:navigate>{{ $c->customer->name }}</a>
                        <div class="text-xs text-slate-500">{{ $c->customer->phone }}</div>
                    </td>
                    <td><a class="hover:underline" href="{{ route('biz.contract', $c) }}" wire:navigate>{{ $c->number }}</a><div class="text-xs text-slate-500">{{ $c->item }}</div></td>
                    <td class="text-end font-semibold">{{ rs($c->overdue() ?: ($c->nextDue()->amount - $c->nextDue()->paid)) }}<div class="text-xs font-normal text-slate-500">of {{ rs($c->outstanding()) }}</div></td>
                    <td>
                        @if ($c->daysLate())<span @class(['badge', 'badge-amber' => $c->daysLate() <= 30, 'badge-red' => $c->daysLate() > 30])>{{ $c->daysLate() }} days</span>
                        @else<span class="badge badge-slate">Due {{ $c->nextDue()->due_on->format('j M') }}</span>@endif
                    </td>
                    <td class="text-xs">{{ $last ? $last->date->format('j M Y').' · '.rs($last->total()) : 'Never' }}</td>
                    <td class="text-end whitespace-nowrap">
                        <a class="btn btn-sm" href="https://wa.me/{{ \App\Support\Phone::international($c->customer->phone) }}?text={{ $text }}" target="_blank" rel="noopener" title="WhatsApp the customer"><x-icon name="message" class="h-3.5 w-3.5" /></a>
                        @if ($g = $c->guarantors->firstWhere('phone', '!=', null))
                            <a class="btn btn-sm" href="https://wa.me/{{ \App\Support\Phone::international($g->phone) }}?text={{ rawurlencode('Assalam o Alaikum '.$g->name.'. Aap '.$c->customer->name.' ke zamin hain (plan '.$c->number.'). Un ki '.rs($c->overdue()).' qist late hai. Baraye meharbani unhein yaad dilayein. - '.$business) }}" target="_blank" rel="noopener" title="WhatsApp the guarantor"><x-icon name="users" class="h-3.5 w-3.5" /></a>
                        @endif
                        <a class="btn btn-sm btn-primary" href="{{ route('biz.contract', ['contract' => $c, 'collect' => 1]) }}" wire:navigate>Collect</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-500"><x-icon name="check-circle" class="mx-auto mb-1 h-6 w-6 text-emerald-600" /> Nobody here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
