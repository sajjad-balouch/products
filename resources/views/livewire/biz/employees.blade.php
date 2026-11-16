<div>
    <x-page-header title="Employees and salesmen" subtitle="Staff, salaries, advances, and each salesman's sales and commission. · Staff, tankhwah, advance aur salesman ka commission.">
        <a class="btn" href="{{ route('biz.reports.salesmen') }}" wire:navigate><x-icon name="chart" class="h-4 w-4" /> Salesman report</a>
        <button class="btn" wire:click="openForm(null, true)"><x-icon name="user-plus" class="h-4 w-4" /> New salesman</button>
        <button class="btn btn-primary" wire:click="openForm()"><x-icon name="user-plus" class="h-4 w-4" /> New employee</button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-stat label="Monthly salaries · Mahana tankhwah" :value="rs($payroll)" icon="wallet" />
        <x-stat label="Employees" :value="number_format($employees->where('is_active', true)->count())" icon="users" />
        <x-stat label="Salesmen" :value="number_format($employees->where('is_active', true)->where('is_salesman', true)->count())" icon="trend" />
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Employee</th><th>Job</th><th class="text-end">Salary</th><th class="text-end">Paid this month</th><th class="text-end">Advance owed</th><th class="text-end">Sales this month</th><th class="text-end">Commission earned</th><th></th></tr></thead>
            <tbody>
            @forelse ($employees as $e)
                @php($m = $month[$e->id])
                <tr wire:key="e-{{ $e->id }}" @class(['opacity-60' => ! $e->is_active])>
                    <td>
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$e->name" />
                            <div class="min-w-0">
                                <a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.employee', $e) }}" wire:navigate>{{ $e->name }}</a>
                                @if ($e->is_salesman)<span class="badge badge-green ms-1">Salesman</span>@endif
                                <div class="text-xs text-slate-500">{{ $e->code }}{{ $e->phone ? ' · '.$e->phone : '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>{{ $e->designation }}</td>
                    <td class="text-end">{{ rs($e->salary) }}</td>
                    <td class="text-end">{{ $m['paid'] ? rs($m['paid']) : '—' }}</td>
                    <td @class(['text-end', 'font-semibold text-amber-700' => $m['advance'] > 0])>{{ $m['advance'] ? rs($m['advance']) : '—' }}</td>
                    <td class="text-end">{{ $m['sales'] ? rs($m['sales']['total']) : '—' }}</td>
                    <td class="text-end">{{ $m['sales'] && $e->commission_percent ? rs($m['sales']['commission']).' ('.rtrim(rtrim(number_format($e->commission_percent, 2), '0'), '.').'%)' : '—' }}</td>
                    <td class="text-end whitespace-nowrap">
                        <a class="btn btn-sm" href="{{ route('biz.employee', $e) }}" wire:navigate>Open · Kholein</a>
                        <button class="btn btn-sm" wire:click="openForm({{ $e->id }})" aria-label="Edit"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-6 text-center text-slate-500">No employees yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit employee' : ($form['is_salesman'] ? 'New salesman' : 'New employee')" close="$set('showForm', false)" size="lg">
            <form wire:submit="save" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-field label="Name · Naam" name="form.name"><input id="form.name" class="input" wire:model="form.name"></x-field>
                    <x-field label="Father's name" name="form.father_name"><input id="form.father_name" class="input" wire:model="form.father_name"></x-field>
                    <x-field label="Phone" name="form.phone"><input id="form.phone" class="input" type="tel" wire:model="form.phone"></x-field>
                    <x-field label="CNIC" name="form.cnic"><input id="form.cnic" class="input" wire:model="form.cnic" inputmode="numeric"></x-field>
                    <x-field label="Job · Kaam" name="form.designation"><input id="form.designation" class="input" wire:model="form.designation" placeholder="Salesman, helper, accountant"></x-field>
                    <x-field label="Joined on" name="form.joined_on"><input id="form.joined_on" type="date" class="input" wire:model="form.joined_on"></x-field>
                    <x-field label="Monthly salary (Rs) · Tankhwah" name="form.salary"><input id="form.salary" type="number" min="0" class="input" wire:model="form.salary"></x-field>
                    <label class="flex items-center gap-2 self-end pb-2 text-sm font-medium"><input type="checkbox" wire:model.live="form.is_salesman"> Salesman (sells) · Bechta hai</label>
                    @if ($form['is_salesman'])
                        <x-field label="Commission % on sales" name="form.commission_percent" hint="0 if none"><input id="form.commission_percent" type="number" step="0.01" min="0" max="100" class="input" wire:model="form.commission_percent"></x-field>
                    @endif
                    <x-field label="Address" name="form.address" class="sm:col-span-2"><input id="form.address" class="input" wire:model="form.address"></x-field>
                    <x-field label="Notes" name="form.notes"><input id="form.notes" class="input" wire:model="form.notes"></x-field>
                </div>
                @if ($editingId)<label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="form.is_active"> Still working here</label>@endif
                <x-biz.guide key="employee" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
