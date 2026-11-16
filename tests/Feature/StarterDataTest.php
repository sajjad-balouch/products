<?php

namespace Tests\Feature;

use App\Livewire\Auth\Signup;
use App\Livewire\Platform\Schools;
use App\Models\AcademicSession;
use App\Models\Biz\Account;
use App\Models\Biz\Category;
use App\Models\Biz\Product;
use App\Models\ClassSubject;
use App\Models\FeeHead;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TimetablePeriod;
use App\Models\User;
use App\Services\StarterData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StarterDataTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, int> */
    private function counts(int $id): array
    {
        $count = fn (string $model) => $model::withoutGlobalScopes()->where('school_id', $id)->count();

        return [
            'classes' => $count(SchoolClass::class), 'sections' => $count(Section::class), 'subjects' => $count(Subject::class),
            'class subjects' => $count(ClassSubject::class), 'periods' => $count(TimetablePeriod::class), 'fee heads' => $count(FeeHead::class),
            'sessions' => $count(AcademicSession::class), 'accounts' => $count(Account::class), 'categories' => $count(Category::class), 'products' => $count(Product::class),
        ];
    }

    public function test_a_new_school_from_signup_is_ready_to_use(): void
    {
        Livewire::test(Signup::class)->set('type', 'school')->set('school', 'Test Public School')->set('name', 'Head')
            ->set('email', 'head@example.test')->set('phone', '03001234567')->set('password', 'password1')->call('register')->assertHasNoErrors();

        $school = School::where('name', 'Test Public School')->firstOrFail();
        $classes = SchoolClass::withoutGlobalScopes()->where('school_id', $school->id)->orderBy('sort')->pluck('name')->all();
        $this->assertSame(['Playgroup', 'Nursery', 'Prep', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6', 'Class 7', 'Class 8', 'Class 9', 'Class 10'], $classes);

        $counts = $this->counts($school->id);
        $this->assertSame(13, $counts['sections']);
        $this->assertSame(1, $counts['sessions']);
        $this->assertSame(4, $counts['fee heads']);
        $this->assertSame(8, $counts['periods']);
        $this->assertGreaterThan(50, $counts['class subjects']);
        $this->assertSame(0, $counts['products']);

        $class9 = SchoolClass::withoutGlobalScopes()->where('school_id', $school->id)->where('name', 'Class 9')->first();
        $this->assertTrue(ClassSubject::withoutGlobalScopes()->where('school_class_id', $class9->id)->whereHas('subject', fn ($q) => $q->where('name', 'Physics'))->exists());

        // The admin, signed in, sees the classes on the Classes page.
        $this->get(route('academics.classes'))->assertOk()->assertSee('Playgroup')->assertSee('Class 10');

        // Running it again adds nothing.
        $this->assertSame([], app(StarterData::class)->seed($school));
        $this->assertSame($counts, $this->counts($school->id));
    }

    public function test_a_college_and_a_school_and_college_get_their_groups(): void
    {
        $owner = User::create(['name' => 'Owner', 'username' => 'owner@example.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);
        Livewire::actingAs($owner)->test(Schools::class)->set('school', 'Test College')->set('type', 'college')->set('name', 'Principal')
            ->set('email', 'college@example.test')->set('phone', '03001234568')->call('create')->assertHasNoErrors();

        $college = School::where('name', 'Test College')->firstOrFail();
        $classes = SchoolClass::withoutGlobalScopes()->where('school_id', $college->id)->get();
        $this->assertSame(10, $classes->count());
        $this->assertTrue($classes->contains(fn ($c) => $c->name === 'XI' && $c->stream === 'Pre-Medical'));
        $this->assertTrue($classes->contains(fn ($c) => $c->name === 'XII' && $c->stream === 'I.Com'));
        $this->assertFalse($classes->contains('name', 'Nursery'));

        $both = app(\App\Services\SchoolProvisioner::class)->create(['school' => 'Both', 'type' => 'both', 'name' => 'P', 'email' => 'both@example.test', 'phone' => '0300', 'password' => 'password'], starterData: true);
        $this->assertSame(23, SchoolClass::withoutGlobalScopes()->where('school_id', $both->school_id)->count());
    }

    public function test_a_new_business_gets_accounts_catalog_and_starter_products_for_its_kind(): void
    {
        Livewire::test(Signup::class)->set('type', 'business')->set('kind', 'motorcycle')->set('school', 'Test Motors')->set('name', 'Owner')
            ->set('email', 'motors@example.test')->set('phone', '03001234569')->set('password', 'password1')->call('register')->assertHasNoErrors();

        $shop = School::where('name', 'Test Motors')->firstOrFail();
        $counts = $this->counts($shop->id);
        $this->assertSame(0, $counts['classes']);
        $this->assertSame(0, $counts['fee heads']);
        $this->assertSame(count(\App\Services\Biz\ChartOfAccounts::DEFAULT), $counts['accounts']);
        $this->assertSame(['Motorcycle parts', 'Motorcycles', 'Other'], Category::withoutGlobalScopes()->where('school_id', $shop->id)->orderBy('name')->pluck('name')->all());

        $products = Product::withoutGlobalScopes()->where('school_id', $shop->id)->with('category')->get();
        $this->assertSame(4, $products->count());
        $cd70 = $products->firstWhere('name', 'CD 70');
        $this->assertSame('Honda', $cd70->brand);
        $this->assertSame('Motorcycles', $cd70->category->name);
        $this->assertNotNull($cd70->model_id);

        // The products page shows them.
        $this->get(route('biz.products'))->assertOk()->assertSee('CD 70')->assertSee('YBR 125');

        $this->assertSame([], app(StarterData::class)->seed($shop));
        $this->assertSame($counts, $this->counts($shop->id));
    }

    public function test_backfill_fills_only_empty_areas_and_never_duplicates(): void
    {
        $school = School::create(['name' => 'Old School', 'slug' => 'old-school', 'type' => 'school']);
        SchoolClass::create(['school_id' => $school->id, 'name' => 'One', 'sort' => 1]);
        $shop = School::create(['name' => 'Old Shop', 'slug' => 'old-shop', 'type' => 'business', 'business_kind' => 'mobile']);

        $this->artisan('accounts:starter-data', ['--dry-run' => true])->expectsOutputToContain('Old School (school): empty: fee heads, sessions')->assertSuccessful();
        $this->assertSame(0, FeeHead::withoutGlobalScopes()->count());

        $this->artisan('accounts:starter-data')->assertSuccessful();

        // The school kept its own class list; it got the session, fee heads and periods it lacked.
        $this->assertSame(['One'], SchoolClass::withoutGlobalScopes()->where('school_id', $school->id)->pluck('name')->all());
        $this->assertSame(4, FeeHead::withoutGlobalScopes()->where('school_id', $school->id)->count());
        $this->assertSame(1, AcademicSession::withoutGlobalScopes()->where('school_id', $school->id)->count());
        $this->assertSame(5, Product::withoutGlobalScopes()->where('school_id', $shop->id)->count());

        $before = [$this->counts($school->id), $this->counts($shop->id)];
        $this->artisan('accounts:starter-data')->expectsOutputToContain('Old Shop: nothing to add')->assertSuccessful();
        $this->assertSame($before, [$this->counts($school->id), $this->counts($shop->id)]);
    }
}
