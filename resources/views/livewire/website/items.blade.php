{{-- An editable list of rows. Needs $items, $itemFields, $itemName and $itemPlural. --}}
<div class="border-t border-slate-100 pt-3">
    <div class="mb-2 text-sm font-semibold">{{ $itemPlural }}</div>
    @isset ($itemHint)<p class="mb-2 text-xs text-slate-500">{{ $itemHint }}</p>@endisset
    <div class="space-y-3">
        @foreach ($items as $i => $item)
            <div class="space-y-2 rounded-lg border border-slate-200 p-3" wire:key="item-{{ $item['_id'] }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-medium text-slate-500">{{ $itemName }} {{ $loop->iteration }}</span>
                    <span class="flex gap-1">
                        <button type="button" class="btn btn-sm" wire:click="moveItem({{ $i }}, -1)" aria-label="Move up" @disabled($loop->first)>↑</button>
                        <button type="button" class="btn btn-sm" wire:click="moveItem({{ $i }}, 1)" aria-label="Move down" @disabled($loop->last)>↓</button>
                        <button type="button" class="btn btn-sm btn-danger" wire:click="removeItem({{ $i }})">Remove</button>
                    </span>
                </div>
                @foreach ($itemFields as $key => $field)
                    @include('livewire.website.field', ['field' => $field, 'model' => 'items.'.$i.'.'.$key, 'upload' => $item['_id'].'_'.$key, 'value' => $item[$key] ?? null])
                @endforeach
            </div>
        @endforeach
    </div>
    <button type="button" class="btn btn-sm mt-2" wire:click="addItem">Add {{ strtolower($itemName) }}</button>
</div>
