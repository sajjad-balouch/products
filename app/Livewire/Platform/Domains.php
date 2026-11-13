<?php

namespace App\Livewire\Platform;

use App\Livewire\Platform\Concerns\OwnerOnly;
use App\Models\School;
use App\Models\Site;
use App\Services\DomainChecker;
use App\Services\SiteBuilder;
use App\Support\SiteHost;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Web addresses of school websites. The owner buys each domain, sets it up, and records it here. */
#[Title('Domains')]
class Domains extends Component
{
    use OwnerOnly;

    #[Url]
    public string $tab = 'list';

    #[Url]
    public string $filter = 'all';

    #[Url]
    public string $search = '';

    // The address form
    public bool $showForm = false;

    public ?int $siteId = null;

    public string $subdomain = '';

    public string $domain = '';

    public string $registrar = '';

    public string $expiresOn = '';

    public string $notes = '';

    /** Which school the guide fills its examples with. */
    #[Url]
    public ?int $guideSite = null;

    public function openForm(int $schoolId): void
    {
        $this->resetValidation();
        $site = app(SiteBuilder::class)->forSchool(School::findOrFail($schoolId));
        $this->siteId = $site->id;
        $this->subdomain = (string) ($site->subdomain ?? $this->suggestSubdomain($site));
        $this->domain = (string) $site->domain;
        $this->registrar = (string) $site->domain_registrar;
        $this->expiresOn = (string) $site->domain_expires_on?->toDateString();
        $this->notes = (string) $site->domain_notes;
        $this->showForm = true;
    }

    private function suggestSubdomain(Site $site): string
    {
        $name = substr($site->school->slug, 0, 63);

        return in_array($name, config('sites.reserved'), true) || Site::where('subdomain', $name)->exists() ? '' : $name;
    }

    public function save(): void
    {
        $site = Site::whereNotNull('school_id')->findOrFail($this->siteId);
        $this->subdomain = strtolower(trim($this->subdomain));
        $this->domain = SiteHost::normalize($this->domain);
        $base = SiteHost::baseDomain();
        if (! $base) {
            // Free addresses are off, so the box is hidden: keep whatever was saved before.
            $this->subdomain = (string) $site->subdomain;
        }

        $this->validate([
            'subdomain' => ['nullable', 'max:63', 'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', Rule::notIn(config('sites.reserved')), Rule::unique('sites', 'subdomain')->ignore($site->id)],
            'domain' => ['nullable', 'max:190', 'regex:/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/', Rule::unique('sites', 'domain')->ignore($site->id),
                function ($attribute, $value, $fail) use ($base) {
                    if (SiteHost::isAppHost($value) || ($base && str_ends_with($value, '.'.$base))) {
                        $fail('This is our own address. Use the free address box for it.');
                    }
                }],
            'registrar' => 'nullable|string|max:100',
            'expiresOn' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ], [
            'subdomain.regex' => 'Use small letters, numbers and dashes only.',
            'subdomain.not_in' => 'This name is kept for the software.',
            'subdomain.unique' => 'Another school already has this free address.',
            'domain.regex' => 'Write a domain like school.edu.pk, without http or slashes.',
            'domain.unique' => 'Another school already has this domain.',
        ], ['expiresOn' => 'renewal date']);

        $changed = $site->domain !== ($this->domain ?: null);
        $site->update([
            'subdomain' => $this->subdomain ?: null,
            'domain' => $this->domain ?: null,
            'domain_registrar' => $this->registrar ?: null,
            'domain_expires_on' => $this->expiresOn ?: null,
            'domain_notes' => $this->notes ?: null,
        ] + ($changed ? ['domain_status' => $this->domain ? 'waiting' : 'none', 'domain_check' => null] : []));

        $this->showForm = false;
        $this->dispatch('toast', message: $changed && $this->domain ? 'Saved. Now set the DNS records and press Check.' : 'Saved.');
    }

    public function check(int $siteId, DomainChecker $checker): void
    {
        $site = Site::whereNotNull('school_id')->findOrFail($siteId);
        $host = $site->domain ?: $site->subdomainHost();

        if (! $host) {
            $this->dispatch('toast', message: 'This school has no address to check yet.', error: true);

            return;
        }

        $result = $checker->check($site, $host);
        $site->domain_check = $result;

        if ($site->domain && $result['reaches'] && $result['https']) {
            $site->domain_status = 'live';
        }
        $site->save();

        $this->dispatch('toast', ...match (true) {
            $result['reaches'] && $result['https'] => ['message' => $host.' works with SSL. Links now use it.'],
            $result['reaches'] => ['message' => $host.' reaches this website, but SSL is not ready yet. Turn on SSL, then check again.', 'error' => true],
            $result['ips'] === [] => ['message' => $host.' does not point anywhere yet. DNS changes can take up to 24 hours.', 'error' => true],
            default => ['message' => $host.' points to '.implode(', ', $result['ips']).' but does not open this website yet. See the guide.', 'error' => true],
        });
    }

    /** For when the check cannot run from this server, for example while testing on a local computer. */
    public function setStatus(int $siteId, string $status): void
    {
        abort_unless(in_array($status, ['waiting', 'live'], true), 422);
        $site = Site::whereNotNull('school_id')->whereNotNull('domain')->findOrFail($siteId);
        $site->update(['domain_status' => $status]);
        $this->dispatch('toast', message: $status === 'live' ? $site->domain.' is now the website address.' : $site->domain.' is on hold. Links use the old address again.');
    }

    public function removeDomain(int $siteId): void
    {
        $site = Site::whereNotNull('school_id')->findOrFail($siteId);
        $site->update(['domain' => null, 'domain_status' => 'none', 'domain_check' => null]);
        $this->dispatch('toast', message: 'Domain removed. The website stays at its free or old address.');
    }

    public function render()
    {
        $schools = School::with(['site.school'])->orderBy('name')
            ->when($this->search, fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')
                ->orWhereHas('site', fn ($q) => $q->where('domain', 'like', '%'.$this->search.'%')->orWhere('subdomain', 'like', '%'.$this->search.'%'))))
            ->get();

        $renewBy = today()->addDays(30);
        $all = $schools;
        $schools = $schools->filter(fn ($s) => match ($this->filter) {
            'live' => $s->site?->domain_status === 'live',
            'waiting' => $s->site?->domain_status === 'waiting',
            'none' => ! $s->site?->domain,
            'renew' => $s->site?->domain_expires_on && $s->site->domain_expires_on->lte($renewBy),
            default => true,
        });

        $guide = $this->guideSite ? Site::with('school')->whereNotNull('school_id')->find($this->guideSite) : null;

        return view('livewire.platform.domains', [
            'schools' => $schools,
            'counts' => [
                'live' => $all->where('site.domain_status', 'live')->count(),
                'waiting' => $all->where('site.domain_status', 'waiting')->count(),
                'free' => $all->filter(fn ($s) => $s->site?->subdomain)->count(),
                'renew' => $all->filter(fn ($s) => $s->site?->domain_expires_on && $s->site->domain_expires_on->lte($renewBy))->count(),
            ],
            'base' => SiteHost::baseDomain(),
            'serverIp' => config('sites.server_ip'),
            'appHost' => parse_url((string) config('app.url'), PHP_URL_HOST),
            'guide' => $guide,
            'guideSites' => Site::with('school')->whereNotNull('school_id')->whereNotNull('domain')->get(),
        ]);
    }
}
