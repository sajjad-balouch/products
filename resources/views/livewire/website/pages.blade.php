<div>
    <x-page-header title="Website pages" subtitle="Every page visitors can see. Open a page to add, change and arrange its sections.">
        <a class="btn" href="{{ $site->url() }}" target="_blank" rel="noopener">View website</a>
        @if ($canStructure)<button class="btn btn-primary" wire:click="openForm">New page</button>@endif
    </x-page-header>

    @if ($isOwner)
        <div class="card-pad mb-4 flex flex-wrap items-center gap-3 text-sm">
            @if ($site->is_published)
                <span class="badge badge-green">Public</span>
                <span class="text-slate-600">Anyone can open <a class="font-mono text-xs text-brand-600 underline" href="{{ $site->url() }}" target="_blank" rel="noopener">{{ $site->url() }}</a></span>
                <button class="btn btn-sm ms-auto" wire:click="setPublished(false)" wire:confirm="Take the whole website offline for visitors?">Make it a draft</button>
            @else
                <span class="badge badge-amber">Draft</span>
                <span class="text-slate-600">Only people who manage the website can see it. Replace the sample text, then publish.</span>
                <button class="btn btn-primary btn-sm ms-auto" wire:click="setPublished(true)" wire:confirm="Make this website public?">Publish website</button>
            @endif
            @if ($site->school_id)
                <button class="btn btn-sm" wire:click="useSchoolTemplate" wire:confirm="This deletes all current pages and menu links, then creates the complete school template. Colours, logo, footer and contact details stay. Continue?">Use school template</button>
            @endif
        </div>
    @endif

    @if (! $hasAnything)
        <div class="card-pad text-sm text-slate-500">Nothing has been assigned to you yet. Ask your admin to give you a part of the website under Website editors.</div>
    @elseif ($pages->isEmpty())
        <div class="card-pad text-sm text-slate-500">No pages have been assigned to you. Use the other Website links in the menu.</div>
    @else
        <div class="card overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Page</th><th>Address</th><th>Sections</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach ($pages as $page)
                    <tr wire:key="page-{{ $page->id }}">
                        <td class="font-medium">
                            <a class="text-brand-600 hover:underline" href="{{ route('website.page', $page->id) }}" wire:navigate>{{ $page->title }}</a>
                            @if ($page->is_home)<span class="badge badge-blue ms-1">Home</span>@endif
                        </td>
                        <td class="font-mono text-xs"><a class="hover:underline" href="{{ $site->pageUrl($page) }}" target="_blank" rel="noopener">{{ $page->is_home ? '/' : $page->slug }}</a></td>
                        <td>{{ $page->sections_count }}</td>
                        <td><span @class(['badge', 'badge-green' => $page->is_published, 'badge-slate' => ! $page->is_published])>{{ $page->is_published ? 'Visible' : 'Hidden' }}</span></td>
                        <td class="text-end whitespace-nowrap">
                            <a class="btn btn-sm" href="{{ route('website.page', $page->id) }}" wire:navigate>Edit</a>
                            @if ($canStructure)
                                <button class="btn btn-sm" wire:click="move({{ $page->id }}, -1)" aria-label="Move {{ $page->title }} up" @disabled($loop->first)>↑</button>
                                <button class="btn btn-sm" wire:click="move({{ $page->id }}, 1)" aria-label="Move {{ $page->title }} down" @disabled($loop->last)>↓</button>
                                <button class="btn btn-sm" wire:click="openForm({{ $page->id }})">Settings</button>
                                <button class="btn btn-sm" wire:click="togglePublished({{ $page->id }})">{{ $page->is_published ? 'Hide' : 'Show' }}</button>
                                @unless ($page->is_home)
                                    <button class="btn btn-sm" wire:click="makeHome({{ $page->id }})">Make home</button>
                                    <button class="btn btn-sm btn-danger" wire:click="delete({{ $page->id }})" wire:confirm="Delete {{ $page->title }} and all its sections? This cannot be undone.">Delete</button>
                                @endunless
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($showForm)
        <x-modal :title="$editingId ? 'Page settings' : 'New page'" close="$set('showForm', false)">
            <form wire:submit="save" class="space-y-3">
                <x-field label="Title" name="title"><input id="title" class="input" wire:model="title"></x-field>
                <x-field label="Web address" name="slug" hint="Letters, numbers and dashes. Leave empty to make it from the title."><input id="slug" class="input" wire:model="slug"></x-field>
                <x-field label="Description for search engines" name="seo_description"><textarea id="seo_description" class="input" rows="2" wire:model="seo_description"></textarea></x-field>
                @unless ($editingId)
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="addToMenu"> Add to the header and footer menus</label>
                @endunless
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
