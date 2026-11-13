<div>
    <x-page-header title="New entry: what happened?" subtitle="Choose what happened in plain words. The system does the debit and credit for you. · Bas chunein ke kya hua, debit aur credit system khud lagayega.">
        <a class="btn" href="{{ route('biz.vouchers') }}" wire:navigate>All entries</a>
    </x-page-header>

    @if ($saved)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
            <span class="flex items-center gap-2"><x-icon name="check-circle" class="h-5 w-5" /> Saved as <strong>{{ $saved->number }}</strong> · {{ rs($saved->total()) }} · Entry save ho gayi.</span>
            <span class="flex gap-2">
                <a class="btn btn-sm" href="{{ route('biz.print.voucher', $saved) }}" target="_blank">Print</a>
                <button type="button" class="btn btn-sm" wire:click="choose(null)">Another entry · Nayi entry</button>
            </span>
        </div>
    @endif

    @if (! $entry)
        <div class="space-y-5">
            @foreach (\App\Support\Biz\EasyEntries::GROUPS as $group => [$en, $ur])
                <section>
                    <h2 class="mb-2 text-sm font-semibold text-slate-700">{{ $en }} <span class="font-normal text-slate-500">· {{ $ur }}</span></h2>
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach (\App\Support\Biz\EasyEntries::ALL as $key => $e)
                            @continue($e[0] !== $group)
                            <button type="button" wire:click="choose('{{ $key }}')" class="card-pad text-start transition hover:border-brand-300 hover:shadow-md" data-entry="{{ $key }}">
                                <div class="font-semibold text-slate-900">{{ $e[6][0] }}</div>
                                <div class="text-sm text-brand-700">{{ $e[7][0] }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $e[6][1] }} · {{ $e[7][1] }}</div>
                            </button>
                        @endforeach
                        @if ($group === 'start')
                            <a href="{{ route('biz.products') }}" wire:navigate class="card-pad text-start transition hover:border-brand-300 hover:shadow-md">
                                <div class="font-semibold text-slate-900">Stock already in the shop</div>
                                <div class="text-sm text-brand-700">Dukaan mein pehle se para maal</div>
                                <div class="mt-1 text-xs text-slate-500">Products → Stock in → Paid by: "Already in the shop (opening stock)"</div>
                            </a>
                        @endif
                        @if ($group === 'out')
                            <a href="{{ route('biz.products') }}" wire:navigate class="card-pad text-start transition hover:border-brand-300 hover:shadow-md">
                                <div class="font-semibold text-slate-900">Bought stock to sell</div>
                                <div class="text-sm text-brand-700">Bechne ke liye maal khareeda</div>
                                <div class="mt-1 text-xs text-slate-500">Products → Stock in, so each piece keeps its cost</div>
                            </a>
                        @endif
                    </div>
                </section>
            @endforeach
            <p class="text-sm text-slate-500">Sales, installments and their payments have their own screens. For anything not listed here, an accountant can use a <a class="text-brand-600 underline" href="{{ route('biz.vouchers') }}" wire:navigate>journal voucher</a>.</p>
        </div>
    @else
        <form wire:submit="save" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="min-w-0 space-y-4">
                <section class="card-pad space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">{{ $entry['en'][0] }}</h2>
                            <p class="text-brand-700">{{ $entry['ur'][0] }}</p>
                        </div>
                        <button type="button" class="btn btn-sm" wire:click="choose(null)">Change · Badlein</button>
                    </div>

                    <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-3 text-sm text-slate-700">
                        <p class="font-semibold text-sky-900">What happens · Kya hoga</p>
                        <p class="mt-1">{{ $entry['explain'][0] }}</p>
                        <p class="mt-1 text-slate-600">{{ $entry['explain'][1] }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-field label="Amount (Rs) · Raqam" name="amount">
                            <input id="amount" type="number" min="1" class="input text-lg font-semibold" wire:model.live.debounce.400ms="amount" autofocus>
                        </x-field>
                        @if ($entry['money'])
                            <div>
                                <div class="label">{{ $entry['money'] === 'in' ? 'Went into · Kahan aaye' : 'Paid from · Kahan se diye' }}</div>
                                <div class="flex rounded-lg border border-slate-300 bg-white p-0.5">
                                    @foreach (['cash' => 'Cash', 'bank' => 'Bank'] as $k => $label)
                                        <button type="button" wire:click="$set('money', '{{ $k }}')" @class(['flex-1 rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $money === $k, 'text-slate-600' => $money !== $k])>{{ $label }}</button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if ($entry['pick'])
                            @php($pickLabel = ['expense' => 'What was it for · Kis cheez ka', 'vendor' => 'Which vendor · Kaun sa vendor', 'employee' => 'Which employee · Kaun sa employee'][$entry['pick']])
                            <x-field :label="$pickLabel" name="pick" class="sm:col-span-2">
                                <select id="pick" class="input" wire:model.live="pick">
                                    <option value="">Choose · Chunein…</option>
                                    @foreach ($choices as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                                </select>
                            </x-field>
                            @if ($choices->isEmpty() && $entry['pick'] !== 'expense')
                                <p class="text-sm text-amber-800 sm:col-span-2">No {{ $entry['pick'] }}s yet. <a class="underline" href="{{ route($entry['pick'] === 'vendor' ? 'biz.vendors' : 'biz.employees') }}" wire:navigate>Add one first</a>.</p>
                            @endif
                        @endif
                        <x-field label="Date · Tareekh" name="date"><input id="date" type="date" class="input" wire:model.live="date"></x-field>
                        <x-field label="Detail (optional) · Tafseel" name="note" hint="For example: November rent, Ali bhai's loan"><input id="note" class="input" wire:model="note"></x-field>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
                @if ($impact)
                    <x-biz.impact :impact="$impact" />
                @else
                    <p class="rounded-xl border border-dashed border-slate-300 p-4 text-sm text-slate-500">Write the amount to see exactly what will change. · Raqam likhein, phir yahan nazar aayega ke kya badlega.</p>
                @endif
                <button class="btn btn-primary w-full py-3 text-base" wire:loading.attr="disabled" wire:target="save"><x-icon name="check-circle" class="h-5 w-5" /> Save · Save karein</button>
            </aside>
        </form>
    @endif
</div>
