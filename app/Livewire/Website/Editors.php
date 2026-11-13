<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\ManagesSite;
use App\Models\AuditLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Only the site's owner decides who else may manage which part of it. */
#[Title('Website editors')]
class Editors extends Component
{
    use ManagesSite;

    public ?int $editingId = null;

    public array $granted = [];

    public string $name = '';

    public string $email = '';

    public ?array $issued = null;

    public function mount(): void
    {
        $this->ensureOwner();
    }

    private function ensureOwner(): void
    {
        abort_unless($this->site()->isOwnedBy(Auth::user()), 403);
    }

    /** Logins of the same school that can be given website work. User has no tenant scope of its own. */
    private function candidates()
    {
        return User::where('school_id', $this->site()->school_id)->whereIn('role', ['accountant', 'teacher', 'website_editor']);
    }

    private function allowedValues(): array
    {
        return [...array_keys(Site::AREAS), ...$this->site()->pages()->pluck('id')->map(fn ($id) => 'page:'.$id)];
    }

    public function createEditor(): void
    {
        $this->ensureOwner();

        $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,username',
        ]);

        $password = Str::password(10, symbols: false);
        $email = Str::lower($this->email);

        $user = User::create([
            'school_id' => $this->site()->school_id,
            'name' => $this->name,
            'username' => $email,
            'email' => $email,
            'role' => 'website_editor',
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        AuditLog::record('login_created', $user, null, null, $email.' as website_editor');
        $this->issued = ['username' => $email, 'password' => $password];
        $this->reset('name', 'email');
        $this->edit($user->id);
    }

    public function edit(int $id): void
    {
        $this->ensureOwner();
        $user = $this->candidates()->findOrFail($id);

        $this->editingId = $user->id;
        $this->granted = $this->site()->permissions()->where('user_id', $user->id)->pluck('permission')->all();
    }

    public function saveAccess(): void
    {
        $this->ensureOwner();
        $user = $this->candidates()->findOrFail($this->editingId);

        $this->validate(['granted.*' => [Rule::in($this->allowedValues())]]);

        DB::transaction(function () use ($user) {
            $site = $this->site();
            $site->permissions()->where('user_id', $user->id)->whereNotIn('permission', $this->granted)->get()->each->delete();

            foreach ($this->granted as $permission) {
                $site->permissions()->firstOrCreate(['user_id' => $user->id, 'permission' => $permission]);
            }
        });

        $this->editingId = null;
        $this->dispatch('toast', message: "Access saved for {$user->name}.");
    }

    public function render()
    {
        $site = $this->site();
        $pages = $site->pages()->get();
        $labels = Site::AREAS + $pages->mapWithKeys(fn ($p) => ['page:'.$p->id => $p->title.' page'])->all();
        $users = $this->candidates()->orderBy('name')->get();

        return view('livewire.website.editors', [
            'users' => $users,
            'pages' => $pages,
            'areas' => Site::AREAS,
            'labels' => $labels,
            'grants' => $site->permissions()->get()->groupBy('user_id'),
            'editing' => $this->editingId ? $users->firstWhere('id', $this->editingId) : null,
        ]);
    }
}
