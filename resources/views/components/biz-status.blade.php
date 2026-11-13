@props(['contract'])
{{-- Running, late, completed or written off, as a coloured badge. --}}
@php($late = $contract->isOpen() ? $contract->daysLate() : 0)
@if ($contract->status === 'completed')
    <span class="badge badge-green">Completed</span>
@elseif ($contract->status === 'written_off')
    <span class="badge badge-slate">Written off</span>
@elseif ($late > $contract->grace_days)
    <span class="badge badge-red">Late {{ $late }} {{ str('day')->plural($late) }}</span>
@else
    <span class="badge badge-blue">Running</span>
@endif
