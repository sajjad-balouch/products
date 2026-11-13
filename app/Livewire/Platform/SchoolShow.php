<?php

namespace App\Livewire\Platform;

use App\Livewire\Platform\Concerns\OwnerOnly;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\School;
use App\Models\Section;
use App\Models\Site;
use App\Models\Staff;
use App\Models\Student;
use App\Models\SubscriptionEntry;
use App\Models\User;
use App\Services\SchoolEraser;
use App\Services\SiteBuilder;
use App\Services\SubscriptionService;
use App\Support\Impersonation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** One customer school, everything the owner can control about it. */
#[Title('School')]
class SchoolShow extends Component
{
    use OwnerOnly;

    public const TABS = ['overview' => 'Overview', 'billing' => 'Fee and access', 'features' => 'Features', 'users' => 'Logins', 'website' => 'Website'];

    public School $school;

    #[Url]
    public string $tab = 'overview';

    // Details
    public array $details = [];

    // Fee settings
    public string $monthlyFee = '';

    public string $graceDays = '7';

    public bool $lockWhenDue = true;

    // Free months
    public string $freeMonths = '1';

    public string $freeNote = '';

    // Payment
    public string $amount = '';

    public string $months = '1';

    public string $method = 'cash';

    public string $paidOn = '';

    public string $reference = '';

    public string $payNote = '';

    // Date by hand
    public string $manualDate = '';

    public string $manualNote = '';

    public array $features = [];

    // Plan
    public string $planCode = '';

    public bool $keepFee = false;

    public ?array $issued = null;

    // Set a login's password
    public ?int $passwordUserId = null;

    public string $passwordMode = 'type';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public bool $mustChange = false;

    // Delete the school
    public bool $showDelete = false;

    public string $confirmName = '';

    public string $ownerPassword = '';

    public function mount(School $school): void
    {
        $this->school = $school;
        $this->tab = isset(self::TABS[$this->tab]) ? $this->tab : 'overview';
        $this->load();
    }

    private function load(): void
    {
        $s = $this->school->refresh();
        $this->details = ['name' => $s->name, 'phone' => (string) $s->phone, 'email' => (string) $s->email, 'address' => (string) $s->address, 'owner_note' => (string) $s->owner_note];
        $this->monthlyFee = (string) $s->monthly_fee;
        $this->graceDays = (string) $s->grace_days;
        $this->lockWhenDue = $s->lock_when_due;
        $this->amount = (string) $s->monthly_fee;
        $this->paidOn = today()->toDateString();
        $this->manualDate = (string) $s->accessUntil()?->toDateString();
        $this->features = array_values(array_diff(array_keys(School::featuresFor($s->product())), $s->features_off ?? []));
        $this->planCode = $s->plan;
    }

    public function applyPlan(): void
    {
        // A school can only be put on a school plan, and a business on a business plan.
        $this->validate(['planCode' => ['required', Rule::exists('plans', 'code')->where('product', $this->school->product())]], [], ['planCode' => 'plan']);

        $plan = Plan::where('code', $this->planCode)->firstOrFail();
        $plan->applyTo($this->school, $this->keepFee);
        $this->load();
        $this->dispatch('toast', message: "Moved to the {$plan->name} plan. Fee and features updated.");
    }

    public function saveDetails(): void
    {
        $this->validate([
            'details.name' => 'required|string|max:150',
            'details.phone' => 'nullable|string|max:30',
            'details.email' => 'nullable|email|max:150',
            'details.address' => 'nullable|string|max:255',
            'details.owner_note' => 'nullable|string|max:2000',
        ], [], ['details.name' => 'name', 'details.email' => 'email', 'details.owner_note' => 'note']);

        $this->school->update($this->details);
        $this->dispatch('toast', message: 'Details saved.');
    }

    public function saveBilling(): void
    {
        $this->validate(['monthlyFee' => 'required|integer|min:0|max:10000000', 'graceDays' => 'required|integer|min:0|max:90']);

        $this->school->update(['monthly_fee' => (int) $this->monthlyFee, 'grace_days' => (int) $this->graceDays, 'lock_when_due' => $this->lockWhenDue]);
        $this->dispatch('toast', message: 'Fee settings saved.');
    }

