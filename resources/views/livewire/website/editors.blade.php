<div>
    <x-page-header title="Website editors" subtitle="Give someone only the parts they should manage. You always keep full control." />

    @if ($issued)
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">This password is shown only once.</p>
            <p class="mt-1">Username <span class="font-mono">{{ $issued['username'] }}</span>, password <span class="font-mono">{{ $issued['password'] }}</span>. It must be changed at first sign-in.</p>
        </div>
    @endif

    <form wire:submit="createEditor" class="card-pad mb-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
        <x-field label="Name" name="name"><input id="name" class="input" wire:model="name"></x-field>
        <x-field label="Email" name="email"><input id="email" class="input" type="email" wire:model="email"></x-field>
        <button class="btn btn-primary" wire:loading.attr="disabled">Create website login</button>
        <p class="text-xs text-slate-500 sm:col-span-3">A website login can only open the website manager. Existing teachers and accountants are listed below and can be given website work too.</p>
    </form>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Person</th><th>Role</th><th>Can manage</th><th></th></tr></thead>
            <tbody>
            @forelse ($users as $user)
                <tr wire:key="editor-{{ $user->id }}">
                    <td class="font-medium">{{ $user->name }}<div class="font-mono text-xs font-normal text-slate-500">{{ $user->username }}</div></td>
                    <td>{{ \App\Models\User::ROLES[$user->role] ?? $user->role }}</td>
                    <td>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($grants->get($user->id, collect()) as $grant)
                                <span class="badge badge-blue whitespace-normal">{{ $labels[$grant->permission] ?? $grant->permission }}</span>
                            @empty
                                <span class="text-xs text-slate-400">Nothing</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="text-end"><button class="btn btn-sm" wire:click="edit({{ $user->id }})">Change access</button></td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-slate-500">There is nobody to assign yet. Create a website login above.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($editing)
        <x-modal :title="'Access for '.$editing->name" close="$set('editingId', null)" size="lg">
            <form wire:submit="saveAccess" class="space-y-4">
                <fieldset>
                    <legend class="mb-1 text-sm font-semibold">Website-wide</legend>
                    <div class="space-y-1">
                        @foreach ($areas as $key => $label)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $key }}" wire:model="granted"> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <fieldset>
                    <legend class="mb-1 text-sm font-semibold">Only these pages</legend>
                    <div class="space-y-1">
                        @foreach ($pages as $page)
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" value="page:{{ $page->id }}" wire:model="granted"> {{ $page->title }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('editingId', null)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save access</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
