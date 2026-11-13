<?php

namespace Tests\Feature;

use App\Livewire\Platform\Domains;
use App\Models\Site;
use App\Models\User;
use App\Services\DomainChecker;
use App\Services\SchoolProvisioner;
use App\Services\SiteBuilder;
use App\Support\SiteHost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DomainTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://mskdevs.test', 'sites.base_domain' => 'mskdevs.test', 'sites.server_ip' => '203.0.113.7', 'sites.scheme' => 'https']);
        $this->owner = User::create(['name' => 'Owner', 'username' => 'owner@example.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);
        $this->admin = app(SchoolProvisioner::class)->create(['school' => 'Alpha School', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $this->site = app(SiteBuilder::class)->forSchool($this->admin->school);
        $this->site->update(['is_published' => true]);
    }

    private function about(): string
    {
        $page = $this->site->pages()->create(['title' => 'About us', 'slug' => 'about-us', 'sort' => 5]);
        $this->site->menuItems()->create(['location' => 'header', 'label' => 'About us', 'site_page_id' => $page->id]);

        return $page->slug;
    }

    public function test_only_the_owner_sets_addresses_and_bad_ones_are_refused(): void
    {
        $this->actingAs($this->admin)->get(route('platform.domains'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('platform.domains', ['tab' => 'guide']))->assertOk()->assertSee('One-time setup')->assertSee('203.0.113.7');

        $form = Livewire::actingAs($this->owner)->test(Domains::class)->call('openForm', $this->admin->school_id)
            ->assertSet('subdomain', $this->admin->school->slug);

        $form->set('subdomain', 'www')->set('domain', 'https://www.Alpha-School.edu.pk/about')->call('save')->assertHasErrors(['subdomain' => 'not_in']);
        $form->set('subdomain', 'alpha')->set('domain', 'x.mskdevs.test')->call('save')->assertHasErrors('domain');
        $form->set('domain', 'https://www.Alpha-School.edu.pk/about')->set('expiresOn', '2027-01-01')->call('save')->assertHasNoErrors();

        $this->site->refresh();
        $this->assertSame(['alpha', 'alpha-school.edu.pk', 'waiting'], [$this->site->subdomain, $this->site->domain, $this->site->domain_status]);

        $other = app(SchoolProvisioner::class)->create(['school' => 'Beta School', 'type' => 'school', 'name' => 'Head', 'email' => 'b@example.test', 'phone' => '03000000001', 'password' => 'password']);
        Livewire::actingAs($this->owner)->test(Domains::class)->call('openForm', $other->school_id)
            ->set('subdomain', 'alpha')->set('domain', 'alpha-school.edu.pk')->call('save')->assertHasErrors(['subdomain' => 'unique', 'domain' => 'unique']);
    }

    public function test_free_and_own_addresses_serve_the_school_website_and_nothing_else(): void
    {
        $this->site->update(['subdomain' => 'alpha', 'domain' => 'alpha-school.edu.pk', 'domain_status' => 'waiting']);
        $about = $this->about();

        foreach (['http://alpha.mskdevs.test', 'http://alpha-school.edu.pk', 'http://www.alpha-school.edu.pk'] as $root) {
            $this->get($root.'/')->assertOk()->assertSee('Alpha School')->assertSee('href="'.$root.'/'.$about.'"', false);
            $this->get($root.'/'.$about)->assertOk()->assertSee('About us');
            $this->get($root.'/no-such-page')->assertRedirect('https://mskdevs.test/no-such-page');
            $this->get($root.'/login')->assertRedirect('https://mskdevs.test/login');
            $this->get($root.'/.well-known/msk-site.txt')->assertOk()->assertSeeText(SiteHost::token($this->site));
            // Menu links saved as /site/{school}/... stay on the school's own address.
            $slug = $this->admin->school->slug;
            $this->get($root.'/site/'.$slug)->assertRedirect($root.'/');
            $this->get($root.'/site/'.$slug.'/'.$about.'?x=1')->assertRedirect($root.'/'.$about.'?x=1');
        }

        $this->get('http://unknown.mskdevs.test/')->assertNotFound();
        $this->get('http://someone-else.pk/')->assertOk(); // not a school address: the normal home page

        $this->get('/domains/allowed?domain=alpha-school.edu.pk')->assertOk();
        $this->get('/domains/allowed?domain=alpha.mskdevs.test')->assertOk();
        $this->get('/domains/allowed?domain=evil.example')->assertNotFound();

        // A locked school's website stops on its own address too.
        $this->admin->school->update(['is_active' => false]);
        $this->get('http://alpha-school.edu.pk/')->assertNotFound();
    }

    public function test_a_passing_check_makes_the_domain_live_and_old_links_move_to_it(): void
    {
        $this->site->update(['domain' => 'alpha-school.edu.pk', 'domain_status' => 'waiting']);
        $about = $this->about();
        $old = route('site.school.page', [$this->admin->school->slug, $about]);
        $this->get($old)->assertOk(); // not live yet, and no free address

        $this->partialMock(DomainChecker::class, fn ($mock) => $mock->shouldReceive('lookup')->andReturn(['203.0.113.7']));
        $answer = 'another website';
        Http::fake(function () use (&$answer) {
            return Http::response($answer);
        });
        Livewire::actingAs($this->owner)->test(Domains::class)->call('check', $this->site->id);
        $this->assertSame('waiting', $this->site->fresh()->domain_status);
        $this->assertFalse($this->site->fresh()->domain_check['reaches']);

        $answer = SiteHost::token($this->site);
        Livewire::actingAs($this->owner)->test(Domains::class)->call('check', $this->site->id);
        $this->site->refresh();
        $this->assertSame('live', $this->site->domain_status);
        $this->assertTrue($this->site->domain_check['points'] && $this->site->domain_check['https']);
        $this->assertSame('https://alpha-school.edu.pk', $this->site->url());

        auth()->logout();
        $this->get($old)->assertRedirect('https://alpha-school.edu.pk/'.$about)->assertStatus(301);
        // Its editors still preview on the main address.
        $this->actingAs($this->admin)->get($old)->assertOk();
    }

    public function test_school_staff_cannot_change_the_address(): void
    {
        Livewire::actingAs($this->admin)->test(Domains::class)->assertForbidden();
        $this->actingAs($this->admin)->get(route('platform.domains'))->assertForbidden();
    }
}
