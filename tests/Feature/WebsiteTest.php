<?php

namespace Tests\Feature;

use App\Livewire\Website\Design;
use App\Livewire\Website\Editors;
use App\Livewire\Website\Menu;
use App\Livewire\Website\Messages;
use App\Livewire\Website\PageEditor;
use App\Livewire\Website\Pages;
use App\Models\Plan;
use App\Models\Site;
use App\Models\SiteMedia;
use App\Models\SiteMessage;
use App\Models\SitePage;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Services\SiteBuilder;
use App\Support\Navigation;
use Database\Seeders\DemoWebsiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WebsiteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->school('School A');
        $this->site = app(SiteBuilder::class)->forSchool($this->admin->school);
    }

    private function school(string $name): User
    {
        return app(SchoolProvisioner::class)->create(['school' => $name, 'type' => 'school', 'name' => 'Head', 'email' => str_replace(' ', '', strtolower($name)).'@example.test', 'phone' => '03000000000', 'password' => 'password']);
    }

    private function staff(string $role = 'website_editor', ?User $of = null): User
    {
        static $n = 0;
        $n++;

        return User::create(['school_id' => ($of ?? $this->admin)->school_id, 'name' => 'Staff '.$n, 'username' => "staff{$n}@example.test", 'role' => $role, 'password' => Hash::make('password')]);
    }

    private function grant(User $user, string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            $this->site->permissions()->create(['user_id' => $user->id, 'permission' => $permission]);
        }
    }

    private function publish(): void
    {
        $this->site->update(['is_published' => true]);
    }

    /** The school template is one long home page, so tests that need a second page add it. */
    private function extraPage(string $slug): SitePage
    {
        return app(SiteBuilder::class)->addPage($this->site, ucfirst($slug), $slug);
    }

    public function test_the_school_template_has_every_part_of_a_complete_school_website(): void
    {
        $this->publish();

        $types = $this->site->home()->sections()->pluck('type')->all();
        foreach (['slider', 'image_text', 'message', 'features', 'campuses', 'gallery', 'notices', 'contact'] as $type) {
            $this->assertContains($type, $types);
        }

        $base = '/site/'.$this->admin->school->slug;
        $this->get($base)->assertOk()
            ->assertSee('data-slider', false)->assertSee('id="about"', false)->assertSee('id="mission"', false)->assertSee('id="campuses"', false)->assertSee('id="contact"', false)
            ->assertSee($base.'#values', false)->assertSee('Portals')->assertSee('Teachers and staff')
            ->assertSee("Principal's message")->assertSee('Main campus')
            // The gallery has no pictures yet, so visitors do not see an empty heading.
            ->assertDontSee('id="gallery"', false);
    }

    public function test_slider_message_and_gallery_show_what_the_school_entered(): void
    {
        Storage::fake('local');
        $this->publish();
        $page = $this->extraPage('events');
        $editor = Livewire::actingAs($this->admin)->test(PageEditor::class, ['page' => $page->id]);

        $editor->call('chooseType', 'slider')->set('form.interval', '9')->call('addItem')->call('addItem')
            ->set('items.0.heading', 'Sports day')->set('items.1.heading', 'Science fair')->set('items.1.button_label', 'See')->set('items.1.button_url', 'javascript:alert(1)')
            ->call('saveSection')->assertHasErrors('items.1.button_url');
        $editor->set('items.1.button_url', '/ok')->call('saveSection')->assertHasNoErrors();

        $editor->call('chooseType', 'message')->set('form.heading', 'From the chairman')->set('form.body', 'Welcome **all**')->set('form.name', 'A. Khan')->set('form.role', 'Chairman')->set('form._eyebrow', 'Leadership')->set('form._anchor', 'Our Chairman')->call('saveSection')->assertHasNoErrors();

        $gallery = $editor->call('chooseType', 'gallery')->set('form.heading', 'Moments')->call('addItem');
        $id = $editor->get('items')[0]['_id'];
        $gallery->set('uploads.'.$id.'_image', UploadedFile::fake()->image('a.jpg'))->set('items.0.category', 'Events')->set('items.0.caption', 'Prize day')->call('saveSection')->assertHasNoErrors();

        auth()->logout();
        $this->get('/site/'.$this->admin->school->slug.'/events')->assertOk()
            ->assertSee('data-interval="9"', false)->assertSee('Sports day')->assertSee('Science fair')
            ->assertSee('id="our-chairman"', false)->assertSee('Leadership')->assertSee('<strong>all</strong>', false)->assertSee('A. Khan')
            ->assertSee('data-filter="Events"', false)->assertSee('Events (1)')->assertSee('Prize day');
    }

    public function test_campuses_are_entered_once_and_show_in_the_section_and_footer(): void
    {
        Storage::fake('local');
        $this->publish();

        $design = Livewire::actingAs($this->admin)->test(Design::class)->set('tab', 'contact');
        $design->call('removeItem', 0)->call('addItem')->call('addItem')
            ->set('items.0.name', 'City Campus')->set('items.0.address', '5 Mall Road')->set('items.0.phone', '068-111')
            ->set('items.1.name', 'Town Campus')->call('save')->assertHasNoErrors();

        $this->assertSame(['City Campus', 'Town Campus'], array_column($this->site->fresh()->campuses(), 'name'));

        $design->call('moveItem', 1, -1)->call('save');
        $this->assertSame('Town Campus', $this->site->fresh()->campuses()[0]['name']);

        auth()->logout();
        $response = $this->get('/site/'.$this->admin->school->slug)->assertOk()->assertSee('City Campus')->assertSee('tel:068111', false)->assertSee('Directions');
        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), 'City Campus'));
    }

    public function test_the_pop_up_shows_only_when_turned_on_and_can_be_assigned(): void
    {
        $this->publish();
        $url = '/site/'.$this->admin->school->slug;
        $this->get($url)->assertDontSee('data-popup', false);

        $editor = $this->staff();
        $this->grant($editor, 'popup');
        Livewire::actingAs($editor)->test(Design::class)->assertSet('tab', 'popup')
            ->set('form.show', true)->set('form.heading', 'Fall admissions open')->call('save')->assertHasNoErrors();
        Livewire::actingAs($editor)->test(Design::class)->set('tab', 'footer')->assertForbidden();

        auth()->logout();
        $this->get($url)->assertSee('data-popup', false)->assertSee('Fall admissions open');
    }

    public function test_menu_links_can_sit_in_a_dropdown_one_level_deep(): void
    {
        $this->publish();
        $menu = Livewire::actingAs($this->admin)->test(Menu::class);

        $menu->call('openForm')->set('label', 'Facilities')->set('url', '#')->call('save')->assertHasNoErrors();
        $parent = $this->site->menuItems()->where('label', 'Facilities')->first();
        $menu->call('openForm')->set('label', 'Science lab')->set('url', '/lab')->set('parent_id', (string) $parent->id)->call('save')->assertHasNoErrors();
        $child = $this->site->menuItems()->where('label', 'Science lab')->first();

        $menu->call('openForm')->set('label', 'Too deep')->set('url', '/x')->set('parent_id', (string) $child->id)->call('save')->assertHasErrors('parent_id');
        $menu->call('openForm', $parent->id)->set('parent_id', (string) $this->site->menuItems()->where('label', 'Portals')->first()->id)->call('save')->assertHasErrors('parent_id');

        auth()->logout();
        $this->get('/site/'.$this->admin->school->slug)->assertSee('Facilities')->assertSee('Science lab');

        Livewire::actingAs($this->admin)->test(Menu::class)->call('delete', $parent->id);
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_using_the_template_is_for_the_owner_and_keeps_the_look(): void
    {
        $this->extraPage('old-page');
        $settings = $this->site->settings;
        $settings['design']['primary'] = '#123456';
        $this->site->update(['settings' => $settings]);

        $editor = $this->staff();
        $this->grant($editor, 'pages');
        Livewire::actingAs($editor)->test(Pages::class)->call('useSchoolTemplate')->assertForbidden();
        $this->assertNotNull($this->site->pages()->where('slug', 'old-page')->first());

        Livewire::actingAs($this->admin)->test(Pages::class)->call('useSchoolTemplate')->assertOk();

        $this->assertNull($this->site->pages()->where('slug', 'old-page')->first());
        $this->assertSame(1, $this->site->pages()->where('is_home', true)->count());
        $this->assertSame('#123456', $this->site->fresh()->setting('design', 'primary'));
    }

    public function test_the_template_gives_an_older_site_campuses_but_keeps_ones_already_entered(): void
    {
        $settings = $this->site->settings;
        unset($settings['contact']['campuses'], $settings['popup']);
        $this->site->update(['settings' => $settings]);

        $builder = app(SiteBuilder::class);
        $builder->applySchoolTemplate($this->site);
        $this->assertSame('Main campus', $this->site->fresh()->campuses()[0]['name']);
        $this->assertSame('Admissions are open', $this->site->fresh()->setting('popup', 'heading'));

        $settings = $this->site->fresh()->settings;
        $settings['contact']['campuses'] = [['name' => 'City', 'badge' => '', 'address' => '', 'phone' => '', 'about' => '', 'image' => null]];
        $this->site->update(['settings' => $settings]);

        $builder->applySchoolTemplate($this->site->fresh());
        $this->assertSame('City', $this->site->fresh()->campuses()[0]['name']);
    }

    public function test_home_shows_the_owners_site_with_its_features_to_visitors(): void
    {
        $this->get('/')->assertOk()->assertSee(config('app.name'))->assertSee('Early warning')->assertSee('Sign in')->assertSee('Start free trial');
        $this->get('/p/features')->assertOk()->assertSee('Library and transport');
        $this->get('/p/nope')->assertNotFound();
    }

    public function test_unpublishing_the_owners_site_brings_back_the_sign_in_page(): void
    {
        app(SiteBuilder::class)->platform()->update(['is_published' => false]);

        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_a_school_site_is_a_draft_only_editors_see_until_published(): void
    {
        $url = '/site/'.$this->admin->school->slug;

        $this->get($url)->assertNotFound();
        $this->actingAs($this->staff('teacher'))->get($url)->assertNotFound();
        $this->actingAs($this->admin)->get($url)->assertOk()->assertSee('The website is a draft');

        Livewire::actingAs($this->admin)->test(Pages::class)->call('setPublished', true);

        auth()->logout();
        $this->get($url)->assertOk()->assertSee('Welcome to School A')->assertSee('Apply now')->assertDontSee('You can edit this website');
    }

    public function test_a_suspended_schools_site_is_gone(): void
    {
        $this->publish();
        $this->admin->school->update(['is_active' => false]);

        $this->get('/site/'.$this->admin->school->slug)->assertNotFound();
    }

    public function test_only_the_owner_can_publish_and_assign(): void
    {
        $editor = $this->staff();
        $this->grant($editor, 'pages');

        Livewire::actingAs($editor)->test(Pages::class)->call('setPublished', true)->assertForbidden();
        $this->actingAs($editor)->get(route('website.editors'))->assertForbidden();
        $this->assertFalse($this->site->fresh()->is_published);
    }

    public function test_an_editor_reaches_only_what_was_assigned(): void
    {
        $editor = $this->staff();
        $about = $this->extraPage('about');
        $this->extraPage('admissions');
        $home = $this->site->home();
        $this->grant($editor, 'page:'.$about->id, 'header');

        $this->actingAs($editor);
        $this->get(route('website.page', $about->id))->assertOk();
        $this->get(route('website.page', $home->id))->assertForbidden();
        $this->get(route('website.menu'))->assertForbidden();
        $this->get(route('website.messages'))->assertForbidden();
        $this->get(route('website.design'))->assertOk()->assertSee('Header')->assertDontSee('Brand and colours');
        $this->get(route('dashboard'))->assertForbidden();

        Livewire::test(Design::class)->set('tab', 'footer')->assertForbidden();
        Livewire::test(Pages::class)->assertSee('About')->assertDontSee('Admissions')->call('delete', $about->id)->assertForbidden();
        $this->assertNotNull($about->fresh());

        $routes = collect(Navigation::for($editor))->flatten(1)->pluck(1)->all();
        $this->assertSame(['website.pages', 'website.design'], $routes);
    }

    public function test_a_teacher_keeps_their_work_and_gains_only_the_assigned_website_link(): void
    {
        $teacher = $this->staff('teacher');
        $this->assertArrayNotHasKey('Website', Navigation::for($teacher));

        $this->grant($teacher, 'messages');

        $this->assertSame([['Pages', 'website.pages'], ['Messages', 'website.messages']], Navigation::for($teacher)['Website']);
        $this->actingAs($teacher)->get(route('dashboard'))->assertOk();
    }

    public function test_a_parent_and_another_school_cannot_manage_this_site(): void
    {
        $parent = $this->staff('parent');
        $other = $this->school('School B');

        $this->actingAs($parent)->get(route('website.pages'))->assertForbidden();

        $page = $this->site->home();
        $this->actingAs($other)->get(route('website.page', $page->id))->assertNotFound();
        Livewire::actingAs($other)->test(Editors::class)->call('edit', $this->staff()->id)->assertNotFound();
    }

    public function test_admin_can_assign_access_and_create_a_website_login(): void
    {
        $page = $this->site->home();
        $editors = Livewire::actingAs($this->admin)->test(Editors::class)
            ->set('name', 'Web Person')->set('email', 'Web@Example.test')->call('createEditor')->assertHasNoErrors();

        $user = User::where('username', 'web@example.test')->firstOrFail();
        $this->assertSame('website_editor', $user->role);
        $this->assertSame($this->admin->school_id, $user->school_id);
        $this->assertTrue($user->must_change_password);

        $editors->set('granted', ['footer', 'page:'.$page->id])->call('saveAccess')->assertHasNoErrors();
        $this->assertTrue($this->site->fresh()->allows($user, 'footer'));
        $this->assertTrue($this->site->fresh()->allowsPage($user, $page));
        $this->assertFalse($this->site->fresh()->allows($user, 'header'));

        $editors->call('edit', $user->id)->set('granted', ['header'])->call('saveAccess');
        $this->assertFalse($this->site->fresh()->allows($user, 'footer'));

        $editors->call('edit', $user->id)->set('granted', ['page:999999'])->call('saveAccess')->assertHasErrors('granted.0');
    }

    public function test_sections_can_be_added_edited_moved_hidden_copied_and_deleted(): void
    {
        $this->publish();
        $page = $this->extraPage('gallery');
        $page->sections()->create(['type' => 'gallery', 'data' => ['items' => []], 'sort' => 1]);
        $url = '/site/'.$this->admin->school->slug.'/gallery';
        $editor = Livewire::actingAs($this->admin)->test(PageEditor::class, ['page' => $page->id]);

        $editor->call('chooseType', 'text')->set('form.heading', 'Our campus')->set('form.body', 'Green **lawns**')->set('form._bg', 'dark')->call('saveSection')->assertHasNoErrors();
        $section = $page->sections()->where('type', 'text')->firstOrFail();

        auth()->logout();
        $this->get($url)->assertSee('Our campus')->assertSee('<strong>lawns</strong>', false)->assertSee('site-bg-dark');

        $this->actingAs($this->admin);
        $editor->call('moveSection', $section->id, -1);
        $this->assertSame('text', $page->sections()->first()->type);

        $editor->call('duplicateSection', $section->id);
        $this->assertSame(2, $page->sections()->where('type', 'text')->count());

        $editor->call('toggleSection', $section->id);
        $copy = $page->sections()->where('type', 'text')->where('id', '!=', $section->id)->first();
        $editor->call('deleteSection', $copy->id);

        auth()->logout();
        $this->get($url)->assertDontSee('Our campus');
    }

    public function test_cards_keep_their_order_and_empty_cards_are_dropped(): void
    {
        $page = $this->site->home();
        $editor = Livewire::actingAs($this->admin)->test(PageEditor::class, ['page' => $page->id])
            ->call('chooseType', 'faq')->set('form.heading', 'FAQ')->call('addItem')->call('addItem')->call('addItem');

        $editor->set('items.0.question', 'First')->set('items.1.question', 'Second')->call('moveItem', 1, -1)->call('saveSection')->assertHasNoErrors();

        $items = $page->sections()->where('type', 'faq')->first()->data['items'];
        $this->assertSame(['Second', 'First'], array_column($items, 'question'));
        $this->assertArrayNotHasKey('_id', $items[0]);
    }

    public function test_editors_cannot_put_script_on_the_site(): void
    {
        $this->publish();
        $page = $this->site->home();
        $editor = Livewire::actingAs($this->admin)->test(PageEditor::class, ['page' => $page->id]);

        $editor->call('chooseType', 'cta')->set('form.heading', 'Go')->set('form.button_label', 'Click')->set('form.button_url', 'javascript:alert(1)')->call('saveSection')->assertHasErrors('form.button_url');

        $editor->set('form.button_url', '/ok')->set('form.heading', '<script>alert(1)</script>')->call('saveSection')->assertHasNoErrors();
        $editor->call('chooseType', 'text')->set('form.body', "<img src=x onerror=alert(2)>\n\n[bad](javascript:alert(3))")->call('saveSection')->assertHasNoErrors();

        Livewire::actingAs($this->admin)->test(Design::class)->set('tab', 'design')->set('form.primary', 'red;}body{display:none')->call('save')->assertHasErrors('form.primary');
        Livewire::actingAs($this->admin)->test(Menu::class)->call('openForm')->set('label', 'X')->set('url', 'javascript:alert(4)')->call('save')->assertHasErrors('url');

        auth()->logout();
        $this->get('/site/'.$this->admin->school->slug)->assertOk()
            ->assertDontSee('<script>alert(1)', false)->assertDontSee('onerror', false)->assertDontSee('javascript:', false);
    }

    public function test_header_footer_brand_and_contact_settings_show_on_the_site(): void
    {
        $this->publish();
        $design = Livewire::actingAs($this->admin)->test(Design::class);

        $design->set('tab', 'design')->set('form.site_name', 'Bright Future')->set('form.primary', '#123456')->set('form.direction', 'rtl')->call('save')->assertHasNoErrors();
        $design->set('tab', 'header')->set('form.topbar_show', true)->set('form.topbar_text', 'Admissions open')->set('form.login_show', false)->set('form.cta_label', 'Join us')->call('save')->assertHasNoErrors();
        $design->set('tab', 'footer')->set('form.about', 'Since long ago')->set('form.facebook', 'https://facebook.com/x')->set('form.copyright', 'All mine')->call('save')->assertHasNoErrors();
        $design->set('tab', 'contact')->set('form.phone', '0300 111')->call('save')->assertHasNoErrors();

        auth()->logout();
        $this->get('/site/'.$this->admin->school->slug)->assertOk()
            ->assertSee('Bright Future')->assertSee('#123456')->assertSee('dir="rtl"', false)
            ->assertSee('Admissions open')->assertDontSee('Sign in')->assertSee('Join us')
            ->assertSee('Since long ago')->assertSee('https://facebook.com/x')->assertSee('All mine')->assertSee('0300 111');

        $design = Livewire::actingAs($this->admin)->test(Design::class)->set('tab', 'footer')->set('form.show', false)->call('save');
        auth()->logout();
        $this->get('/site/'.$this->admin->school->slug)->assertDontSee('Since long ago');
    }

    public function test_menu_links_can_be_managed_and_hidden_pages_drop_out(): void
    {
        $this->publish();
        $about = $this->extraPage('history');
        $menu = Livewire::actingAs($this->admin)->test(Menu::class);

        $menu->call('openForm')->set('label', 'Portal')->set('url', 'https://example.com/portal')->set('new_tab', true)->call('save')->assertHasNoErrors();
        $menu->call('openForm')->set('label', 'Nothing')->call('save')->assertHasErrors('url');

        Livewire::actingAs($this->admin)->test(Pages::class)->call('togglePublished', $about->id);

        auth()->logout();
        $url = '/site/'.$this->admin->school->slug;
        $this->get($url)->assertSee('https://example.com/portal')->assertDontSee('>History<', false);
        $this->get($url.'/history')->assertNotFound();
    }

    public function test_pages_can_be_created_and_the_home_page_is_protected(): void
    {
        $pages = Livewire::actingAs($this->admin)->test(Pages::class);
        $home = $this->site->home();

        $pages->call('openForm')->set('title', 'Sports Week')->call('save')->assertHasNoErrors();
        $new = $this->site->pages()->where('slug', 'sports-week')->firstOrFail();
        $this->assertSame(1, $this->site->menuItems()->where(['location' => 'header', 'site_page_id' => $new->id])->count());

        $pages->call('openForm')->set('title', 'Sports Week')->call('save')->assertHasErrors('slug');

        $pages->call('delete', $home->id);
        $pages->call('togglePublished', $home->id);
        $this->assertTrue($home->fresh()->is_published);

        $pages->call('makeHome', $new->id);
        $this->assertTrue($new->fresh()->is_home);
        $this->assertFalse($home->fresh()->is_home);

        $pages->call('delete', $home->id);
        $this->assertNull($home->fresh());
        $this->assertSame(0, $this->site->menuItems()->where('site_page_id', $home->id)->count());
    }

    public function test_contact_form_saves_messages_for_those_allowed_and_ignores_bots(): void
    {
        $this->publish();
        $route = route('site.contact', $this->site);

        $this->post($route, ['name' => 'Ali'])->assertSessionHasErrors(['message', 'phone']);
        $this->post($route, ['name' => 'Bot', 'phone' => '1', 'message' => 'spam', 'website' => 'x'])->assertSessionHas('site_contact_sent');
        $this->assertSame(0, SiteMessage::count());

        $page = '/site/'.$this->admin->school->slug;
        $this->from($page)->post($route, ['name' => 'Ali', 'phone' => '0300', 'message' => 'Fee details please', 'anchor' => 'contact'])
            ->assertRedirect($page.'#contact')->assertSessionHas('site_contact_sent');
        $this->assertSame(1, $this->site->messages()->count());

        Livewire::actingAs($this->admin)->test(Messages::class)->assertSee('Fee details please')->call('toggleRead', SiteMessage::first()->id);
        $this->assertNotNull(SiteMessage::first()->read_at);

        Livewire::actingAs($this->school('School B'))->test(Messages::class)->assertDontSee('Fee details please')->call('delete', SiteMessage::first()->id)->assertNotFound();
    }

    public function test_contact_form_on_an_unpublished_site_is_refused(): void
    {
        $this->post(route('site.contact', $this->site), ['name' => 'Ali', 'phone' => '0300', 'message' => 'Hi'])->assertNotFound();
    }

    public function test_pictures_upload_and_only_real_images_are_accepted(): void
    {
        Storage::fake('local');
        $this->publish();

        $design = Livewire::actingAs($this->admin)->test(Design::class)->set('tab', 'design')->set('uploads.logo', UploadedFile::fake()->create('evil.svg', 5, 'image/svg+xml'))->call('save');
        $design->assertHasErrors('uploads.logo');

        $design->set('uploads.logo', UploadedFile::fake()->image('logo.png'))->call('save')->assertHasNoErrors();
        $media = SiteMedia::firstOrFail();
        $this->assertSame($media->id, $this->site->fresh()->setting('design', 'logo'));

        auth()->logout();
        $this->get(route('site.media', $media->id))->assertOk();
        $this->get('/site/'.$this->admin->school->slug)->assertSee(route('site.media', $media->id));
    }

    public function test_a_picture_from_another_site_cannot_be_borrowed(): void
    {
        $theirs = app(SiteBuilder::class)->forSchool($this->school('School B')->school);
        $media = SiteMedia::create(['site_id' => $theirs->id, 'path' => 'x.png']);

        Livewire::actingAs($this->admin)->test(Design::class)->set('tab', 'design')->set('form.logo', $media->id)->call('save')->assertHasErrors('form.logo');
    }

    public function test_software_owner_manages_their_own_site_and_can_add_an_editor(): void
    {
        $owner = User::create(['name' => 'Owner', 'username' => 'owner@example.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);
        $platform = app(SiteBuilder::class)->platform();

        foreach (Navigation::for($owner)['Website'] as [$label, $route]) {
            $this->actingAs($owner)->get(route($route))->assertOk();
        }

        Livewire::actingAs($owner)->test(Design::class)->set('tab', 'header')->set('form.login_label', 'Log in here')->call('save')->assertHasNoErrors();
        $this->assertSame('Log in here', $platform->fresh()->setting('header', 'login_label'));
        $this->assertNull($this->site->fresh()->settings['header']['login_label'] ?? null);

        Livewire::actingAs($owner)->test(Editors::class)->set('name', 'Web')->set('email', 'web@example.test')->call('createEditor')->set('granted', ['header'])->call('saveAccess');

        $editor = User::where('username', 'web@example.test')->first();
        $this->assertNull($editor->school_id);
        $this->actingAs($editor);
        $this->get(route('website.design'))->assertRedirect(route('password.change'));

        $editor->update(['must_change_password' => false]);
        $this->get(route('website.design'))->assertOk();
        $this->get(route('platform.schools'))->assertForbidden();
    }

    public function test_a_website_editor_with_nothing_assigned_is_told_so_and_opens_no_office_page(): void
    {
        $editor = $this->staff();
        $this->assertSame('website.pages', $editor->homeRoute());

        $this->actingAs($editor);
        $this->get(route('website.pages'))->assertOk()->assertSee('Nothing has been assigned');
        foreach (['dashboard', 'students.index', 'settings', 'portal', 'timetable'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_demo_seeder_fills_both_websites_with_photos(): void
    {
        Storage::fake('local');
        $this->seed(DemoWebsiteSeeder::class);

        $school = $this->site->fresh();
        $sections = $school->home()->sections()->get()->keyBy(fn ($s) => $s->data['_anchor'] ?? $s->id);
        $this->assertCount(3, $sections['home']->data['items']);
        $this->assertNotNull($sections['home']->data['items'][2]['image']);
        $this->assertCount(12, $sections['gallery']->data['items']);
        $this->assertNotNull($sections['message']->data['image']);
        $this->assertNotNull($school->campuses()[1]['image']);
        $this->assertTrue($school->is_published);

        $platform = app(SiteBuilder::class)->platform();
        $this->assertNotNull($platform->home()->sections()->first()->data['image']);
        $this->assertNotNull($platform->home()->sections()->first()->data['image2']);
        $this->assertNotNull($platform->home()->sections()->where('type', 'features')->first()->data['items'][0]['image']);
        $this->assertSame('#4f46e5', $platform->fresh()->setting('design', 'primary'));

        foreach (SiteMedia::all() as $media) {
            Storage::disk('local')->assertExists($media->path);
        }
        $this->get('/')->assertOk()->assertSee(route('site.media', $platform->home()->sections()->first()->data['image']));
        $this->get('/site/'.$this->admin->school->slug)->assertOk()->assertSee('Where every child learns to shine')->assertSee('Girls campus')->assertSee('Inter-house football');
    }

    public function test_demo_seeder_keeps_pictures_already_uploaded_and_does_not_rebuild_an_edited_site(): void
    {
        Storage::fake('local');
        $mine = SiteMedia::create(['site_id' => $this->site->id, 'path' => 'mine.jpg', 'original_name' => 'mine.jpg']);
        $slider = $this->site->home()->sections()->where('type', 'slider')->first();
        $data = $slider->data;
        $data['items'][0]['image'] = $mine->id;
        $this->travel(1)->minutes();
        $this->actingAs($this->admin);
        $slider->update(['data' => $data]);
        auth()->logout();

        $this->seed(DemoWebsiteSeeder::class);

        $slider = $slider->fresh();
        $this->assertNotNull($slider, 'An edited site must not be rebuilt.');
        $this->assertSame($mine->id, $slider->data['items'][0]['image']);
        $this->assertSame('Where every child learns to shine', $slider->data['items'][0]['heading']);
        $this->assertNotNull($slider->data['items'][1]['image']);
    }

    public function test_product_site_shows_the_launch_hero_steps_and_live_plans(): void
    {
        Plan::where('code', 'basic')->update(['monthly_price' => 3500]);

        $this->get('/')->assertOk()
            ->assertSee('<span class="site-gradient-text">one simple system</span>', false)
            ->assertSee('30-day free trial')
            ->assertSee('Up and running in a day')->assertSee('Bring in your students')
            ->assertSee('id="pricing"', false)->assertSee('Rs 3,500')->assertSee('Ask for the price')->assertSee('Everything included')
            ->assertSee('Questions schools ask')->assertSee('/#features', false);

        // A school's site never shows the owner's plans, even with a plans section.
        $this->publish();
        $this->site->home()->sections()->create(['type' => 'plans', 'data' => ['heading' => 'Our plans'], 'sort' => 99]);
        $this->get('/site/'.$this->admin->school->slug)->assertOk()->assertDontSee('Our plans');
    }

    public function test_a_highlight_cannot_inject_markup_into_the_heading(): void
    {
        $this->publish();
        $editor = Livewire::actingAs($this->admin)->test(PageEditor::class, ['page' => $this->site->home()->id]);
        $editor->call('chooseType', 'hero')->set('form.layout', 'product')->set('form.heading', 'Hello <b>world</b>')->set('form.highlight', '<b>world</b>')->call('saveSection')->assertHasNoErrors();

        auth()->logout();
        $this->get('/site/'.$this->admin->school->slug)->assertSee('&lt;b&gt;world&lt;/b&gt;</span>', false)->assertDontSee('<b>world</b>', false);
    }

    public function test_youtube_links_only_embed_youtube(): void
    {
        $this->publish();
        $editor = Livewire::actingAs($this->admin)->test(PageEditor::class, ['page' => $this->site->home()->id]);
        $editor->call('chooseType', 'video')->set('form.url', 'https://youtu.be/dQw4w9WgXcQ')->call('saveSection')->assertHasNoErrors();
        $editor->call('chooseType', 'video')->set('form.url', 'https://evil.example/embed')->call('saveSection')->assertHasNoErrors();

        auth()->logout();
        $this->get('/site/'.$this->admin->school->slug)->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)->assertDontSee('evil.example', false);
    }
}
