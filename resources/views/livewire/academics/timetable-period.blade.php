<td class="px-3 py-2 whitespace-nowrap">
    @if ($canEdit)
        <button class="text-start hover:underline" wire:click="openPeriodForm({{ $period->id }})" aria-label="Edit {{ $period->name }}">
            <div class="font-medium">{{ $period->name }}</div>
            <div class="text-xs text-slate-500">{{ $period->starts_at }}-{{ $period->ends_at }}</div>
        </button>
    @else
        <div class="font-medium">{{ $period->name }}</div>
        <div class="text-xs text-slate-500">{{ $period->starts_at }}-{{ $period->ends_at }}</div>
    @endif
</td>
