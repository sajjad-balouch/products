@if ($contact['phone'])<div><a class="hover:underline" href="tel:{{ preg_replace('/[^\d+]/', '', $contact['phone']) }}">{{ $contact['phone'] }}</a></div>@endif
@if ($contact['whatsapp'])<div><a class="hover:underline" href="https://wa.me/{{ preg_replace('/\D/', '', $contact['whatsapp']) }}" target="_blank" rel="noopener">WhatsApp</a></div>@endif
@if ($contact['email'])<div><a class="hover:underline" href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a></div>@endif
@if ($contact['address'] && ! $site->campuses())<div class="whitespace-pre-line">{{ $contact['address'] }}</div>@endif
