<div>
    <x-page-header :title="$page->title" subtitle="Sections appear on the page in this order.">
        <a class="btn" href="{{ route('website.pages') }}" wire:navigate>All pages</a>
        <a class="btn" href="{{ $site->pageUrl($page) }}" target="_blank" rel="noopener">View page</a>
        <button class="btn btn-primary" wire:click="$set('showTypes', true)">Add section</button>
    </x-page-header>

    <div class="space-y-2">
        @forelse ($sections as $section)
            <div wire:key="section-{{ $section->id }}" @class(['card-pad flex flex-wrap items-center gap-3 p-3!', 'opacity-60' => ! $section->is_visible])>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold">{{ $types[$section->type]['label'] ?? $section->type }} @unless ($section->is_visible)<span class="badge badge-slate ms-1">Hidden</span>@endunless</div>
                    <div class="truncate text-xs text-slate-500">{{ ($section->data['heading'] ?? '') ?: (isset($section->data['items']) ? count($section->data['items']).' items' : ($types[$section->type]['about'] ?? '')) }}</div>
                </div>
                <div class="flex flex-wrap gap-1.5">
                    <button class="btn btn-sm" wire:click="moveSection({{ $section->id }}, -1)" aria-label="Move up" @disabled($loop->first)>↑</button>
                    <button class="btn btn-sm" wire:click="moveSection({{ $section->id }}, 1)" aria-label="Move down" @disabled($loop->last)>↓</button>
                    <button class="btn btn-sm" wire:click="edit({{ $section->id }})">Edit</button>
                    <button class="btn btn-sm" wire:click="duplicateSection({{ $section->id }})">Copy</button>
                    <button class="btn btn-sm" wire:click="toggleSection({{ $section->id }})">{{ $section->is_visible ? 'Hide' : 'Show' }}</button>
                    <button class="btn btn-sm btn-danger" wire:click="deleteSection({{ $section->id }})" wire:confirm="Delete this section?">Delete</button>
                </div>
            </div>
        @empty
            <div class="card-pad text-sm text-slate-500">This page is empty. <button class="text-brand-600 underline" wire:click="$set('showTypes', true)">Add the first section</button>.</div>
        @endforelse
    </div>

    @if ($showTypes)
        <x-modal title="Add section" close="$set('showTypes', false)" size="lg">
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($types as $key => $type)
                    <button class="rounded-lg border border-slate-200 p-3 text-start hover:border-brand-500" wire:key="type-{{ $key }}" wire:click="chooseType('{{ $key }}')">
                        <div class="text-sm font-medium">{{ $type['label'] }}</div>
                        <div class="text-xs text-slate-500">{{ $type['about'] }}</div>
                    </button>
                @endforeach
            </div>
        </x-modal>
    @endif

    @if ($editingType)
        <x-modal :title="$types[$editingType]['label']" close="closeEditor" size="xl">
            <form wire:submit="saveSection" class="space-y-3">
                @foreach ($fields as $key => $field)
                    <div wire:key="f-{{ $key }}">
                        @include('livewire.website.field', ['field' => $field, 'model' => 'form.'.$key, 'upload' => $key, 'value' => $form[$key] ?? null])
                    </div>
                @endforeach

                @if ($itemFields)
                    @include('livewire.website.items', ['itemName' => $types[$editingType]['item'], 'itemPlural' => $types[$editingType]['item'].'s'])
                @endif

                <div class="flex justify-end gap-2 border-t border-slate-100 pt-3">
                    <button type="button" class="btn" wire:click="closeEditor">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save section</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
