<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureRole;
use App\Livewire\Academics\Sections;
use App\Livewire\Auth\Signup;
use App\Livewire\Certificates;
use App\Livewire\Platform\Schools;
use App\Livewire\Staff\PayrollRun;
use App\Livewire\Students\Form;
use App\Models\Certificate;
use App\Models\Guardian;
use App\Models\Payroll;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_certificate_type_can_be_issued_and_printed_with_a_qr_code(): void
    {
        $admin = $this->admin();
        $class = SchoolClass::create(['school_id' => $admin->school_id, 'name' => 'One']);
        $section = Section::create(['school_id' => $admin->school_id, 'school_class_id' => $class->id, 'name' => 'A']);
        $guardian = Guardian::create(['school_id' => $admin->school_id, 'name' => 'Raza', 'phone' => '03001234567']);
        $student = Student::create(['school_id' => $admin->school_id, 'guardian_id' => $guardian->id, 'section_id' => $section->id, 'admission_no' => 'A1', 'name' => 'Ali Raza']);

        foreach (array_keys(Certificate::TYPES) as $type) {
            Livewire::actingAs($admin)->test(Certificates::class)
                ->set('admission_no', 'A1')->set('type', $type)->call('issue')->assertHasNoErrors();

            $certificate = Certificate::latest('id')->firstOrFail();

            $this->actingAs($admin)->get(route('print.certificate', $certificate->id))
                ->assertOk()->assertSee('Ali Raza')->assertSee('<svg', false)->assertDontSee('<?xml', false);
        }

        $this->assertSame(3, Certificate::distinct()->count('serial'));
    }

    public function test_salary_slip_prints(): void
    {
        $admin = $this->admin();
        Staff::create(['school_id' => $admin->school_id, 'name' => 'Sara', 'basic_salary' => 30000]);

        Livewire::actingAs($admin)->test(PayrollRun::class)->call('generate');

        $this->actingAs($admin)->get(route('print.payslip', Payroll::firstOrFail()->id))
            ->assertOk()->assertSee('Sara')->assertSee('30,000');
    }

    private function admin(string $name = 'School A'): User
    {
        return app(SchoolProvisioner::class)->create([
            'school' => $name, 'type' => 'school', 'name' => 'Head',
            'email' => strtolower(str_replace(' ', '', $name)).'@example.test', 'phone' => '03000000000', 'password' => 'password',
        ]);
    }

    public function test_crud_page_saves_a_record_and_refuses_another_schools_option(): void
    {
        $admin = $this->admin();
        $other = $this->admin('School B');
        $mine = SchoolClass::create(['school_id' => $admin->school_id, 'name' => 'One']);
        $theirs = SchoolClass::create(['school_id' => $other->school_id, 'name' => 'One']);

        $page = Livewire::actingAs($admin)->test(Sections::class)->call('create');

        $page->set('form.school_class_id', $theirs->id)->set('form.name', 'A')->call('save')->assertHasErrors('form.school_class_id');
        $this->assertSame(0, Section::withoutGlobalScopes()->count());

        $page->set('form.school_class_id', $mine->id)->call('save')->assertHasNoErrors();
        $this->assertSame($admin->school_id, Section::withoutGlobalScopes()->first()->school_id);
    }

    public function test_student_form_adds_a_sibling_to_the_existing_family(): void
    {
        $admin = $this->admin();
        $class = SchoolClass::create(['school_id' => $admin->school_id, 'name' => 'One']);
        $section = Section::create(['school_id' => $admin->school_id, 'school_class_id' => $class->id, 'name' => 'A']);
        $family = Guardian::create(['school_id' => $admin->school_id, 'name' => 'Raza', 'phone' => '03001234567']);

        Livewire::actingAs($admin)->test(Form::class)
            ->set('phone', '+92 300 1234567')
            ->set('guardian_name', 'Raza')
            ->set('name', 'Ali')
            ->set('section_id', (string) $section->id)
            ->call('save')
            ->assertHasNoErrors();

        $student = Student::firstOrFail();
        $this->assertSame($family->id, $student->guardian_id);
        $this->assertSame(1, Guardian::count());
        $this->assertNotEmpty($student->admission_no);
    }

    public function test_signup_page_creates_a_school_and_signs_in(): void
    {
        Livewire::test(Signup::class)
            ->set('school', 'Fresh Academy')->set('name', 'Owner')->set('email', 'New@Example.test')
            ->set('phone', '03000000000')->set('password', 'password123')
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertSame('new@example.test', User::where('role', 'admin')->first()->username);
    }

    public function test_every_teacher_and_accountant_page_loads(): void
    {
        $admin = $this->admin();

        foreach (['teacher', 'accountant'] as $role) {
            $user = User::create(['school_id' => $admin->school_id, 'name' => $role, 'username' => $role.'@example.test', 'role' => $role, 'password' => Hash::make('password')]);

            foreach (Navigation::for($user) as $links) {
                foreach ($links as [$label, $route]) {
                    $this->actingAs($user)->get(route($route))->assertOk();
                }
            }
        }
    }

    public function test_only_the_owner_reaches_the_platform_panel(): void
    {
        $admin = $this->admin();
        $owner = User::create(['name' => 'Owner', 'username' => 'owner@example.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);

        $this->actingAs($admin)->get(route('platform.schools'))->assertForbidden();
        $this->actingAs($owner)->get(route('platform.schools'))->assertOk()->assertSee('School A');

        Livewire::actingAs($owner)->test(Schools::class)->call('toggle', $admin->school_id);
        $this->assertFalse(School::find($admin->school_id)->is_active);
    }

    public function test_livewire_actions_recheck_the_role(): void
    {
        $this->assertContains(
            EnsureRole::class,
            app(PersistentMiddleware::class)->getPersistentMiddleware(),
        );
    }
}
