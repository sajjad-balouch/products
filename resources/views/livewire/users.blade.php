<div>
    <x-page-header title="User logins" subtitle="Staff logins. Parent logins are created from the student's profile." />
    @if ($issued)
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">This password is shown only once.</p>
            <p class="mt-1">Username <span class="font-mono">{{ $issued['username'] }}</span>, password <span class="font-mono">{{ $issued['password'] }}</span>. It must be changed at first sign-in.</p>
        </div>
    @endif
    <form wire:submit="create" class="card-pad mb-4 grid gap-3 sm:grid-cols-4">
        <x-field label="Name" name="name"><input id="name" class="input" wire:model="name"></x-field>
        <x-field label="Email" name="email"><input id="email" class="input" type="email" wire:model="email"></x-field>
        <x-field label="Role" name="role"><select id="role" class="input" wire:model="role">@foreach ($roles as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></x-field>
        <x-field label="Link to staff record" name="staff_id" hint="A teacher needs this to see their classes."><select id="staff_id" class="input" wire:model="staff_id"><option value="">None</option>@foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></x-field>
        <button class="btn btn-primary sm:col-span-4">Create login</button>
    </form>
    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach ($users as $user)
                <tr wire:key="u-{{ $user->id }}"><td>{{ $user->name }}</td><td class="font-mono text-xs">{{ $user->username }}</td><td>{{ $roles[$user->role] }}</td>
                    <td><span @class(['badge', 'badge-green' => $user->is_active, 'badge-red' => ! $user->is_active])>{{ $user->is_active ? 'Active' : 'Disabled' }}</span></td>
                    <td class="text-end whitespace-nowrap">
                        <button class="btn btn-sm" wire:click="resetPassword({{ $user->id }})" wire:confirm="Issue a new password for {{ $user->name }}?">Reset password</button>
                        @if ($user->id !== auth()->id())<button class="btn btn-sm" wire:click="toggle({{ $user->id }})">{{ $user->is_active ? 'Disable' : 'Enable' }}</button>@endif
                    </td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
