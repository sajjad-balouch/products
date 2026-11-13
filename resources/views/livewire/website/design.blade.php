<div>
    <x-page-header title="Website look" subtitle="Brand, header, footer and contact details, used on every page.">
        <a class="btn" href="{{ $site->url() }}" target="_blank" rel="noopener">View website</a>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-1 rounded-lg border border-slate-300 bg-white p-0.5 sm:w-fit">
        @foreach ($tabs as $key => $label)
            <button wire:key="tab-{{ $key }}" @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $tab === $key, 'text-slate-600' => $tab !== $key]) wire:click="$set('tab', '{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>

    <form wire:submit="save" class="card-pad max-w-2xl space-y-3" wire:key="form-{{ $tab }}">
        @foreach ($fields as $key => $field)
            <div wire:key="f-{{ $tab }}-{{ $key }}">
                @include('livewire.website.field', ['field' => $field, 'model' => 'form.'.$key, 'upload' => $key, 'value' => $form[$key] ?? null])
            </div>
        @endforeach
        @if ($list)
            @include('livewire.website.items', ['itemName' => $list['item'], 'itemPlural' => $list['plural'], 'itemHint' => $list['hint']])
        @endif
        <div class="flex justify-end border-t border-slate-100 pt-3">
            <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
        </div>
    </form>
</div>
