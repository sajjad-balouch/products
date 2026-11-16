<div>
    <x-page-header title="Vendors (suppliers)" subtitle="Everyone you buy stock or services from, and how much you owe each one. · Jin se maal lete hain, aur kis ka kitna dena hai.">
        <button class="btn btn-primary" wire:click="openForm()"><x-icon name="user-plus" class="h-4 w-4" /> New vendor</button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        <x-stat label="You owe vendors · Kul dena" :value="rs($owedTotal)" icon="wallet" />
        <x-stat label="Vendors" :value="number_format($vendors->total())" icon="users" />
    </div>

    <div class="card">
        <div class="border-b border-slate-200 p-3">
            <input class="input max-w-sm" type="search" placeholder="Name, company, phone or V-0001" wire:model.live.debounce.300ms="search" aria-label="Search vendors">
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Vendor</th><th>Phone</th><th class="text-end">You owe · Dena</th><th></th></tr></thead>
                <tbody>
                @forelse ($vendors as $v)
                    @php($owed = $balances[$v->id] ?? 0)
                    <tr wire:key="v-{{ $v->id }}" @class(['opacity-60' => ! $v->is_active])>
                        <td>
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$v->name" />
                                <div class="min-w-0">
                                    <a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.vendor', $v) }}" wire:navigate>{{ $v->name }}</a>
                                    <div class="text-xs text-slate-500">{{ $v->code }}{{ $v->company ? ' · '.$v->company : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td dir="ltr">{{ $v->phone }}</td>
                        <td @class(['text-end font-semibold', 'text-red-700' => $owed > 0, 'text-slate-500' => $owed <= 0])>{{ rs($owed) }}</td>
                        <td class="text-end whitespace-nowrap">
                            <a class="btn btn-sm" href="{{ route('biz.vendor', $v) }}" wire:navigate>Account · Khata</a>
                            <button class="btn btn-sm" wire:click="openForm({{ $v->id }})" aria-label="Edit"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-slate-500">No vendors yet. Add the people you buy stock from.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $vendors->links() }}</div>
    </div>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit vendor' : 'New vendor'" close="$set('showForm', false)">
            <form wire:submit="save" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Name · Naam" name="form.name"><input id="form.name" class="input" wire:model="form.name" placeholder="Bilal Traders"></x-field>
                    <x-field label="Shop or company" name="form.company"><input id="form.company" class="input" wire:model="form.company"></x-field>
                    <x-field label="Phone" name="form.phone"><input id="form.phone" class="input" type="tel" wire:model="form.phone"></x-field>
                    <x-field label="Address" name="form.address"><input id="form.address" class="input" wire:model="form.address"></x-field>
                    <x-field label="Notes" name="form.notes" class="sm:col-span-2"><input id="form.notes" class="input" wire:model="form.notes"></x-field>
                </div>
                @if ($editingId)<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_active"> Active</label>@endif
                <x-biz.guide key="vendor" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save vendor</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
