<div>
    <x-page-header title="Admissions pipeline" :subtitle="$conversion.'% of inquiries have become admissions.'">
        <a class="btn" href="{{ $publicLink }}" target="_blank">Open online form</a>
        <button class="btn btn-primary" wire:click="$set('showForm', true)">Add inquiry</button>
    </x-page-header>
    <p class="mb-4 text-sm text-slate-500 no-print">Share this link on Facebook or WhatsApp. Applications arrive here as new inquiries: <span class="font-mono text-xs break-all">{{ $publicLink }}</span></p>

    <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($stages as $stage => $label)
            <div class="rounded-xl bg-slate-200/60 p-2">
                <div class="mb-2 flex justify-between px-1 text-xs font-semibold text-slate-600"><span>{{ $label }}</span><span>{{ $inquiries->get($stage, collect())->count() }}</span></div>
                <div class="space-y-2">
                    @foreach ($inquiries->get($stage, collect()) as $inquiry)
                        <div class="rounded-lg bg-white p-2.5 text-sm shadow-sm" wire:key="i-{{ $inquiry->id }}">
                            <div class="font-medium">{{ $inquiry->student_name }}</div>
                            <div class="text-xs text-slate-500">{{ $inquiry->schoolClass?->label }} · {{ $inquiry->source }}</div>
                            <a class="text-xs text-brand-600 underline" href="https://wa.me/{{ \App\Support\Phone::international($inquiry->phone) }}" target="_blank" rel="noopener">{{ $inquiry->phone }}</a>
                            @if ($inquiry->notes)<p class="mt-1 line-clamp-3 text-xs text-slate-500">{{ $inquiry->notes }}</p>@endif
                            @if ($inquiry->follow_up_on)
                                <div @class(['mt-1 text-xs', 'font-medium text-red-700' => $inquiry->follow_up_on->isPast() && ! in_array($stage, ['admitted', 'lost']), 'text-slate-500' => ! $inquiry->follow_up_on->isPast()])>Follow up {{ $inquiry->follow_up_on->format('j M') }}</div>
                            @endif
                            <select class="input mt-2 py-1! text-xs!" aria-label="Move {{ $inquiry->student_name }}" wire:change="move({{ $inquiry->id }}, $event.target.value)">
                                @foreach ($stages as $value => $text)<option value="{{ $value }}" @selected($value === $stage)>{{ $text }}</option>@endforeach
                            </select>
                            @if (! $inquiry->student_id && ! in_array($stage, ['lost']))
                                <a class="btn btn-sm mt-1 w-full" href="{{ route('students.form', ['inquiry' => $inquiry->id]) }}" wire:navigate>Admit</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @if ($showForm)
        <x-modal title="Add inquiry" close="$set('showForm', false)">
            <form wire:submit="save" class="space-y-3">
                <x-field label="Student name" name="student_name"><input id="student_name" class="input" wire:model="student_name"></x-field>
                <x-field label="Guardian name" name="guardian_name"><input id="guardian_name" class="input" wire:model="guardian_name"></x-field>
                <x-field label="Phone" name="phone"><input id="phone" class="input" wire:model="phone"></x-field>
                <x-field label="Class" name="school_class_id">
                    <select id="school_class_id" class="input" wire:model="school_class_id"><option value="">Select</option>@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach</select>
                </x-field>
                <x-field label="Source" name="source">
                    <select id="source" class="input" wire:model="source">@foreach (['walk-in', 'phone', 'facebook', 'whatsapp', 'referral', 'website'] as $s)<option value="{{ $s }}">{{ ucfirst($s) }}</option>@endforeach</select>
                </x-field>
                <x-field label="Follow up on" name="follow_up_on"><input id="follow_up_on" class="input" type="date" wire:model="follow_up_on"></x-field>
                <x-field label="Notes" name="notes"><textarea id="notes" class="input" rows="2" wire:model="notes"></textarea></x-field>
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button><button class="btn btn-primary">Save</button></div>
            </form>
        </x-modal>
    @endif
</div>
