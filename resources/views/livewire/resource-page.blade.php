<div>
    <x-page-header :title="$heading" :subtitle="$subheading">
        <button class="btn btn-primary" wire:click="create">Add new</button>
    </x-page-header>

    <div class="card overflow-hidden">
        @if ($hasSearch)
            <div class="border-b border-slate-200 p-3">
                <input class="input max-w-xs" type="search" placeholder="Search" wire:model.live.debounce.300ms="search" aria-label="Search">
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr>
                    @foreach ($columnDefs as $column)<th>{{ is_array($column) ? $column[0] : $column }}</th>@endforeach
                    <th></th>
                </tr></thead>
                <tbody>
                @forelse ($rows as $row)
                    <tr wire:key="row-{{ $row->id }}">
                        @foreach ($columnDefs as $key => $column)
                            <td>{{ is_array($column) ? $column[1]($row) : data_get($row, $key) }}</td>
                        @endforeach
                        <td class="text-end whitespace-nowrap">
                            <button class="btn btn-sm" wire:click="edit({{ $row->id }})">Edit</button>
                            @if ($deletable)
                                <button class="btn btn-sm btn-danger" wire:click="delete({{ $row->id }})" wire:confirm="Delete this record?">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ count($columnDefs) + 1 }}" class="py-10 text-center text-slate-500">Nothing here yet. Use “Add new” to create the first record.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $rows->links() }}</div>

    @if ($showForm)
        <x-modal :title="($editingId ? 'Edit' : 'Add').' '.strtolower($heading)" close="$set('showForm', false)">
            <form wire:submit="save" class="space-y-3">
                @foreach ($fieldDefs as $key => $field)
                    @php($type = $field['type'] ?? 'text')
                    <x-field :label="$type === 'checkbox' ? null : $field['label']" :name="'form.'.$key" :hint="$field['hint'] ?? null">
                        @if (isset($field['options']))
                            <select id="form.{{ $key }}" class="input" wire:model="form.{{ $key }}">
                                <option value="">Select</option>
                                @foreach ($field['options'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                            </select>
                        @elseif ($type === 'textarea')
                            <textarea id="form.{{ $key }}" class="input" rows="4" wire:model="form.{{ $key }}"></textarea>
                        @elseif ($type === 'checkbox')
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.{{ $key }}"> {{ $field['label'] }}</label>
                        @else
                            <input id="form.{{ $key }}" class="input" type="{{ $type }}" wire:model="form.{{ $key }}">
                        @endif
                    </x-field>
                @endforeach
                @error('form')<p class="err">{{ $message }}</p>@enderror
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
