<thead>
    <tr class="bg-slate-50">
        <th class="px-3 py-2 text-start text-xs text-slate-500">Period</th>
        @foreach ($days as $d)<th class="px-3 py-2 text-xs text-slate-500">{{ $d }}</th>@endforeach
    </tr>
</thead>
