<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Support\SiteHost;
use App\Support\SiteSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/** A public website: a school's, or the software owner's when school_id is null. */
class Site extends Model
{
    use Audited;

    public const AREAS = [
        'design' => 'Brand and colours',
        'header' => 'Header',
        'footer' => 'Footer',
        'contact' => 'Contact details and campuses',
        'popup' => 'Pop-up notice',
        'menu' => 'Menus',
        'pages' => 'All pages (add, remove, publish, edit)',
        'messages' => 'Contact form messages',
    ];

    protected $guarded = ['id'];

    protected $casts = ['settings' => 'array', 'is_published' => 'boolean', 'domain_check' => 'array', 'domain_expires_on' => 'date'];

    private ?array $grantCache = null;

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function pages()
    {
        return $this->hasMany(SitePage::class)->orderBy('sort')->orderBy('id');
    }

    public function menuItems()
    {
        return $this->hasMany(SiteMenuItem::class)->orderBy('sort')->orderBy('id');
    }

    public function messages()
    {
        return $this->hasMany(SiteMessage::class)->latest();
    }

    public function permissions()
    {
        return $this->hasMany(SitePermission::class);
    }

    public function setting(string $group, ?string $key = null): mixed
    {
        $values = ($this->settings[$group] ?? []) + (SiteSchema::settingDefaults()[$group] ?? []);

        return $key ? ($values[$key] ?? null) : $values;
    }

    public function name(): string
    {
        return $this->setting('design', 'site_name') ?: config('app.name');
    }

    public function url(): string
    {
        if (! $this->school_id) {
            return route('home');
        }

        return $this->webRoot() ?? route('site.school', $this->school->slug);
    }

    public function pageUrl(SitePage $page): string
    {
        if ($page->is_home) {
            return $this->url();
        }

        if (! $this->school_id) {
            return route('site.page', $page->slug);
        }

        $root = $this->webRoot();

        return $root ? $root.'/'.$page->slug : route('site.school.page', [$this->school->slug, $page->slug]);
    }

    /** The school's own address when it has one that works: the one being visited, a live domain, or its free address. */
    public function webRoot(): ?string
    {
        if (! $this->school_id) {
            return null;
        }

        if (request()->attributes->get('site_host') === $this->id) {
            return request()->getSchemeAndHttpHost();
        }

        if ($this->domain && $this->domain_status === 'live') {
            return config('sites.scheme').'://'.$this->domain;
        }

        return $this->subdomainHost() ? config('sites.scheme').'://'.$this->subdomainHost() : null;
    }

    public function subdomainHost(): ?string
    {
        $base = SiteHost::baseDomain();

        return $this->subdomain && $base ? $this->subdomain.'.'.$base : null;
    }

    public function home(): ?SitePage
    {
        return $this->pages()->where('is_home', true)->first();
    }

    /** @return Collection<int, SiteMenuItem> top links that lead somewhere a visitor can open, each with its dropdown links as children */
    public function menu(string $location): Collection
    {
        $links = $this->menuItems()->with('page')->where('location', $location)->where('is_visible', true)->get()
            ->filter(fn ($i) => $i->site_page_id ? $i->page?->is_published : (bool) $i->url);

        return $links->whereNull('parent_id')->values()
            ->each(fn ($i) => $i->setRelation('children', $links->where('parent_id', $i->id)->values()));
    }

    /** @return array<int, array<string, mixed>> */
    public function campuses(): array
    {
        return $this->setting('contact', 'campuses') ?? [];
    }

    public function isOwnedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->school_id ? $user->school_id === $this->school_id && $user->isRole('admin') : $user->isRole('super_admin');
    }

    private function grants(User $user): array
    {
        return $this->grantCache[$user->id] ??= $this->permissions()->where('user_id', $user->id)->pluck('permission')->all();
    }

    public function allows(?User $user, string $permission): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isOwnedBy($user) || in_array($permission, $this->grants($user), true);
    }

    public function allowsPage(?User $user, SitePage $page): bool
    {
        return $this->allows($user, 'pages') || $this->allows($user, 'page:'.$page->id);
    }

    public function allowsAnything(?User $user): bool
    {
        return $user && ($this->isOwnedBy($user) || $this->grants($user) !== []);
    }

    /** First tab of the design screen this user may open. */
    public function designTabs(?User $user): array
    {
        return array_values(array_filter(['design', 'header', 'footer', 'contact', 'popup'], fn ($tab) => $this->allows($user, $tab)));
    }
}
