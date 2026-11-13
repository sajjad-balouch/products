<div class="mx-auto max-w-3xl space-y-4">
<form wire:submit="save" class="space-y-4">
    <x-page-header title="Settings"><button class="btn btn-primary">Save</button></x-page-header>
    <div class="card-pad grid gap-3 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">Institution</h2>
        <x-field label="Name" name="form.name"><input class="input" wire:model="form.name"></x-field>
        <x-field label="Principal" name="form.principal_name"><input class="input" wire:model="form.principal_name"></x-field>
        <x-field label="Phone" name="form.phone"><input class="input" wire:model="form.phone"></x-field>
        <x-field label="Email" name="form.email"><input class="input" type="email" wire:model="form.email"></x-field>
        <x-field label="Address" name="form.address" class="sm:col-span-2"><input class="input" wire:model="form.address"></x-field>
    </div>
    <div class="card-pad grid gap-3 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">Fee rules</h2>
        <x-field label="Fee due on day of month" name="form.fee_due_day"><input class="input" type="number" wire:model="form.fee_due_day"></x-field>
        <x-field label="Sibling discount (%)" name="form.sibling_discount_percent" hint="Applied from the second child onward."><input class="input" type="number" wire:model="form.sibling_discount_percent"></x-field>
        <x-field label="Late fee per day (Rs)" name="form.late_fee_per_day" hint="0 turns late fee off."><input class="input" type="number" wire:model="form.late_fee_per_day"></x-field>
        <x-field label="Maximum late fee per voucher (Rs)" name="form.late_fee_max" hint="0 means no limit."><input class="input" type="number" wire:model="form.late_fee_max"></x-field>
    </div>
    <div class="card-pad grid gap-3 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">How parents can pay</h2>
        <x-field label="JazzCash number" name="form.jazzcash_number"><input class="input" wire:model="form.jazzcash_number"></x-field>
        <x-field label="Easypaisa number" name="form.easypaisa_number"><input class="input" wire:model="form.easypaisa_number"></x-field>
        <x-field label="Bank account details" name="form.bank_details" class="sm:col-span-2" hint="Printed on vouchers and shown in the parent portal."><textarea class="input" rows="3" wire:model="form.bank_details"></textarea></x-field>
    </div>
    <div class="card-pad grid gap-3 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">Alerts</h2>
        <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" wire:model="form.absent_alerts"> Add a parent message to the outbox when a student is marked absent</label>
        <x-field label="Flag attendance below (%)" name="form.risk_attendance_threshold"><input class="input" type="number" wire:model="form.risk_attendance_threshold"></x-field>
    </div>
    <div class="card-pad grid gap-3 sm:grid-cols-2">
        <h2 class="font-semibold sm:col-span-2">Staff attendance</h2>
        <x-field label="Duty starts at" name="form.staff_starts_at" hint="Staff with their own hours, such as guards, are set on their staff record."><input class="input" type="time" wire:model="form.staff_starts_at"></x-field>
        <x-field label="Duty ends at" name="form.staff_ends_at"><input class="input" type="time" wire:model="form.staff_ends_at"></x-field>
        <x-field label="Minutes allowed before someone is late" name="form.staff_late_minutes"><input class="input" type="number" min="0" wire:model="form.staff_late_minutes"></x-field>
        <x-field label="Lates that count as one absent day" name="form.lates_per_absent" hint="Taken off pay in payroll. 0 means lates never cut pay."><input class="input" type="number" min="0" wire:model="form.lates_per_absent"></x-field>
        <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" wire:model="form.staff_self_check_in"> Let staff with a login check themselves in and out from their dashboard</label>
    </div>
    <div class="card-pad grid gap-3 sm:grid-cols-2" x-data="{ locating: false, problem: '' }">
        <h2 class="font-semibold sm:col-span-2">Strict attendance</h2>
        <label class="flex items-start gap-2 text-sm sm:col-span-2">
            <input type="checkbox" class="mt-1" wire:model="form.strict_attendance">
            <span>Staff can only mark attendance at school. Checking in from a phone works only on the school network or inside the school grounds, attendance machines are only accepted from the school network, and times from a machine or a check-in cannot be changed on the attendance sheet. Every change is kept in the audit log.</span>
        </label>
        <x-field label="School internet addresses" name="form.check_in_networks" class="sm:col-span-2" hint="One per line or separated by commas. A range like 39.45.12.0/24 is fine.">
            <textarea id="form.check_in_networks" class="input font-mono" rows="2" wire:model="form.check_in_networks" placeholder="39.45.12.7"></textarea>
            <p class="mt-1 text-xs text-slate-600">
                This computer is using <span class="font-mono font-semibold">{{ request()->ip() }}</span>.
                If you are at school on the school Wi-Fi now, <button type="button" class="text-brand-600 underline" wire:click="addThisNetwork">add it</button>.
            </p>
        </x-field>
        <x-field label="School latitude" name="form.check_in_lat"><input id="form.check_in_lat" class="input" inputmode="decimal" wire:model="form.check_in_lat"></x-field>
        <x-field label="School longitude" name="form.check_in_lng"><input id="form.check_in_lng" class="input" inputmode="decimal" wire:model="form.check_in_lng"></x-field>
        <x-field label="Allowed distance from that point (metres)" name="form.check_in_radius"><input id="form.check_in_radius" class="input" type="number" min="30" wire:model="form.check_in_radius"></x-field>
        <div class="text-sm sm:pt-6">
            <button type="button" class="btn btn-sm" :disabled="locating"
                    @click="locating = true; problem = ''; navigator.geolocation ? navigator.geolocation.getCurrentPosition(p => { $wire.set('form.check_in_lat', p.coords.latitude.toFixed(7)); $wire.set('form.check_in_lng', p.coords.longitude.toFixed(7)); locating = false }, () => { problem = 'Location was not allowed.'; locating = false }, { enableHighAccuracy: true, timeout: 15000 }) : (problem = 'This browser cannot share location.', locating = false)">
                <span x-text="locating ? 'Finding you...' : 'Use my current location'"></span>
            </button>
            <p class="err" x-show="problem" x-text="problem"></p>
            <p class="mt-1 text-xs text-slate-500">Press this while standing at school.</p>
        </div>
    </div>
</form>
<livewire:reminder-settings />
</div>
