<div class="mt-2 overflow-x-auto rounded-lg border border-slate-200">
    <table class="tbl">
        <thead><tr><th>Type</th><th>Name / Host</th><th>Value / Points to</th><th>TTL</th></tr></thead>
        <tbody>
        @foreach ($rows as [$type, $name, $value])
            <tr>
                <td class="font-mono text-xs font-semibold">{{ $type }}</td>
                <td class="font-mono text-xs">{{ $name }}</td>
                <td class="font-mono text-xs" dir="ltr">
                    <span x-data="{ done: false }" class="inline-flex items-center gap-2">{{ $value }}
                        <button type="button" class="text-slate-400 hover:text-brand-600" title="Copy" aria-label="Copy {{ $value }}" @click="navigator.clipboard.writeText(@js($value)); done = true; setTimeout(() => done = false, 1500)">
                            <x-icon name="clipboard" class="h-3.5 w-3.5" x-show="!done" /><x-icon name="check-circle" class="h-3.5 w-3.5 text-emerald-600" x-show="done" x-cloak />
                        </button>
                    </span>
                </td>
                <td class="text-xs text-slate-500">1 hour / Auto</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
