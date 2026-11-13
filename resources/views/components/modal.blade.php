@props(['title', 'close' => null, 'size' => 'md'])
{{--
    A pop-up that always fits the screen: the header stays put and only the content scrolls.
    On phones it opens as a sheet from the bottom. Esc closes it when it has a close action.
--}}
@php($width = ['sm' => 'sm:max-w-md', 'md' => 'sm:max-w-lg', 'lg' => 'sm:max-w-2xl', 'xl' => 'sm:max-w-4xl'][$size] ?? 'sm:max-w-lg')
@php($closeJs = $close ? '$wire.'.(str_ends_with($close, ')') ? $close : $close.'()') : null)
<div data-modal class="fixed inset-0 z-50 overflow-y-auto overscroll-contain bg-slate-950/50 backdrop-blur-[2px]" role="dialog" aria-modal="true" aria-label="{{ $title }}"
     @if ($closeJs) x-data x-on:keydown.escape.window="{{ $closeJs }}" @endif>
    <div class="flex min-h-full items-end justify-center sm:items-center sm:p-4">
        <div class="flex max-h-[92dvh] w-full {{ $width }} flex-col rounded-t-2xl bg-white shadow-2xl sm:max-h-[calc(100dvh-2rem)] sm:rounded-xl">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-200 px-5 py-3">
                <h2 class="min-w-0 truncate font-semibold text-slate-900">{{ $title }}</h2>
                @if ($close)
                    <button type="button" wire:click="{{ $close }}" class="-me-2 shrink-0 rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
                        <x-icon name="x-circle" class="h-5 w-5" />
                    </button>
                @endif
            </div>
            <div class="modal-body min-h-0 flex-1 overflow-y-auto overscroll-contain px-5 pt-4">{{ $slot }}</div>
        </div>
    </div>
</div>
