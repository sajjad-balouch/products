@php($mapAddress = $contact['map_address'] ?: $contact['address'])
@include('site.heading')
@if (! empty($d['text']))<p class="site-muted mx-auto -mt-4 mb-8 max-w-2xl text-center whitespace-pre-line">{{ $d['text'] }}</p>@endif
<div class="grid gap-8 md:grid-cols-2">
    @if (! empty($d['show_form']))
        <div class="site-card">
            @if (session('site_contact_sent'))
                <p class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800" role="status">Thank you. Your message has been sent.</p>
            @else
                <form method="POST" action="{{ route('site.contact', $site) }}" class="space-y-3 text-sm">
                    @csrf
                    <input type="hidden" name="anchor" value="{{ $anchor }}">
                    <div class="hidden" aria-hidden="true"><label>Website <input name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div><label class="label" for="c-name">Name</label><input id="c-name" class="input" name="name" value="{{ old('name') }}" required>@error('name')<p class="err">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label class="label" for="c-phone">Phone</label><input id="c-phone" class="input" name="phone" value="{{ old('phone') }}">@error('phone')<p class="err">{{ $message }}</p>@enderror</div>
                        <div><label class="label" for="c-email">Email</label><input id="c-email" class="input" type="email" name="email" value="{{ old('email') }}">@error('email')<p class="err">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label class="label" for="c-message">Message</label><textarea id="c-message" class="input" name="message" rows="4" required>{{ old('message') }}</textarea>@error('message')<p class="err">{{ $message }}</p>@enderror</div>
                    <button class="site-btn site-accent">Send message</button>
                </form>
            @endif
        </div>
    @endif
    <div class="space-y-4">
        @if (! empty($d['show_details']))
            <div class="space-y-1.5">@include('site.contact-details', ['contact' => $contact])</div>
        @endif
        @if (! empty($d['show_map']) && $mapAddress)
            <iframe class="h-64 w-full rounded-2xl border-0" title="Map" loading="lazy" src="https://www.google.com/maps?q={{ urlencode($mapAddress) }}&output=embed"></iframe>
        @endif
    </div>
</div>
