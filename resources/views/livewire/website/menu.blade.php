<div>
    <x-page-header title="Website menus" subtitle="The links in the header and footer of every page.">
        <a class="btn" href="{{ $site->url() }}" target="_blank" rel="noopener">View website</a>
        <button class="btn btn-primary" wire:click="openForm()">Add link</button>
    </x-page-header>

    <div class="mb-4 flex w-fit rounded-lg border border-slate-300 bg-white p-0.5">
        @foreach (['header' => 'Header menu', 'footer' => 'Footer menu'] as $key => $label)
            <button wire:key="loc-{{ $key }}" @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $location === $key, 'text-slate-600' => $location !== $key]) wire:click="$set('location', '{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Text</th><th>Goes to</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($menu as $item)
                <tr wire:key="m-{{ $item->id }}">
                    <td @class(['font-medium', 'ps-8! font-normal' => $item->parent_id])>{{ $item->parent_id ? '↳ ' : '' }}{{ $item->label }}</td>
                    <td class="text-xs">{{ $item->page ? $item->page->title.' page' : $item->url }}{{ $item->new_tab ? ' (new tab)' : '' }}</td>
                    <td><span @class(['badge', 'badge-green' => $item->is_visible, 'badge-slate' => ! $item->is_visible])>{{ $item->is_visible ? 'Visible' : 'Hidden' }}</span></td>
                    <td class="text-end whitespace-nowrap">
                        <button class="btn btn-sm" wire:click="move({{ $item->id }}, -1)" aria-label="Move up">↑</button>
                        <button class="btn btn-sm" wire:click="move({{ $item->id }}, 1)" aria-label="Move down">↓</button>
                        <button class="btn btn-sm" wire:click="openForm({{ $item->id }})">Edit</button>
                        <button class="btn btn-sm" wire:click="toggle({{ $item->id }})">{{ $item->is_visible ? 'Hide' : 'Show' }}</button>
                        <button class="btn btn-sm btn-danger" wire:click="delete({{ $item->id }})" wire:confirm="Remove {{ $item->label }} from this menu?">Remove</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-slate-500">This menu has no links.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit link' : 'Add link'" close="$set('showForm', false)">
            <form wire:submit="save" class="space-y-3">
                <x-field label="Text" name="label"><input id="label" class="input" wire:model="label"></x-field>
                <x-field label="Page" name="site_page_id">
                    <select id="site_page_id" class="input" wire:model="site_page_id">
                        <option value="">Another link (enter below)</option>
                        @foreach ($pages as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
                    </select>
                </x-field>
                <x-field label="Other link" name="url" hint="Used when no page is chosen. For example /signup or https://example.com. Enter # for a link that only opens a dropdown."><input id="url" class="input" wire:model="url"></x-field>
                @if ($location === 'header')
                    <x-field label="Show inside dropdown of" name="parent_id">
                        <select id="parent_id" class="input" wire:model="parent_id">
                            <option value="">No dropdown, show in the main menu</option>
                            @foreach ($parents as $p)<option value="{{ $p->id }}">{{ $p->label }}</option>@endforeach
                        </select>
                    </x-field>
                @endif
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="new_tab"> Open in a new tab</label>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
