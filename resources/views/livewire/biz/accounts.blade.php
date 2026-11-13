<div>
    <x-page-header title="Chart of accounts" subtitle="Every account your money moves through, with today's balance. Accounts marked 'used by sales' are where installment sales and collections post.">
        <button class="btn btn-primary" wire:click="openForm()"><x-icon name="plus" class="h-4 w-4" /> New account</button>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach (\App\Models\Biz\Account::TYPES as $type => $label)
            <div class="card overflow-x-auto">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-semibold">{{ $label }}</h2>
                    <span class="text-sm font-semibold">{{ rs(($accounts[$type] ?? collect())->sum('balance')) }}</span>
                </div>
                <table class="tbl">
                    <tbody>
                    @foreach ($accounts[$type] ?? [] as $a)
                        <tr wire:key="a-{{ $a->id }}" @class(['opacity-50' => ! $a->is_active])>
                            <td class="w-16 font-mono text-xs text-slate-500">{{ $a->code }}</td>
                            <td>
                                <a class="hover:underline" href="{{ route('biz.ledger', ['account' => $a->id]) }}" wire:navigate>{{ $a->name }}</a>
                                @if ($a->system_key)<span class="badge badge-slate ms-1">used by sales</span>@endif
                                @unless ($a->is_active)<span class="badge badge-amber ms-1">hidden</span>@endunless
                            </td>
                            <td class="text-end">{{ rs($a->balance) }}</td>
                            <td class="w-10 text-end"><button class="btn btn-sm" wire:click="openForm({{ $a->id }})" title="Edit"><x-icon name="pencil" class="h-3.5 w-3.5" /></button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit account' : 'New account'" close="$set('showForm', false)" size="sm">
            <form wire:submit="save" class="space-y-3">
                <div class="grid grid-cols-3 gap-3">
                    <x-field label="Code" name="code"><input id="code" class="input" wire:model="code" inputmode="numeric"></x-field>
                    <x-field label="Name" name="name" class="col-span-2"><input id="name" class="input" wire:model="name"></x-field>
                </div>
                <x-field label="Type" name="type" hint="Assets 1xxx, liabilities 2xxx, equity 3xxx, income 4xxx, expenses 5xxx">
                    <select id="type" class="input" wire:model="type">@foreach (\App\Models\Biz\Account::TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                </x-field>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="isActive"> Active (shown when making vouchers)</label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
