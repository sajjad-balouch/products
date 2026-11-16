@props(['name', 'size' => null])
{{-- A person's initials in a circle. The same name always gets the same colour, so a list is easy to scan.
     Hidden on a phone, where the name needs the room more. --}}
@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '?';
    $tones = ['bg-brand-50 text-brand-700 ring-brand-600/15', 'bg-sky-50 text-sky-700 ring-sky-600/15', 'bg-violet-50 text-violet-700 ring-violet-600/15',
        'bg-amber-50 text-amber-700 ring-amber-600/20', 'bg-rose-50 text-rose-700 ring-rose-600/15', 'bg-emerald-50 text-emerald-700 ring-emerald-600/15', 'bg-indigo-50 text-indigo-700 ring-indigo-600/15'];
    $tone = $tones[crc32(mb_strtolower(trim((string) $name))) % count($tones)];
@endphp
<span {{ $attributes->class(['avatar max-sm:hidden', $tone, 'avatar-sm' => $size === 'sm']) }} aria-hidden="true">{{ $initials }}</span>
