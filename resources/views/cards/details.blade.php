{{-- The facts on a card. Landscape cards have less height, so they show fewer lines. --}}
@php($compact = $compact ?? false)
@php($rows = $compact ? array_slice($card['rows'], 0, isset($card['children']) ? 2 : 4, true) : $card['rows'])
<table class="rows">
    @foreach ($rows as $label => $value)
        <tr><td>{{ $label }}</td><td>{{ $value }}</td></tr>
    @endforeach
</table>
@isset($card['children'])
    <div class="kids">
        @foreach (array_slice($card['children'], 0, $compact ? 2 : 4) as $child)
            <div><b>{{ $child['name'] }}</b><i>{{ $child['class'] }}</i></div>
        @endforeach
        @php($more = count($card['children']) - ($compact ? 2 : 4) + $card['more_children'])
        @if ($more > 0)<div><i>and {{ $more }} more</i></div>@endif
    </div>
@endisset
