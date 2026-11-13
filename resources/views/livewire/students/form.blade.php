<form wire:submit="save" class="mx-auto max-w-3xl space-y-4">
    <x-page-header :title="$student ? 'Edit '.$student->name : 'New student'" />

    <div class="card-pad">
        <h2 class="mb-3 font-semibold">Parent or guardian</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <x-field label="Mobile number" name="phone" hint="Children with the same number share one family fee voucher.">
                <input id="phone" class="input" wire:model.live.debounce.500ms="phone" inputmode="tel" placeholder="03001234567">
            </x-field>
            <x-field label="Guardian name" name="guardian_name"><input id="guardian_name" class="input" wire:model="guardian_name"></x-field>
            <x-field label="CNIC" name="cnic"><input id="cnic" class="input" wire:model="cnic"></x-field>
            <x-field label="Address" name="address"><input id="address" class="input" wire:model="address"></x-field>
        </div>
        @if ($this->existingFamily)
            <p class="mt-3 rounded-lg bg-sky-50 p-3 text-sm text-sky-900">
                This number belongs to {{ $this->existingFamily->name }}, with {{ $this->existingFamily->students->pluck('name')->join(', ') ?: 'no students yet' }}. This student will join that family.
            </p>
        @endif
    </div>

    <div class="card-pad">
        <h2 class="mb-3 font-semibold">Student</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <x-field label="Name" name="name"><input id="name" class="input" wire:model="name"></x-field>
            <x-field label="Father's name" name="father_name"><input id="father_name" class="input" wire:model="father_name"></x-field>
            <x-field label="Gender" name="gender">
                <select id="gender" class="input" wire:model="gender"><option value="male">Male</option><option value="female">Female</option></select>
            </x-field>
            <x-field label="Date of birth" name="dob"><input id="dob" class="input" type="date" wire:model="dob"></x-field>
            <x-field label="B-Form number" name="b_form"><input id="b_form" class="input" wire:model="b_form"></x-field>
            <x-field label="Class and section" name="section_id">
                <select id="section_id" class="input" wire:model="section_id">
                    <option value="">Select</option>
                    @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Roll number" name="roll_no"><input id="roll_no" class="input" wire:model="roll_no"></x-field>
            <x-field label="Transport route" name="transport_route_id">
                <select id="transport_route_id" class="input" wire:model="transport_route_id">
                    <option value="">No transport</option>
                    @foreach ($routes as $r)<option value="{{ $r->id }}">{{ $r->name }} (Rs {{ number_format($r->monthly_fee) }})</option>@endforeach
                </select>
            </x-field>
            <x-field label="Monthly concession (Rs)" name="monthly_concession"><input id="monthly_concession" class="input" type="number" min="0" wire:model="monthly_concession"></x-field>
            <x-field label="Concession reason" name="concession_reason"><input id="concession_reason" class="input" wire:model="concession_reason" placeholder="Scholarship, staff child..."></x-field>
            @if ($student)
                <x-field label="Status" name="status">
                    <select id="status" class="input" wire:model="status"><option value="active">Active</option><option value="left">Left</option><option value="graduated">Graduated</option></select>
                </x-field>
            @endif
        </div>
    </div>

    <div class="flex justify-end gap-2">
        <a class="btn" href="{{ $student ? route('students.show', $student) : route('students.index') }}" wire:navigate>Cancel</a>
        <button class="btn btn-primary" wire:loading.attr="disabled">Save student</button>
    </div>
</form>
