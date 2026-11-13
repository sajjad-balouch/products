<div>
    @include('cards.styles')
    <x-page-header title="ID cards" subtitle="Real bank-card size (85.6 × 54 mm). Choose a design, add photos, then print one card or a whole class on A4 or a PVC card printer, or download a PDF.">
        <div class="flex rounded-lg border border-slate-300 bg-white p-0.5">
            @foreach (\App\Support\IdCards::TYPES as $key => $label)
                <button type="button" wire:click="$set('type', '{{ $key }}')" @class(['flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $type === $key, 'text-slate-600' => $type !== $key])>
                    <x-icon :name="$key === 'student' ? 'id-card' : 'users'" class="h-4 w-4" /> {{ $label }}
                </button>
            @endforeach
        </div>
    </x-page-header>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_25rem]">
        <div class="min-w-0 space-y-5">
            {{-- 1. Design --}}
            <section class="card-pad">
                <h2 class="panel-title"><span class="icon-chip tone-violet h-7! w-7! text-xs font-bold">1</span> Choose a design</h2>
                <div class="mt-4 flex gap-3 overflow-x-auto pb-2">
                    @foreach (\App\Support\IdCards::TEMPLATES as $key => [$name, $orientation, $about])
                        <button type="button" wire:click="$set('template', '{{ $key }}')" wire:key="tpl-{{ $key }}"
                                @class(['flex shrink-0 flex-col items-center gap-2 rounded-xl border-2 p-3 text-center transition', 'border-brand-500 bg-brand-50/60' => $template === $key, 'border-transparent hover:border-slate-200 hover:bg-slate-50' => $template !== $key])>
                            <div class="grid h-[33mm] place-items-center">
                                @if ($card)
                                    @include('cards.scaled', ['s' => ['template' => $key] + $settings, 'side' => 'front', 'scale' => $orientation === 'landscape' ? 0.42 : 0.38, 'logo' => $assets['logo'], 'sign' => $assets['sign']])
                                @endif
                            </div>
                            <div>
                                <div class="flex items-center justify-center gap-1 text-sm font-semibold">@if ($template === $key)<x-icon name="check-circle" class="h-4 w-4 text-brand-600" />@endif {{ $name }}</div>
                                <div class="text-[11px] text-slate-500">{{ ucfirst($orientation) }}</div>
                            </div>
                        </button>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ $templateInfo[2] }}</p>

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div>
                        <div class="label">Colours</div>
                        <div class="flex flex-wrap items-center gap-3">
                            <label class="flex items-center gap-2 text-sm"><input type="color" class="h-9 w-12 cursor-pointer rounded border border-slate-300" wire:model.live.debounce.400ms="primary"> Main</label>
                            <label class="flex items-center gap-2 text-sm"><input type="color" class="h-9 w-12 cursor-pointer rounded border border-slate-300" wire:model.live.debounce.400ms="accent"> Accent</label>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-1.5" aria-label="Ready colour pairs">
                            @foreach ([['#0e6a5a', '#d9822b'], ['#1e3a8a', '#f59e0b'], ['#7f1d1d', '#d4a017'], ['#4c1d95', '#ec4899'], ['#0f172a', '#22c55e'], ['#065f46', '#0ea5e9'], ['#9a3412', '#1e293b']] as [$p, $a])
                                <button type="button" title="Use these colours" wire:click="$set('primary', '{{ $p }}'); $set('accent', '{{ $a }}')" class="flex h-7 w-11 overflow-hidden rounded-md ring-1 ring-slate-300 hover:ring-2 hover:ring-brand-500">
                                    <span class="flex-1" style="background: {{ $p }}"></span><span class="w-3" style="background: {{ $a }}"></span>
                                </button>
                            @endforeach
                        </div>
                        @error('primary')<p class="err">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <x-field label="Valid till" name="validUntil"><input id="validUntil" type="date" class="input" wire:model.live="validUntil"></x-field>
                        <div class="space-y-2 pt-6 text-sm">
                            <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="showBack"> Print the back side</label>
                            <label class="flex items-center gap-2"><input type="checkbox" wire:model.live="showQr"> QR code</label>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach (['logo' => ['School logo', 'logo_path', $assets['logo']], 'signature' => ["Principal's signature", 'signature_path', $assets['sign']]] as $prop => [$label, $key, $url])
                            <div>
                                <div class="label">{{ $label }}</div>
                                <div class="flex items-center gap-2">
                                    <div class="grid h-12 w-16 shrink-0 place-items-center overflow-hidden rounded-lg border border-dashed border-slate-300 bg-slate-50">
                                        @if ($url)<img src="{{ $url }}" alt="" class="max-h-full max-w-full object-contain">@else<x-icon name="image" class="h-5 w-5 text-slate-400" />@endif
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <label class="btn btn-sm cursor-pointer"><x-icon name="upload" class="h-3.5 w-3.5" /> {{ $url ? 'Change' : 'Upload' }}<input type="file" class="sr-only" accept="image/png,image/jpeg,image/webp" wire:model="{{ $prop }}"></label>
                                        @if (($settings[$key] ?? null))<button type="button" class="text-xs text-red-600 hover:underline" wire:click="removeImage('{{ $key }}')">Remove</button>@endif
                                    </div>
                                </div>
                                <div wire:loading wire:target="{{ $prop }}" class="text-xs text-slate-500">Uploading…</div>
                                @error($prop)<p class="err">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                        <p class="col-span-2 text-xs text-slate-500">No logo uploaded? The website logo is used. A signature on white or see-through (PNG) looks best.</p>
                    </div>
                    <x-field :label="$type === 'guardian' ? 'Back side note (parent cards)' : 'Back side note (student cards)'" :name="$type === 'guardian' ? 'guardianNote' : 'backNote'">
                        @if ($type === 'guardian')
                            <textarea id="guardianNote" class="input" rows="3" maxlength="200" wire:model.live.debounce.600ms="guardianNote"></textarea>
                        @else
                            <textarea id="backNote" class="input" rows="3" maxlength="200" wire:model.live.debounce.600ms="backNote"></textarea>
                        @endif
                    </x-field>
                </div>
            </section>

            {{-- 2. People --}}
            <section class="card">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4">
                    <h2 class="panel-title"><span class="icon-chip tone-blue h-7! w-7! text-xs font-bold">2</span> Who gets a card</h2>
                    <div class="flex flex-wrap items-center gap-2">
                        <select class="input w-auto" wire:model.live="section" aria-label="Class">
                            <option value="">Whole school</option>
                            @foreach ($sections as $item)<option value="{{ $item->id }}">{{ $item->label }}</option>@endforeach
                        </select>
                        <input class="input w-48" type="search" placeholder="{{ $type === 'guardian' ? 'Name or phone' : 'Name or adm. no.' }}" wire:model.live.debounce.300ms="search" aria-label="Search">
                    </div>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 px-4 py-2 text-sm">
                    <span><strong>{{ $chosenCount }}</strong> of {{ $people->count() }} chosen @if ($missingPhotos)<span class="ms-2 text-amber-700"><x-icon name="image" class="inline h-4 w-4" /> {{ $missingPhotos }} without a photo</span>@endif</span>
                    <span class="flex gap-3"><button type="button" class="text-brand-600 hover:underline" wire:click="selectAll">Choose all</button><button type="button" class="text-brand-600 hover:underline" wire:click="selectNone">Choose none</button></span>
                </div>
                @error('photo')<p class="err px-4 pt-2">{{ $message }}</p>@enderror
                <ul class="max-h-[30rem] divide-y divide-slate-100 overflow-y-auto">
                    @forelse ($people as $person)
                        @php($key = $person->cardKey())
                        @php($kind = strtok($key, ':'))
                        @php($extra = $person instanceof \App\Models\PickupPerson)
                        @php($on = ! in_array($key, $excluded, true))
                        <li wire:key="p-{{ $key }}" @class(['flex items-center gap-3 py-2 pe-4', 'ps-4' => ! $extra, 'ps-10 bg-slate-50/60' => $extra, 'bg-brand-50/50!' => $card && $previewKey === $key])>
                            <input type="checkbox" @checked($on) wire:click="toggle('{{ $key }}')" aria-label="Choose {{ $person->name }}">
                            <div class="relative h-11 w-9 shrink-0 overflow-hidden rounded-md bg-slate-100">
                                @if ($person->photo_path)
                                    <img src="{{ route('cards.photo', [$kind, $person->id, 'v' => substr(md5($person->photo_path), 0, 8)]) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    <div class="grid h-full place-items-center text-[11px] font-bold text-slate-400">{{ \App\Support\IdCards::initials($person->name) }}</div>
                                @endif
                            </div>
                            <button type="button" class="min-w-0 flex-1 text-start" wire:click="$set('previewKey', '{{ $key }}')" title="Show on the preview">
                                <div class="flex items-center gap-1.5">
                                    <span class="truncate text-sm font-medium">{{ $person->name }}</span>
                                    @if ($type === 'guardian')<span @class(['badge shrink-0', 'badge-slate' => ! $extra, 'badge-amber' => $extra])>{{ $person->relation ?: 'Main guardian' }}</span>@endif
                                </div>
                                <div class="truncate text-xs text-slate-500">
                                    @if ($extra)
                                        {{ $person->phone ? $person->phone.' · ' : '' }}Allowed by {{ $person->guardian->name }}
                                    @elseif ($type === 'guardian')
                                        {{ $person->phone }} · {{ $person->students->pluck('name')->implode(', ') }}
                                    @else
                                        {{ $person->section?->label }} · {{ $person->admission_no }}
                                    @endif
                                </div>
                            </button>
                            <div class="flex shrink-0 items-center gap-1">
                                @if ($type === 'guardian')
                                    @unless ($extra)
                                        <button type="button" class="btn btn-sm" wire:click="openPerson('new:{{ $person->id }}')" title="Add someone who may pick up these children"><x-icon name="user-plus" class="h-3.5 w-3.5" /><span class="hidden sm:inline">Person</span></button>
                                    @endunless
                                    <button type="button" class="btn btn-sm" wire:click="openPerson('{{ $key }}')" title="Edit"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                                    @if ($extra)<button type="button" class="btn btn-sm" wire:click="deletePerson({{ $person->id }})" wire:confirm="Remove {{ $person->name }}? They will no longer have a pickup card." title="Remove"><x-icon name="x-circle" class="h-3.5 w-3.5 text-red-600" /></button>@endif
                                @endif
                                <label class="btn btn-sm cursor-pointer" title="{{ $person->photo_path ? 'Change photo' : 'Add photo' }}">
                                    <x-icon name="image" class="h-3.5 w-3.5" /><span class="hidden sm:inline">{{ $person->photo_path ? 'Change' : 'Photo' }}</span>
                                    <input type="file" class="sr-only" accept="image/png,image/jpeg,image/webp" x-on:click="$wire.photoFor = '{{ $key }}'" wire:model="photo">
                                </label>
                                @if ($person->photo_path)<button type="button" class="btn btn-sm" title="Remove photo" wire:click="removePhoto('{{ $key }}')" wire:confirm="Remove this photo?"><x-icon name="x-circle" class="h-3.5 w-3.5" /></button>@endif
                                <a class="btn btn-sm" title="Print only this card" target="_blank" href="{{ route('print.cards', ['type' => $type, 'layout' => 'card', 'only' => $key]) }}"><x-icon name="file-plus" class="h-3.5 w-3.5" /></a>
                            </div>
                        </li>
                    @empty
                        <li class="p-6 text-center text-sm text-slate-500">{{ $type === 'guardian' ? 'No parents found.' : 'No students found.' }}</li>
                    @endforelse
                </ul>
                <div wire:loading wire:target="photo" class="border-t border-slate-100 px-4 py-2 text-xs text-slate-500">Uploading photo…</div>
            </section>
        </div>

        {{-- Preview and output --}}
        <aside class="space-y-4 xl:sticky xl:top-20 xl:self-start">
            <section class="card-pad">
                <h2 class="panel-title"><x-icon name="sparkles" class="h-5 w-5 text-accent-500" /> Preview</h2>
                @if ($card)
                    @php($land = $templateInfo[1] === 'landscape')
                    <div @class(['mt-4 flex flex-wrap justify-center gap-4', 'flex-col items-center' => $land])>
                        @include('cards.scaled', ['s' => $settings, 'side' => 'front', 'scale' => $land ? 1.02 : 1.25, 'logo' => $assets['logo'], 'sign' => $assets['sign']])
                        @if ($showBack)
                            @include('cards.scaled', ['s' => $settings, 'side' => 'back', 'scale' => $land ? 1.02 : 1.25, 'logo' => $assets['logo'], 'sign' => $assets['sign']])
                        @endif
                    </div>
                    <p class="mt-3 text-center text-xs text-slate-500">Front{{ $showBack ? ' and back' : '' }} of {{ $card['name'] }}. Click a name in the list to see their card.</p>
                @else
                    <p class="mt-3 text-sm text-slate-500">Nobody to show yet.</p>
                @endif
            </section>

            <form method="GET" action="{{ route('print.cards') }}" target="_blank" class="card-pad space-y-3">
                <h2 class="panel-title"><x-icon name="file-plus" class="h-5 w-5 text-brand-600" /> Print or download {{ $chosenCount }} {{ str('card')->plural($chosenCount) }}</h2>
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="section" value="{{ $section }}">
                <input type="hidden" name="search" value="{{ $search }}">
                <input type="hidden" name="excluded" value="{{ implode(',', $excluded) }}">
                <div class="grid grid-cols-2 gap-2">
                    <button name="layout" value="a4" class="btn btn-primary flex-col py-3" @disabled(! $chosenCount)><x-icon name="layers" class="h-5 w-5" /> A4 sheets<span class="text-[11px] font-normal opacity-80">{{ $land ?? false ? '10' : '9' }} cards per page</span></button>
                    <button name="layout" value="card" class="btn flex-col py-3" @disabled(! $chosenCount)><x-icon name="card" class="h-5 w-5" /> Card printer<span class="text-[11px] font-normal text-slate-500">PVC, one per card</span></button>
                </div>
                <button name="download" value="a4" class="btn w-full" @disabled(! $chosenCount)><x-icon name="arrow-right" class="h-4 w-4 rotate-90" /> Download PDF (A4 sheets)</button>
                <button name="download" value="card" class="btn w-full" @disabled(! $chosenCount)><x-icon name="arrow-right" class="h-4 w-4 rotate-90" /> Download PDF (single cards)</button>
                <p class="text-xs text-slate-500">When printing, choose Margins: None, Scale: 100% and turn on Background graphics, so the cards come out at their exact size.</p>
            </form>
        </aside>
    </div>

    @if ($personTarget)
        @php($isMain = str_starts_with($personTarget, 'guardian:'))
        @php($family = \App\Models\Guardian::find((int) \Illuminate\Support\Str::after($personTarget, ':')))
        <x-modal :title="$isMain ? 'Main guardian' : (str_starts_with($personTarget, 'new:') ? 'Add someone who may pick up' : 'Edit pickup person')" close="$set('personTarget', null)">
            <form wire:submit="savePerson" class="space-y-4">
                @if ($isMain)
                    <p class="text-sm text-slate-600"><strong>{{ $family?->name }}</strong> · {{ $family?->phone }}. The name and phone are changed from the student's record, since they link brothers and sisters.</p>
                @elseif (str_starts_with($personTarget, 'new:'))
                    <p class="rounded-lg bg-amber-50 p-2.5 text-sm text-amber-900 ring-1 ring-amber-200">They get their own card, showing the children of <strong>{{ $family?->name }}</strong>'s family. Add only people the family has told the school about.</p>
                @endif
                @unless ($isMain)
                    <x-field label="Full name" name="personName"><input id="personName" class="input" wire:model="personName" autocomplete="off"></x-field>
                @endunless
                <x-field label="Relation to the children" name="personRelation">
                    <select id="personRelation" class="input" wire:model="personRelation">
                        <option value="">Choose…</option>
                        @foreach (\App\Models\PickupPerson::RELATIONS as $relation)<option value="{{ $relation }}">{{ $relation }}</option>@endforeach
                    </select>
                </x-field>
                <div class="grid gap-3 sm:grid-cols-2">
                    @unless ($isMain)
                        <x-field label="Phone" name="personPhone"><input id="personPhone" class="input" type="tel" wire:model="personPhone"></x-field>
                    @endunless
                    <x-field label="CNIC" name="personCnic" hint="Optional. 36302-1234567-1"><input id="personCnic" class="input" wire:model="personCnic" inputmode="numeric"></x-field>
                </div>
                <p class="text-xs text-slate-500">Add a photo from the list after saving, with the Photo button next to their name.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('personTarget', null)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
