<div>
    <x-page-header title="Customers" subtitle="Everyone who buys on installments. Search by name, phone, CNIC or customer number.">
        <button class="btn btn-primary" wire:click="openForm()"><x-icon name="user-plus" class="h-4 w-4" /> New customer</button>
    </x-page-header>

    <div class="card">
        <div class="border-b border-slate-200 p-3">
            <input class="input max-w-sm" type="search" placeholder="Name, phone, CNIC or C-0001" wire:model.live.debounce.300ms="search" aria-label="Search customers">
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Customer</th><th>Phone</th><th>CNIC</th><th>Running plans</th><th></th></tr></thead>
                <tbody>
                @forelse ($customers as $customer)
                    <tr wire:key="c-{{ $customer->id }}">
                        <td>
                            <a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.customer', $customer) }}" wire:navigate>{{ $customer->name }}</a>
                            @if ($customer->is_blacklisted)<span class="badge badge-red ms-1">Blacklisted</span>@endif
                            <div class="text-xs text-slate-500">{{ $customer->code }}{{ $customer->father_name ? ' · s/o '.$customer->father_name : '' }}</div>
                        </td>
                        <td dir="ltr">{{ $customer->phone }}</td>
                        <td class="font-mono text-xs">{{ $customer->cnic }}</td>
                        <td>{{ $customer->running }}</td>
                        <td class="text-end whitespace-nowrap">
                            <a class="btn btn-sm" href="{{ route('biz.contracts.new', ['customer' => $customer->id]) }}" wire:navigate><x-icon name="plus" class="h-3.5 w-3.5" /> Sale</a>
                            <button class="btn btn-sm" wire:click="openForm({{ $customer->id }})"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-slate-500">No customers yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $customers->links() }}</div>
    </div>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit customer' : 'New customer'" close="$set('showForm', false)" size="lg">
            <form wire:submit="save" class="space-y-3">
                @include('livewire.biz.customer-fields')
                <x-field label="Photo" name="photo"><input type="file" class="input" accept="image/png,image/jpeg,image/webp" wire:model="photo"></x-field>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_blacklisted"> Blacklisted (warn before selling to them again)</label>
                <x-biz.guide key="customer" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save customer</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