    public function giveFree(SubscriptionService $service): void
    {
        $this->validate(['freeMonths' => 'required|integer|min:1|max:36', 'freeNote' => 'nullable|string|max:255']);

        $entry = $service->giveFreeMonths($this->school, (int) $this->freeMonths, $this->freeNote ?: null);
        $this->reset('freeNote');
        $this->load();
        $this->dispatch('toast', message: "{$entry->months} free ".Str::plural('month', $entry->months).' added. Access now runs until '.$entry->to_date->format('j M Y').'.');
    }

    public function recordPayment(SubscriptionService $service): void
    {
        $this->validate([
            'amount' => 'required|integer|min:1|max:100000000',
            'months' => 'required|integer|min:1|max:36',
            'method' => ['required', Rule::in(array_keys(SubscriptionEntry::METHODS))],
            'paidOn' => 'required|date|before_or_equal:today',
            'reference' => 'nullable|string|max:120',
            'payNote' => 'nullable|string|max:255',
        ], [], ['paidOn' => 'payment date', 'payNote' => 'note']);

        $entry = $service->recordPayment($this->school, (int) $this->amount, (int) $this->months, $this->method, Carbon::parse($this->paidOn), $this->reference ?: null, $this->payNote ?: null);
        $this->reset('reference', 'payNote');
        $this->months = '1';
        $this->load();
        $this->dispatch('toast', message: 'Payment of Rs '.number_format($entry->amount).' recorded. Access now runs until '.$entry->to_date->format('j M Y').'.');
    }

    public function setDate(SubscriptionService $service): void
    {
        $this->validate(['manualDate' => 'required|date', 'manualNote' => 'required|string|max:255'], ['manualNote.required' => 'Write why the date is being changed.'], ['manualDate' => 'date', 'manualNote' => 'reason']);

        $service->setAccessUntil($this->school, Carbon::parse($this->manualDate), $this->manualNote);
        $this->reset('manualNote');
        $this->load();
        $this->dispatch('toast', message: 'Access date changed.');
    }

    public function toggleActive(): void
    {
        $this->school->update(['is_active' => ! $this->school->is_active]);
        AuditLog::record($this->school->is_active ? 'school_activated' : 'school_suspended', $this->school, null, null, $this->school->name);
        $this->dispatch('toast', message: $this->school->is_active ? 'School activated.' : 'School suspended. Its users are signed out.');
    }

    public function saveFeatures(): void
    {
        $this->validate(['features.*' => [Rule::in(array_keys(School::featuresFor($this->school->product())))]]);

        $this->school->update(['features_off' => array_values(array_diff(array_keys(School::featuresFor($this->school->product())), $this->features))]);
        $this->dispatch('toast', message: 'Features saved.');
    }

    private function user(int $id): User
    {
        return User::where('school_id', $this->school->id)->findOrFail($id);
    }

    public function resetPassword(int $id): void
    {
        $user = $this->user($id);
        $password = Str::password(10, symbols: false);
        $user->update(['password' => Hash::make($password), 'must_change_password' => true]);
        AuditLog::record('password_reset', $user, null, null, $user->username.' by the owner');
        $this->issued = ['username' => $user->username, 'password' => $password];
    }

    public function openPassword(int $id): void
    {
        $this->resetValidation();
        $this->passwordUserId = $this->user($id)->id;
        $this->passwordMode = 'type';
        $this->newPassword = $this->newPassword_confirmation = '';
        $this->mustChange = false;
    }

    /** For when a principal or other staff member forgets their password. */
    public function setPassword(): void
    {
        $user = $this->user((int) $this->passwordUserId);

        if ($this->passwordMode === 'type') {
            $this->validate(['newPassword' => 'required|string|min:8|max:100|confirmed'], [], ['newPassword' => 'new password']);
            $password = $this->newPassword;
        } else {
            $password = Str::password(10, symbols: false);
        }

        $user->update(['password' => Hash::make($password), 'must_change_password' => $this->mustChange]);
        // Anyone still signed in with the old password is signed out.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        AuditLog::record('password_reset', $user, null, null, $user->username.' by the owner');

        $this->issued = ['username' => $user->username, 'password' => $password, 'name' => $user->name, 'mustChange' => $this->mustChange];
        $this->passwordUserId = null;
        $this->newPassword = $this->newPassword_confirmation = '';
    }

