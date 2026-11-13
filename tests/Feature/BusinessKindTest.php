<?php

namespace Tests\Feature;

use App\Livewire\Auth\Signup;
use App\Livewire\Biz\Products;
use App\Livewire\Biz\Settings;
use App\Models\Biz\Brand;
use App\Models\Biz\Category;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessKindTest extends TestCase
{
    use RefreshDatabase;

    private function business(string $kind, string $email): User
    {
        return app(SchoolProvisioner::class)->create(['school' => ucfirst($kind).' Shop', 'type' => 'business', 'kind' => $kind, 'name' => 'Owner', 'email' => $email, 'phone' => '03000000001', 'password' => 'password']);
    }

    public function test_signup_asks_what_the_business_sells_and_sets_it_up_for_that(): void
    {
        $form = Livewire::test(Signup::class)->set('type', 'business')->assertSee('What does your business sell?')->assertSee('Mobile phone shop')
            ->set('school', 'Talha Mobiles')->set('name', 'Talha')->set('email', 'talha@example.test')->set('phone', '03001234567')->set('password', 'password1');
        $form->call('register')->assertHasErrors('kind');
        $form->set('kind', 'mobile')->call('register')->assertRedirect(route('biz.dashboard'));

        $shop = School::where('name', 'Talha Mobiles')->firstOrFail();
        $this->assertSame('mobile', $shop->business_kind);
        $types = ProductType::withoutGlobalScopes()->where('school_id', $shop->id)->pluck('name')->sort()->values()->all();
        $this->assertSame(['Accessory / general', 'Mobile phone'], $types);
        $this->assertTrue(Category::withoutGlobalScopes()->where('school_id', $shop->id)->where('name', 'Power banks')->exists());
        $this->assertFalse(Category::withoutGlobalScopes()->where('school_id', $shop->id)->where('name', 'Motorcycles')->exists());
        $this->assertTrue(Brand::withoutGlobalScopes()->where('school_id', $shop->id)->where('name', 'Infinix')->exists());
        $this->assertFalse(Brand::withoutGlobalScopes()->where('school_id', $shop->id)->where('name', 'Yamaha')->exists());

        // The product pages can choose the kind in the link.
        Livewire::withQueryParams(['product' => 'business', 'kind' => 'motorcycle'])->test(Signup::class)->assertSet('type', 'business')->assertSet('kind', 'motorcycle');
        // Schools are not asked.
        Livewire::withQueryParams([])->test(Signup::class)->assertSet('type', 'school')->assertDontSee('What does your business sell?');
    }

    public function test_dashboard_and_menu_put_each_business_own_work_first(): void
    {
        $mobile = $this->business('mobile', 'm@example.test');
        $bikes = $this->business('motorcycle', 'b@example.test');

        $groups = fn (User $u) => array_keys(Navigation::for($u));
        $this->assertLessThan(array_search('Installments', $groups($mobile)), array_search('Sales and stock', $groups($mobile)));
        $this->assertLessThan(array_search('Sales and stock', $groups($bikes)), array_search('Installments', $groups($bikes)));

        $this->actingAs($mobile)->get(route('biz.dashboard'))->assertOk()->assertSee('Mobile phone shop')->assertSee('Sold today')->assertSee('Add pieces with IMEI');
        $this->actingAs($bikes)->get(route('biz.dashboard'))->assertOk()->assertSee('Motorcycle dealer')->assertSee('This month&#039;s installments paid', false)->assertSee('Stock in, find engine no.');
    }

    public function test_products_show_only_this_business_catalog_plus_its_own_additions(): void
    {
        $owner = $this->business('general', 'g@example.test');
        $this->actingAs($owner);
        $this->assertSame(count(ProductType::STARTERS), ProductType::count());

        // Switch to a mobile shop: other ready-made types hide, unless in use or made by the owner.
        Livewire::test(Settings::class)->set('kind', 'mobile')->call('save')->assertHasNoErrors();
        $this->assertSame('mobile', $owner->school->fresh()->business_kind);
        Product::create(['type_id' => ProductType::where('name', 'Motorcycle')->value('id'), 'brand' => 'Honda', 'name' => 'CD 70', 'sale_price' => 1]);
        ProductType::create(['name' => 'Smart watch', 'fields' => ProductType::fieldsFrom(['Serial no.'])]);

        $page = Livewire::test(Products::class)->set('tab', 'types');
        $page->assertSee('Mobile phone')->assertSee('Motorcycle')->assertSee('Smart watch')->assertDontSee('Solar system')->assertDontSee('Furniture');
        Livewire::test(Products::class)->set('tab', 'categories')->assertSee('Power banks')->assertDontSee('>Solar<', false);
        Livewire::test(Products::class)->set('tab', 'brands')->assertSee('Infinix')->assertDontSee('Longi');
        $this->get(route('biz.products'))->assertSee('Set up for a mobile phone shop');
    }

    public function test_changing_the_kind_adds_its_catalog_and_keeps_everything_else(): void
    {
        $owner = $this->business('mobile', 'm2@example.test');
        $this->actingAs($owner);
        $phone = Product::create(['type_id' => ProductType::where('name', 'Mobile phone')->value('id'), 'brand' => 'Samsung', 'name' => 'A35', 'sale_price' => 1]);

        Livewire::test(Settings::class)->assertSet('kind', 'mobile')->set('kind', 'motorcycle')->call('save')->assertHasNoErrors();
        $this->assertTrue(ProductType::where('name', 'Motorcycle')->exists());
        $this->assertTrue(Brand::where('name', 'Honda')->exists());
        $this->assertTrue(ProductType::where('name', 'Mobile phone')->exists());
        $this->assertModelExists($phone);
        $this->assertSame('Installments', array_keys(Navigation::for($owner->fresh()))[1]);
    }
}
