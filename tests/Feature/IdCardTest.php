<?php

namespace Tests\Feature;

use App\Livewire\Cards;
use App\Models\AuditLog;
use App\Models\Guardian;
use App\Models\PickupPerson;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Support\IdCards;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class IdCardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $this->admin = User::where('username', 'admin@darsgah.test')->firstOrFail();
    }

    public function test_design_photos_and_logo_are_saved_for_the_school_only(): void
    {
        $student = Student::withoutGlobalScopes()->where('school_id', $this->admin->school_id)->where('status', 'active')->firstOrFail();

        $this->actingAs($this->admin)->get(route('cards'))->assertOk()->assertSee('Choose a design')->assertSee($student->name);

        $page = Livewire::actingAs($this->admin)->test(Cards::class)
            ->set('template', 'horizon')->set('primary', '#1e3a8a')->set('accent', '#f59e0b')->set('showBack', false)
            ->assertHasNoErrors();
        $page->set('primary', 'red')->assertHasErrors('primary');
        $page->set('template', 'nope')->assertHasErrors('template');

        $settings = School::find($this->admin->school_id)->card_settings;
        $this->assertSame(['horizon', '#1e3a8a', false], [$settings['template'], $settings['primary'], $settings['show_back']]);

        // A photo for one student, shown only to this school.
        Livewire::actingAs($this->admin)->test(Cards::class)
            ->set('photoFor', 'student:'.$student->id)->set('photo', UploadedFile::fake()->image('me.jpg', 300, 400))
            ->assertHasNoErrors();
        $path = $student->fresh()->photo_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->actingAs($this->admin)->get(route('cards.photo', ['student', $student->id]))->assertOk();

        // A text file is not a photo.
        Livewire::actingAs($this->admin)->test(Cards::class)
            ->set('photoFor', 'student:'.$student->id)->set('photo', UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'))
            ->assertHasErrors('photo');

        $other = app(SchoolProvisioner::class)->create(['school' => 'Other', 'type' => 'school', 'name' => 'Head', 'email' => 'o@example.test', 'phone' => '03000000099', 'password' => 'password']);
        $this->actingAs($other)->get(route('cards.photo', ['student', $student->id]))->assertNotFound();
        Livewire::actingAs($other)->test(Cards::class)->set('photoFor', 'student:'.$student->id)
            ->set('photo', UploadedFile::fake()->image('x.jpg'))->assertNotFound();

        // Logo, then removing the photo deletes the file.
        Livewire::actingAs($this->admin)->test(Cards::class)->set('logo', UploadedFile::fake()->image('logo.png', 200, 200))->assertHasNoErrors();
        $this->actingAs($this->admin)->get(route('cards.asset', 'logo'))->assertOk();
        $this->actingAs($this->admin)->get(route('cards.asset', 'signature'))->assertNotFound();
        Livewire::actingAs($this->admin)->test(Cards::class)->call('removePhoto', 'student:'.$student->id);
        Storage::disk('local')->assertMissing($path);
        $this->assertNull($student->fresh()->photo_path);
    }

    public function test_cards_print_for_a_class_one_person_or_parents_and_only_for_the_office(): void
    {
        $section = Section::withoutGlobalScopes()->where('school_id', $this->admin->school_id)->whereHas('students')->firstOrFail();
        $students = Student::withoutGlobalScopes()->where('section_id', $section->id)->where('status', 'active')->orderBy('name')->get();
        [$first, $second] = [$students[0], $students[1]];

        $sheet = $this->actingAs($this->admin)->get(route('print.cards', ['type' => 'student', 'layout' => 'a4', 'section' => $section->id, 'excluded' => $second->cardKey()]))
            ->assertOk()->assertSee($first->name)->assertSee($first->admission_no)->assertDontSee('>'.$second->name.'<', false)
            ->assertSee('If found, please return it', false)->assertSee('9 per page');
        $this->assertSame($students->count() - 1, substr_count($sheet->getContent(), 'class="idc t-classic'));

        // One card, for a PVC card printer, with the back side after it.
        $one = $this->actingAs($this->admin)->get(route('print.cards', ['type' => 'student', 'layout' => 'card', 'only' => $second->cardKey()]))->assertOk()->assertSee($second->name);
        $this->assertSame(1, substr_count($one->getContent(), 'class="idc t-classic'));
        $this->assertStringContainsString('size: 54mm 85.6mm', $one->getContent());

        // Parent cards list the children.
        $guardian = Guardian::withoutGlobalScopes()->whereKey($first->guardian_id)->firstOrFail();
        $this->actingAs($this->admin)->get(route('print.cards', ['type' => 'guardian', 'download' => 'a4', 'search' => $guardian->phone]))
            ->assertOk()->assertSee($guardian->name)->assertSee($first->name)->assertSee('Parent card')->assertSee('may pick up the children');

        $this->actingAs($this->admin)->get(route('print.cards', ['type' => 'teacher', 'layout' => 'a4']))->assertRedirect();
        $this->actingAs($this->admin)->get(route('print.id-cards', $section->id))->assertRedirect(route('cards', ['section' => $section->id]));

        $teacher = User::where('username', 'teacher@darsgah.test')->first();
        $this->actingAs($teacher)->get(route('cards'))->assertForbidden();
        $this->actingAs($teacher)->get(route('print.cards', ['type' => 'student', 'layout' => 'a4']))->assertForbidden();

        // The feature can be switched off for a school.
        School::find($this->admin->school_id)->update(['features_off' => ['certificates']]);
        $this->actingAs($this->admin->fresh())->get(route('cards'))->assertForbidden();
    }

    public function test_a_family_can_add_anyone_who_may_pick_up_and_each_gets_a_card(): void
    {
        $student = Student::withoutGlobalScopes()->where('school_id', $this->admin->school_id)->where('status', 'active')->firstOrFail();
        $family = Guardian::withoutGlobalScopes()->findOrFail($student->guardian_id);

        $page = Livewire::actingAs($this->admin)->test(Cards::class)->set('type', 'guardian');

        // Name and relation are needed, and the CNIC must look like one.
        $page->call('openPerson', 'new:'.$family->id)->set('personName', '')->set('personRelation', 'Uncle')->call('savePerson')->assertHasErrors('personName');
        $page->set('personName', 'Kamran Ali')->set('personRelation', 'Chef')->call('savePerson')->assertHasErrors('personRelation');
        $page->set('personRelation', 'Uncle')->set('personPhone', '03211234567')->set('personCnic', 'abc')->call('savePerson')->assertHasErrors('personCnic');
        $page->set('personCnic', '36302-1234567-1')->call('savePerson')->assertHasNoErrors()->assertSet('personTarget', null);

        $uncle = PickupPerson::where('guardian_id', $family->id)->firstOrFail();
        $this->assertSame([$this->admin->school_id, 'Uncle', '36302-1234567-1'], [$uncle->school_id, $uncle->relation, $uncle->cnic]);
        $this->assertTrue(AuditLog::where('subject_type', 'PickupPerson')->where('action', 'created')->exists());

        // The main guardian's relation can be set; their name stays with the family record.
        $page->call('openPerson', 'guardian:'.$family->id)->set('personRelation', 'Grandfather')->call('savePerson')->assertHasNoErrors();
        $this->assertSame('Grandfather', $family->fresh()->relation);

        // A photo for the uncle, and he appears right after his family's main guardian.
        $page->set('photoFor', $uncle->cardKey())->set('photo', UploadedFile::fake()->image('u.jpg', 300, 400))->assertHasNoErrors();
        $this->actingAs($this->admin)->get(route('cards.photo', ['pickup', $uncle->id]))->assertOk();
        $keys = IdCards::people('guardian')->map->cardKey()->values();
        $this->assertSame($keys->search($family->cardKey()) + 1, $keys->search($uncle->cardKey()));

        // His card shows him, his relation, who allowed him and the children.
        $this->actingAs($this->admin)->get(route('print.cards', ['type' => 'guardian', 'layout' => 'card', 'only' => $uncle->cardKey()]))
            ->assertOk()->assertSee('Kamran Ali')->assertSee('Uncle')->assertSee('Pickup card')->assertSee($family->name)->assertSee($student->name)
            ->assertSee('A-'.str_pad((string) $uncle->id, 5, '0', STR_PAD_LEFT));

        // Searching by his name finds the family, and he can be left out of a print.
        $this->actingAs($this->admin)->get(route('print.cards', ['type' => 'guardian', 'layout' => 'a4', 'search' => 'Kamran', 'excluded' => $uncle->cardKey()]))
            ->assertOk()->assertSee($family->name)->assertDontSee('Kamran Ali');

        // Another school cannot see or change him.
        $other = app(SchoolProvisioner::class)->create(['school' => 'Other', 'type' => 'school', 'name' => 'Head', 'email' => 'o2@example.test', 'phone' => '03000000098', 'password' => 'password']);
        $this->actingAs($other)->get(route('cards.photo', ['pickup', $uncle->id]))->assertNotFound();
        Livewire::actingAs($other)->test(Cards::class)->call('deletePerson', $uncle->id)->assertNotFound();

        $photo = $uncle->fresh()->photo_path;
        Storage::disk('local')->assertExists($photo);
        Livewire::actingAs($this->admin)->test(Cards::class)->set('type', 'guardian')->call('deletePerson', $uncle->id);
        $this->assertModelMissing($uncle);
        Storage::disk('local')->assertMissing($photo);
    }
}
