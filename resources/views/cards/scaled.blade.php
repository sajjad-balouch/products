{{-- A card face drawn at its real size, then scaled for the screen. Needs what cards.face needs plus $scale. --}}
@php($isLand = (\App\Support\IdCards::TEMPLATES[$s['template']][1] ?? 'portrait') === 'landscape')
@php($w = ($isLand ? \App\Support\IdCards::WIDTH_MM : \App\Support\IdCards::HEIGHT_MM) * $scale)
@php($h = ($isLand ? \App\Support\IdCards::HEIGHT_MM : \App\Support\IdCards::WIDTH_MM) * $scale)
<div style="width: {{ $w }}mm; height: {{ $h }}mm; flex: none" class="rounded-[2mm] shadow-[0_6px_18px_-6px_rgb(15_23_42/.35)]">
    <div style="transform: scale({{ $scale }}); transform-origin: top left">
        @include('cards.face')
    </div>
</div>
