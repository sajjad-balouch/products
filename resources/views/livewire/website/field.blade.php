{{-- One schema-driven input. Needs $field, $model (wire:model path), $upload (key in $uploads) and $value. --}}
@if ($field['type'] === 'toggle')
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="{{ $model }}"> {{ $field['label'] }}</label>
@else
    <x-field :label="$field['label']" :name="$model" :hint="$field['hint'] ?? null">
        @switch ($field['type'])
            @case ('textarea')
                <textarea id="{{ $model }}" class="input" rows="3" wire:model="{{ $model }}"></textarea>
                @break
            @case ('select')
                <select id="{{ $model }}" class="input" wire:model="{{ $model }}">
                    @foreach ($field['options'] as $optionValue => $optionLabel)<option value="{{ $optionValue }}">{{ $optionLabel }}</option>@endforeach
                </select>
                @break
            @case ('color')
                <input id="{{ $model }}" class="input h-10 w-20 p-1" type="color" wire:model="{{ $model }}">
                @break
            @case ('date')
                <input id="{{ $model }}" class="input" type="date" wire:model="{{ $model }}">
                @break
            @case ('image')
                <div class="flex items-center gap-3">
                    @if ($value)
                        <img src="{{ route('site.media', $value) }}" alt="" class="h-14 w-14 rounded-lg border border-slate-200 object-cover">
                        <button type="button" class="btn btn-sm" wire:click="clearImage('{{ $model }}')">Remove</button>
                    @endif
                    <input id="{{ $model }}" class="min-w-0 text-xs" type="file" accept=".jpg,.jpeg,.png,.webp" wire:model="uploads.{{ $upload }}">
                </div>
                <div class="text-xs text-slate-500" wire:loading wire:target="uploads.{{ $upload }}">Uploading...</div>
                @error('uploads.'.$upload)<p class="err">{{ $message }}</p>@enderror
                @break
            @default
                <input id="{{ $model }}" class="input" wire:model="{{ $model }}">
        @endswitch
    </x-field>
@endif
