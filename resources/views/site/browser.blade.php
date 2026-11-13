{{-- A screenshot inside a browser window. Needs $src; $address is optional. --}}
<div class="site-browser {{ $class ?? '' }}">
    <div class="site-browser-bar" aria-hidden="true">
        <span></span><span></span><span></span>
        <div class="site-browser-address">{{ $address ?? '' }}</div>
    </div>
    <img src="{{ $src }}" alt="{{ $alt ?? '' }}" loading="{{ $eager ?? false ? 'eager' : 'lazy' }}" class="block w-full">
</div>