    public function openDelete(): void
    {
        $this->resetValidation();
        $this->confirmName = $this->ownerPassword = '';
        $this->showDelete = true;
    }

    public function deleteSchool(SchoolEraser $eraser)
    {
        $this->validate([
            'confirmName' => ['required', function ($attribute, $value, $fail) {
                if (trim($value) !== $this->school->name) {
                    $fail('Type the school name exactly as shown.');
                }
            }],
            'ownerPassword' => ['required', function ($attribute, $value, $fail) {
                if (! Hash::check($value, Auth::user()->password)) {
                    $fail('This is not your password.');
                }
            }],
        ], [], ['confirmName' => 'school name', 'ownerPassword' => 'your password']);

        $name = $this->school->name;
        $eraser->erase($this->school);
        session()->flash('toast', $name.' and all its data were deleted.');

        return $this->redirectRoute('platform.schools', navigate: true);
    }

    public function toggleUser(int $id): void
    {
        $user = $this->user($id);
        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::record($user->is_active ? 'login_enabled' : 'login_disabled', $user, null, null, $user->username.' by the owner');
    }

    public function loginAs(int $id, string $to = '')
    {
        $user = $this->user($id);
        Impersonation::start(Auth::user(), $user);

        return redirect()->route($to === 'website' ? 'website.pages' : $user->homeRoute());
    }

    private function site(): ?Site
    {
        return Site::where('owner_key', 'school:'.$this->school->id)->first();
    }

    public function createSite(SiteBuilder $builder): void
    {
        $builder->forSchool($this->school);
        $this->dispatch('toast', message: 'Website created as a draft.');
    }

    public function setSitePublished(bool $published): void
    {
        $site = $this->site();
        abort_unless($site, 404);
        $site->update(['is_published' => $published]);
        $this->dispatch('toast', message: $published ? 'Website published.' : 'Website is now a draft.');
    }

    public function useSchoolTemplate(SiteBuilder $builder): void
    {
        $site = $this->site();
        abort_unless($site, 404);
        $builder->applySchoolTemplate($site);
        $this->dispatch('toast', message: 'The complete school template has been applied.');
    }

    public function render()
    {
        $id = $this->school->id;
        $users = User::where('school_id', $id)->get();
        $site = $this->site();

        return view('livewire.platform.school-show', [
            'tabs' => self::TABS,
            'plans' => Plan::where('product', $this->school->product())->orderBy('sort')->get(),
            'stats' => [
                'Active students' => Student::withoutGlobalScopes()->where('school_id', $id)->where('status', 'active')->count(),
                'Families' => Guardian::withoutGlobalScopes()->where('school_id', $id)->count(),
                'Staff' => Staff::withoutGlobalScopes()->where('school_id', $id)->where('status', 'active')->count(),
                'Sections' => Section::withoutGlobalScopes()->where('school_id', $id)->count(),
                'Fees collected this month' => 'Rs '.number_format(Payment::withoutGlobalScopes()->where('school_id', $id)->whereBetween('paid_on', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount')),
                'Last sign-in' => $users->max('last_login_at')?->diffForHumans() ?? 'Never',
            ],
            'staffUsers' => $users->whereNotIn('role', ['parent', 'student'])->sortBy('name'),
            'parentCount' => $users->whereIn('role', ['parent', 'student'])->count(),
            'admin' => $users->where('role', 'admin')->where('is_active', true)->first(),
            'passwordUser' => $this->passwordUserId ? $users->firstWhere('id', $this->passwordUserId) : null,
            'deleteSummary' => $this->showDelete ? app(SchoolEraser::class)->summary($this->school) : [],
            'entries' => $this->school->subscriptionEntries()->with('user')->get(),
            'site' => $site,
            'sitePages' => $site?->pages()->count() ?? 0,
            'siteMessages' => $site?->messages()->whereNull('read_at')->count() ?? 0,
        ]);
    }
}
