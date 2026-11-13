{{-- One side of one card. Needs $card, $s (settings), $school, $logo, $sign, $side ('front' or 'back'). --}}
@php($tpl = $s['template'])
@php($land = (\App\Support\IdCards::TEMPLATES[$tpl][1] ?? 'portrait') === 'landscape')
@php($valid = $s['valid_until'] ? \Illuminate\Support\Carbon::parse($s['valid_until'])->format('d M Y') : null)
@php($style = '--p:'.$s['primary'].';--a:'.$s['accent'])
@php($gid = 'wg'.substr(md5($card['type'].$card['number']), 0, 10))
@php($logoTag = $logo ? '<img src="'.e($logo).'" alt="">' : '<span>'.e(\App\Support\IdCards::initials($school->name)).'</span>')
@php($photoTag = $card['photo'] ? '<img src="'.e($card['photo']).'" alt="">' : '<span>'.e($card['initials']).'</span>')

@if ($side === 'back')
    <div class="idc back {{ $land ? 'land' : '' }}" style="{{ $style }}">
        <div class="ab band"></div>
        <div class="bk">
            <div class="col" @if (! $land) style="align-items:center" @endif>
                <div style="display:flex;align-items:center;gap:1.6mm">
                    <div class="logo" style="width:7mm;height:7mm;border:.3mm solid #e2e8f0">{!! $logoTag !!}</div>
                    <div class="school">{{ $school->name }}</div>
                </div>
                <div class="note">{{ $card['type'] === 'guardian' ? $s['guardian_note'] : $s['back_note'] }}</div>
                <div class="muted">{{ $school->address }}</div>
                @if ($school->phone)<div class="muted">Phone <b>{{ $school->phone }}</b></div>@endif
                @if ($valid)<div class="valid">Valid till {{ $valid }}</div>@endif
                @if (! $land)
                    @if ($s['show_qr'])<div class="qr">{!! \App\Support\Qr::svg($card['qr']) !!}</div>@endif
                @endif
                <div class="sign">
                    @if ($sign)<img src="{{ $sign }}" alt="">@endif
                    <div class="ln">{{ $school->principal_name ?: 'Principal' }}</div>
                </div>
            </div>
            @if ($land && $s['show_qr'])
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1mm">
                    <div class="qr" style="width:19mm;height:19mm">{!! \App\Support\Qr::svg($card['qr']) !!}</div>
                    <div class="muted" style="font-size:5.2pt">{{ $card['number_label'] }} {{ $card['number'] }}</div>
                </div>
            @endif
        </div>
    </div>
@else
    <div class="idc t-{{ $tpl }} {{ $land ? 'land' : '' }}" style="{{ $style }}">
        @switch($tpl)
            @case('wave')
                <div class="ab wave-top">
                    <svg class="w" viewBox="0 0 200 110" preserveAspectRatio="none"><defs><linearGradient id="{{ $gid }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" style="stop-color:var(--p)"/><stop offset="1" style="stop-color:var(--pd)"/></linearGradient></defs>
                        <path d="M0 0H200V72C160 100 120 60 80 80S20 100 0 88Z" fill="url(#{{ $gid }})"/>
                        <path d="M0 92C30 102 55 84 85 86S150 108 200 78" style="fill:none;stroke:var(--a);stroke-width:3"/></svg>
                </div>
                <div class="ab wave-bot"><svg class="w" viewBox="0 0 200 40" preserveAspectRatio="none"><path d="M0 18C50 0 90 30 130 14S185 6 200 12V40H0Z" style="fill:var(--a);opacity:.35"/><path d="M0 24C45 10 95 36 140 20S190 14 200 18V40H0Z" style="fill:var(--p)"/></svg></div>
                <div class="ab brand"><div class="logo">{!! $logoTag !!}</div><div class="school">{{ $school->name }}</div></div>
                <div class="ab role">{{ $card['role'] }}</div>
                <div class="ab photo">{!! $photoTag !!}</div>
                <div class="ab who"><div class="name">{{ $card['name'] }}</div><div class="sub">{{ $card['headline'] }}</div></div>
                <div class="ab body">@include('cards.details')</div>
                <div class="ab foot"><span>{{ $card['number_label'] }} {{ $card['number'] }}</span>@if ($valid)<span>Valid {{ $valid }}</span>@endif</div>
                @break

            @case('crest')
                <div class="ab pattern"></div>
                <div class="ab brand"><div class="logo">{!! $logoTag !!}</div><div class="school">{{ $school->name }}</div></div>
                <div class="ab role">{{ $card['role'] }}</div>
                <div class="ab ring"></div>
                <div class="ab photo">{!! $photoTag !!}</div>
                <div class="ab who"><div class="name">{{ $card['name'] }}</div><div class="sub">{{ $card['headline'] }}</div></div>
                <div class="ab body">@include('cards.details')</div>
                <div class="ab foot"><span class="pill">{{ $card['number'] }}</span>@if ($valid)<span>Valid {{ $valid }}</span>@endif</div>
                @break

            @case('horizon')
                <div class="ab head"><div class="logo">{!! $logoTag !!}</div><div class="school clip">{{ $school->name }}</div><span class="pill">{{ $card['role'] }}</span></div>
                <div class="ab photo">{!! $photoTag !!}</div>
                <div class="ab who"><div class="name clip">{{ $card['name'] }}</div><div class="sub">{{ $card['headline'] }}</div></div>
                <div class="ab body">@include('cards.details', ['compact' => true])</div>
                @if ($s['show_qr'])<div class="ab qr">{!! \App\Support\Qr::svg($card['qr']) !!}</div>@endif
                <div class="ab num">{{ $card['number'] }}</div>
                @if ($valid)<div class="ab valid">Valid till {{ $valid }}</div>@endif
                <div class="ab foot"></div>
                @break

            @case('slate')
                <div class="ab stripe"><span>{{ $card['role'] }}</span></div>
                <div class="ab brand"><div class="logo">{!! $logoTag !!}</div><div class="school">{{ $school->name }}</div></div>
                <div class="ab line"></div>
                @if ($s['show_qr'])<div class="ab qr">{!! \App\Support\Qr::svg($card['qr']) !!}</div>@endif
                <div class="ab photo">{!! $photoTag !!}</div>
                <div class="ab who"><div class="name clip">{{ $card['name'] }}</div><div class="sub">{{ $card['headline'] }}</div></div>
                <div class="ab body">@include('cards.details', ['compact' => true])</div>
                <div class="ab foot"><span>{{ $card['number_label'] }} <b>{{ $card['number'] }}</b></span>@if ($valid)<span>Valid till <b>{{ $valid }}</b></span>@endif</div>
                @break

            @default
                <div class="ab head"></div>
                <div class="ab brand"><div class="logo">{!! $logoTag !!}</div><div class="school">{{ $school->name }}</div></div>
                <div class="ab role">{{ $card['role'] }}</div>
                <div class="ab photo">{!! $photoTag !!}</div>
                <div class="ab who"><div class="name">{{ $card['name'] }}</div>@if ($card['headline'])<span class="pill">{{ $card['headline'] }}</span>@endif</div>
                <div class="ab body">@include('cards.details')</div>
                <div class="ab foot"><span>{{ $card['number_label'] }} {{ $card['number'] }}</span>@if ($valid)<span>Valid {{ $valid }}</span>@endif</div>
        @endswitch
    </div>
@endif
