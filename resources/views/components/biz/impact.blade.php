@props(['impact' => null, 'compact' => false])
{{-- The exact effect of saving, worked out by running the real save and undoing it. --}}
@if ($impact)
    <div {{ $attributes->class(['rounded-xl border text-sm', 'border-amber-300 bg-amber-50' => ! $impact['ok'], 'border-emerald-200 bg-emerald-50/60' => $impact['ok']]) }} data-impact>
        @if (! $impact['ok'])
            <div class="flex gap-2 px-3 py-2.5 text-amber-900">
                <x-icon name="alert-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                <div>
                    @if (! empty($impact['pending']))
                        <p class="font-medium">Fill in the form to see exactly what saving will change.</p>
                        <p class="text-xs text-amber-800">Still needed: {{ $impact['pending'] }}</p>
                    @else
                        <p class="font-medium">This cannot be saved yet.</p>
                        <p class="text-xs text-amber-800">{{ $impact['error'] }}</p>
                    @endif
                </div>
            </div>
        @else
            <div @class(['px-3 py-2.5' => ! $compact, 'px-2.5 py-2 text-xs' => $compact])>
                <p class="flex items-center gap-2 font-semibold text-emerald-900"><x-icon name="check-circle" class="h-4 w-4" /> What saving will change</p>
                @if (! $impact['effects'] && ! $impact['stock'])
                    <p class="mt-1 text-slate-600">Nothing changes in money or stock.</p>
                @endif
                <ul @class(['mt-1.5 space-y-1' => ! $compact, 'mt-1 max-h-40 space-y-0.5 overflow-y-auto' => $compact])>
                    @foreach ($impact['effects'] as $e)
                        <li class="flex items-baseline justify-between gap-3">
                            <span @class(['text-emerald-800' => $e['tone'] === 'good', 'text-red-700' => $e['tone'] === 'bad', 'text-slate-700' => $e['tone'] === 'info'])>{{ $e['text'] }}</span>
                            <span class="shrink-0 font-semibold tabular-nums">{{ rs($e['amount']) }}</span>
                        </li>
                    @endforeach
                    @foreach ($impact['stock'] as $s)
                        <li class="flex items-baseline justify-between gap-3">
                            <span class="text-slate-700">{{ $s['product'] }}: {{ abs($s['pieces']) }} {{ abs($s['pieces']) === 1 ? 'piece' : 'pieces' }} {{ $s['pieces'] >= 0 ? (abs($s['pieces']) === 1 ? 'comes into' : 'come into') : (abs($s['pieces']) === 1 ? 'leaves' : 'leave') }} stock</span>
                            <span class="shrink-0 text-xs text-slate-500 tabular-nums">cost {{ rs(abs($s['cost'])) }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($impact['profit'] !== null)
                    <p @class(['mt-2 flex justify-between gap-3 border-t border-emerald-200 pt-2 font-semibold', 'text-emerald-800' => $impact['profit'] >= 0, 'text-red-700' => $impact['profit'] < 0])>
                        <span>{{ $impact['profit'] >= 0 ? 'Profit from this entry · Munafa' : 'Lowers your profit by · Munafa kam hoga' }}</span>
                        <span class="tabular-nums">{{ rs(abs($impact['profit'])) }}</span>
                    </p>
                @endif
                @if ($impact['lines'])
                    <details class="mt-2">
                        <summary class="cursor-pointer text-xs font-medium text-emerald-800">Books entry (debit and credit)</summary>
                        <div class="mt-1.5 overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead><tr class="text-slate-500"><th class="py-1 text-start font-medium">Account</th><th class="py-1 text-end font-medium">Debit</th><th class="py-1 text-end font-medium">Credit</th></tr></thead>
                                <tbody>
                                    @foreach ($impact['lines'] as $l)
                                        <tr class="border-t border-emerald-100">
                                            <td class="py-1 pe-2">{{ $l['code'] }} {{ $l['account'] }}@if ($l['customer'])<span class="text-slate-500"> · {{ $l['customer'] }}</span>@endif</td>
                                            <td class="py-1 text-end tabular-nums">{{ $l['debit'] ? number_format($l['debit']) : '' }}</td>
                                            <td class="py-1 text-end tabular-nums">{{ $l['credit'] ? number_format($l['credit']) : '' }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="border-t border-emerald-200 font-semibold">
                                        <td class="py-1">Total</td>
                                        <td class="py-1 text-end tabular-nums">{{ number_format(array_sum(array_column($impact['lines'], 'debit'))) }}</td>
                                        <td class="py-1 text-end tabular-nums">{{ number_format(array_sum(array_column($impact['lines'], 'credit'))) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endif
            </div>
        @endif
    </div>
@endif
