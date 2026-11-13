{{-- Text to paste somewhere else, with a copy button. --}}
<div x-data="{ done: false }" class="relative mt-2">
    <pre x-ref="text" class="overflow-x-auto rounded-lg bg-slate-900 p-3 pe-12 font-mono text-xs leading-relaxed text-slate-100" dir="ltr">{{ $slot }}</pre>
    <button type="button" class="absolute end-2 top-2 rounded-md bg-white/10 p-1.5 text-slate-200 hover:bg-white/20" title="Copy" aria-label="Copy"
            @click="navigator.clipboard.writeText($refs.text.innerText); done = true; setTimeout(() => done = false, 1500)">
        <x-icon name="clipboard" class="h-4 w-4" x-show="!done" /><x-icon name="check-circle" class="h-4 w-4 text-emerald-400" x-show="done" x-cloak />
    </button>
</div>
