<?php

namespace Tests\Feature;

use App\Livewire\Platform\SchoolShow;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Services\SiteBuilder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SchoolDeleteTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, int> rows per table that belong to the school */
    private function rowsOf(int $schoolId): array
    {
        // Only this database: listing every database's tables can crash MariaDB when another database is damaged.
        return collect(DB::select("select table_name as t from information_schema.columns where table_schema = database() and column_name = 'school_id'"))
            ->pluck('t')
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->where('school_id', $schoolId)->count()])
            ->filter()
            ->all();
    }

    public function test_deleting_a_school_removes_all_its_data_and_files_but_nothing_else(): void
    {
        Storage::fake('local');
        $this->seed(DatabaseSeeder::class);
        $owner = User::where('role', 'super_admin')->firstOrFail();
        $owner->update(['password' => Hash::make('owner-secret')]);
        $school = User::where('username', 'admin@darsgah.test')->firstOrFail()->school;
        $site = app(SiteBuilder::class)->forSchool($school);

        $other = app(SchoolProvisioner::class)->create(['school' => 'Other School', 'type' => 'school', 'name' => 'Head', 'email' => 'other@example.test', 'phone' => '03000000009', 'password' => 'password'])->school;
        $otherRows = $this->rowsOf($other->id);

        Storage::disk('local')->put('proofs/'.$school->id.'/a.jpg', 'x');
        Storage::disk('local')->put('site-media/'.$site->id.'/b.jpg', 'x');
        Storage::disk('local')->put('proofs/'.$other->id.'/c.jpg', 'x');
        $admin = User::where('username', 'admin@darsgah.test')->first();
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);
        $this->assertGreaterThan(10, count($this->rowsOf($school->id)));

        $page = Livewire::actingAs($owner)->test(SchoolShow::class, ['school' => $school])->call('openDelete')
            ->assertSee('Delete everything');

        // Wrong name or wrong password: nothing happens.
        $page->set('confirmName', 'Some School')->set('ownerPassword', 'owner-secret')->call('deleteSchool')->assertHasErrors('confirmName');
        $page->set('confirmName', $school->name)->set('ownerPassword', 'password1')->call('deleteSchool')->assertHasErrors('ownerPassword');
        $this->assertModelExists($school);

        $page->set('ownerPassword', 'owner-secret')->call('deleteSchool')->assertHasNoErrors()->assertRedirect(route('platform.schools'));

        $this->assertModelMissing($school);
        $this->assertSame([], $this->rowsOf($school->id));
        $this->assertNull(User::where('username', 'admin@darsgah.test')->first());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $admin->id)->count());
        Storage::disk('local')->assertMissing('proofs/'.$school->id.'/a.jpg');
        Storage::disk('local')->assertMissing('site-media/'.$site->id.'/b.jpg');

        // The other school is untouched, and the owner's log keeps a note.
        $this->assertSame($otherRows, $this->rowsOf($other->id));
        Storage::disk('local')->assertExists('proofs/'.$other->id.'/c.jpg');
        $this->assertTrue(AuditLog::whereNull('school_id')->where('action', 'school_deleted')->where('summary', 'like', $school->name.'%')->exists());
        $this->actingAs($owner)->get(route('platform.schools'))->assertOk()->assertSee($school->name.' and all its data were deleted.')
            ->assertDontSee(route('platform.school', $school->id).'"', false);
    }

    public function test_only_the_owner_can_delete_a_school(): void
    {
        $admin = app(SchoolProvisioner::class)->create(['school' => 'School A', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);

        Livewire::actingAs($admin)->test(SchoolShow::class, ['school' => $admin->school])->assertForbidden();
        $this->assertModelExists($admin->school);
    }

    public function test_owner_sets_a_principals_password_and_signs_them_out(): void
    {
        $owner = User::create(['name' => 'Owner', 'username' => 'owner@example.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);
        $admin = app(SchoolProvisioner::class)->create(['school' => 'School A', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $outsider = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'Head B', 'email' => 'b@example.test', 'phone' => '03000000001', 'password' => 'password']);
        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);

        $page = Livewire::actingAs($owner)->test(SchoolShow::class, ['school' => $admin->school])->set('tab', 'users')
            ->assertSee('Change principal\'s password', false);

        Livewire::actingAs($owner)->test(SchoolShow::class, ['school' => $admin->school])->call('openPassword', $outsider->id)->assertNotFound();

        $page->call('openPassword', $admin->id)->set('newPassword', 'short')->set('newPassword_confirmation', 'short')->call('setPassword')->assertHasErrors('newPassword');
        $page->set('newPassword', 'NewSecret123')->set('newPassword_confirmation', 'NewSecret12')->call('setPassword')->assertHasErrors('newPassword');
        $page->set('newPassword_confirmation', 'NewSecret123')->call('setPassword')->assertHasNoErrors()->assertSee('NewSecret123');

        $admin->refresh();
        $this->assertTrue(Hash::check('NewSecret123', $admin->password));
        $this->assertFalse($admin->must_change_password);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $admin->id)->count());

        // A made-up one, which they must change at sign-in.
        $page->call('openPassword', $admin->id)->set('passwordMode', 'generate')->set('mustChange', true)->call('setPassword');
        $made = $page->get('issued')['password'];
        $this->assertSame(10, strlen($made));
        $this->assertTrue(Hash::check($made, $admin->fresh()->password));
        $this->assertTrue($admin->fresh()->must_change_password);
        $this->assertTrue(Hash::check('password', $outsider->fresh()->password));
    }
}
