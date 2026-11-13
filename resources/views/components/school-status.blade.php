@props(['school'])
@php
    $status = $school->status();
    $label = ['suspended' => 'Suspended', 'trial' => 'Free period', 'active' => 'Active', 'grace' => 'Payment due', 'expired' => $school->lock_when_due ? 'Locked' : 'Expired', 'open' => 'No end date'][$status];
    $tone = ['suspended' => 'badge-red', 'trial' => 'badge-blue', 'active' => 'badge-green', 'grace' => 'badge-amber', 'expired' => 'badge-red', 'open' => 'badge-slate'][$status];
@endphp
<span {{ $attributes->class(['badge', $tone]) }}>{{ $label }}</span>
