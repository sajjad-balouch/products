<?php

namespace Tests\Feature;

use App\Livewire\Academics\Timetable;
use App\Livewire\Attendance\Mark as MarkAttendance;
use App\Livewire\Exams\Marks;
use App\Livewire\Fees\Vouchers;
use App\Livewire\Portal\Home;
use App\Livewire\Students\Index as StudentIndex;
use App\Livewire\Users;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\CashClosing;
use App\Models\Certificate;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Models\Mark;
use App\Models\Message;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Models\TransportRoute;
use App\Models\User;
use App\Services\FeeService;
use App\Services\ResultService;
use App\Services\RiskService;
use App\Services\SchoolProvisioner;
use App\Services\StudentImporter;
use App\Services\TimetableService;
use App\Support\Navigation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class SchoolSystemTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function school(string $name = 'School A'): User
    {
        return app(SchoolProvisioner::class)->create([
            'school' => $name, 'type' => 'school', 'name' => 'Head of '.$name,
            'email' => strtolower(str_replace(' ', '', $name)).'@example.test', 'phone' => '03000000000', 'password' => 'password',
        ]);
    }

    private function user(School $school, string $role, ?string $username = null): User
    {
        return User::create(['school_id' => $school->id, 'name' => ucfirst($role), 'username' => $username ?? $role.$school->id.'@example.test', 'role' => $role, 'password' => Hash::make('password')]);
    }

    private function section(School $school, int $tuition = 1000): Section
    {
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class '.++$this->seq, 'sort' => $this->seq]);
        $head = FeeHead::where('school_id', $school->id)->where('name', 'Tuition Fee')->firstOrFail();
        FeeStructure::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'fee_head_id' => $head->id, 'amount' => $tuition]);

        return Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);
    }

    private function student(Section $section, string $phone = '03001111111', array $extra = []): Student
    {
        $guardian = Guardian::firstOrCreate(['school_id' => $section->school_id, 'phone' => $phone], ['name' => 'Parent '.$phone]);

        return Student::create($extra + ['school_id' => $section->school_id, 'guardian_id' => $guardian->id, 'section_id' => $section->id, 'admission_no' => 'T'.++$this->seq, 'name' => 'Student '.$this->seq]);
    }

    private function voucher(School $school, string $month = '2026-09-01'): FeeVoucher
    {
        app(FeeService::class)->generateMonthly($school, Carbon::parse($month), Carbon::parse($month)->day(10));

        return FeeVoucher::withoutGlobalScopes()->where('school_id', $school->id)->firstOrFail();
    }

    // Tenant isolation

    public function test_a_school_cannot_see_another_schools_records(): void
    {
        $adminA = $this->school('School A');
        $adminB = $this->school('School B');
        $studentA = $this->student($this->section($adminA->school));
        $studentB = $this->student($this->section($adminB->school), '03002222222');

        $this->actingAs($adminA);

        $this->assertSame([$studentA->id], Student::pluck('id')->all());
        $this->assertNull(Student::find($studentB->id));
        $this->get(route('students.show', $studentB->id))->assertNotFound();
    }

    public function test_another_schools_voucher_and_user_cannot_be_reached(): void
    {
        $adminA = $this->school('School A');
        $adminB = $this->school('School B');
        $this->student($this->section($adminB->school), '03002222222');
        $voucherB = $this->voucher($adminB->school);

        $this->actingAs($adminA);
        $this->get(route('print.voucher', $voucherB->id))->assertNotFound();

        Livewire::test(Users::class)->call('toggle', $adminB->id)->assertNotFound();
        $this->assertTrue($adminB->fresh()->is_active);
    }

    public function test_new_records_are_stamped_with_the_users_school(): void
    {
        $admin = $this->school();
        $this->actingAs($admin);

        $subject = Subject::create(['name' => 'Physics']);

        $this->assertSame($admin->school_id, $subject->school_id);
    }

    // Permissions

    public function test_roles_reach_only_their_own_pages(): void
    {
        $admin = $this->school();
        $teacher = $this->user($admin->school, 'teacher');
        $accountant = $this->user($admin->school, 'accountant');

        $this->actingAs($teacher)->get(route('fees.vouchers'))->assertForbidden();
        $this->actingAs($teacher)->get(route('settings'))->assertForbidden();
        $this->actingAs($accountant)->get(route('attendance.mark'))->assertForbidden();
        $this->actingAs($accountant)->get(route('audit'))->assertForbidden();
        $this->actingAs($accountant)->get(route('fees.vouchers'))->assertOk();
        $this->actingAs($teacher)->get(route('attendance.mark'))->assertOk();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_teacher_cannot_mark_a_class_not_assigned_to_them(): void
    {
        $admin = $this->school();
        $mine = $this->section($admin->school);
        $other = $this->section($admin->school);
        $this->student($other);
        $teacher = $this->user($admin->school, 'teacher');
        $staff = Staff::create(['school_id' => $admin->school_id, 'user_id' => $teacher->id, 'name' => 'T']);
        $mine->update(['class_teacher_id' => $staff->id]);

        Livewire::actingAs($teacher)->test(MarkAttendance::class)
            ->set('section_id', (string) $other->id)
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Attendance::withoutGlobalScopes()->count());
    }

    public function test_a_teacher_cannot_enter_marks_for_a_subject_not_assigned_to_them(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school);
        $student = $this->student($section);
        $subject = Subject::create(['school_id' => $admin->school_id, 'name' => 'Math']);
        $exam = Exam::create(['school_id' => $admin->school_id, 'name' => 'Mid']);
        $paper = ExamPaper::create(['school_id' => $admin->school_id, 'exam_id' => $exam->id, 'school_class_id' => $section->school_class_id, 'subject_id' => $subject->id]);
        $teacher = $this->user($admin->school, 'teacher');
        Staff::create(['school_id' => $admin->school_id, 'user_id' => $teacher->id, 'name' => 'T']);

        Livewire::actingAs($teacher)->test(Marks::class)
            ->set('exam_id', (string) $exam->id)
            ->set('paper_id', (string) $paper->id)
            ->set('marks', [$student->id => 90])
            ->call('save')
            ->assertForbidden();

        $this->assertSame(0, Mark::withoutGlobalScopes()->count());
    }

    public function test_marks_above_the_paper_total_are_rejected(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school);
        $student = $this->student($section);
        $subject = Subject::create(['school_id' => $admin->school_id, 'name' => 'Math']);
        $exam = Exam::create(['school_id' => $admin->school_id, 'name' => 'Mid']);
        $paper = ExamPaper::create(['school_id' => $admin->school_id, 'exam_id' => $exam->id, 'school_class_id' => $section->school_class_id, 'subject_id' => $subject->id, 'total_marks' => 50]);

        Livewire::actingAs($admin)->test(Marks::class)
            ->set('exam_id', (string) $exam->id)
            ->set('paper_id', (string) $paper->id)
            ->set('marks', [$student->id => 51])
            ->call('save')
            ->assertHasErrors('marks.'.$student->id);
    }

    // Fees

    public function test_siblings_share_one_voucher_with_discount_from_the_second_child(): void
    {
        $admin = $this->school();
        $admin->school->update(['sibling_discount_percent' => 10]);
        $section = $this->section($admin->school, 2000);
        $this->student($section);
        $this->student($section);

        $voucher = $this->voucher($admin->school->fresh());

        $this->assertSame(1, FeeVoucher::withoutGlobalScopes()->count());
        $this->assertSame(3800, (int) $voucher->subtotal); // 2000 + 2000 - 200
    }

    public function test_concession_cannot_make_a_childs_fee_negative_or_cancel_transport(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school, 1000);
        $route = TransportRoute::create(['school_id' => $admin->school_id, 'name' => 'R', 'monthly_fee' => 500]);
        $this->student($section, '03001111111', ['monthly_concession' => 5000, 'transport_route_id' => $route->id]);

        $this->assertSame(500, (int) $this->voucher($admin->school)->subtotal);
    }

    public function test_generating_twice_does_not_double_bill(): void
    {
        $admin = $this->school();
        $this->student($this->section($admin->school));
        $fees = app(FeeService::class);

        $first = $fees->generateMonthly($admin->school, Carbon::parse('2026-09-01'));
        $second = $fees->generateMonthly($admin->school, Carbon::parse('2026-09-01'));

        $this->assertSame(1, $first['created']);
        $this->assertSame(0, $second['created']);
        $this->assertSame(1, $second['skipped']);
    }

    public function test_families_are_not_billed_for_children_who_left(): void
    {
        $admin = $this->school();
        $this->student($this->section($admin->school), '03001111111', ['status' => 'left']);

        $result = app(FeeService::class)->generateMonthly($admin->school, Carbon::parse('2026-09-01'));

        $this->assertSame(0, $result['created']);
    }

    public function test_payment_updates_status_and_overpayment_is_refused(): void
    {
        $admin = $this->school();
        $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $fees = app(FeeService::class);
        $this->actingAs($admin);

        $fees->recordPayment($voucher, 400);
        $this->assertSame('partial', $voucher->fresh()->status);
        $this->assertSame(600, $voucher->fresh()->balance);

        try {
            $fees->recordPayment($voucher, 601);
            $this->fail('Overpayment was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertSame(400, (int) $voucher->fresh()->paid);
        }

        $fees->recordPayment($voucher, 600);
        $this->assertSame('paid', $voucher->fresh()->status);
    }

    public function test_voiding_a_payment_restores_the_balance_and_needs_a_reason(): void
    {
        $admin = $this->school();
        $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $fees = app(FeeService::class);
        $this->actingAs($admin);
        $payment = $fees->recordPayment($voucher, 1000);

        try {
            $fees->voidPayment($payment, '  ');
            $this->fail('Void without a reason was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertSame('paid', $voucher->fresh()->status);
        }

        $fees->voidPayment($payment, 'Wrong family');

        $this->assertSame('unpaid', $voucher->fresh()->status);
        $this->assertNotNull(Payment::find($payment->id)->voided_at);
    }

    public function test_closed_day_locks_cash_receipts(): void
    {
        $admin = $this->school();
        $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $fees = app(FeeService::class);
        $this->actingAs($admin);
        $payment = $fees->recordPayment($voucher, 500);

        CashClosing::create(['date' => now(), 'expected' => 500, 'counted' => 500, 'difference' => 0]);

        $this->expectException(InvalidArgumentException::class);
        $fees->voidPayment($payment, 'Trying to remove cash after closing');
    }

    public function test_late_fee_is_capped_and_never_compounds(): void
    {
        $admin = $this->school();
        $admin->school->update(['late_fee_per_day' => 50, 'late_fee_max' => 300]);
        $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $fees = app(FeeService::class);

        $fees->applyLateFees($admin->school->fresh(), Carbon::parse('2026-09-13'));
        $this->assertSame(150, (int) $voucher->fresh()->late_fee);

        // Same day again must not add more.
        $fees->applyLateFees($admin->school->fresh(), Carbon::parse('2026-09-13'));
        $this->assertSame(150, (int) $voucher->fresh()->late_fee);

        $fees->applyLateFees($admin->school->fresh(), Carbon::parse('2026-10-30'));
        $this->assertSame(300, (int) $voucher->fresh()->late_fee);
    }

    public function test_waived_late_fee_does_not_come_back(): void
    {
        $admin = $this->school();
        $admin->school->update(['late_fee_per_day' => 50]);
        $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $fees = app(FeeService::class);
        $this->actingAs($admin);

        $fees->applyLateFees($admin->school->fresh(), Carbon::parse('2026-09-13'));
        $fees->waiveLateFee($voucher->fresh(), 'Hardship');
        $fees->applyLateFees($admin->school->fresh(), Carbon::parse('2026-09-20'));

        $this->assertSame(0, (int) $voucher->fresh()->late_fee);
    }

    public function test_paying_the_late_fee_later_keeps_a_paid_voucher_settled(): void
    {
        $admin = $this->school();
        $admin->school->update(['late_fee_per_day' => 50]);
        $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $fees = app(FeeService::class);
        $this->actingAs($admin);
        $fees->recordPayment($voucher, 1000);

        $fees->applyLateFees($admin->school->fresh(), Carbon::parse('2026-10-30'));

        $this->assertSame(0, (int) $voucher->fresh()->late_fee);
        $this->assertSame('paid', $voucher->fresh()->status);
    }

    // Audit

    public function test_payment_changes_are_audited_and_the_log_cannot_be_edited(): void
    {
        $admin = $this->school();
        $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $this->actingAs($admin);
        $payment = app(FeeService::class)->recordPayment($voucher, 1000);
        app(FeeService::class)->voidPayment($payment, 'Mistake');

        $log = AuditLog::where('subject_type', 'Payment')->where('subject_id', $payment->id)->where('action', 'updated')->firstOrFail();
        $this->assertSame('Mistake', $log->changes['new']['void_reason']);
        $this->assertSame($admin->name, $log->user_name);

        $this->expectException(LogicException::class);
        $log->delete();
    }

    // Payment proofs and parent portal

    private function parentFor(Student $student): User
    {
        $parent = $this->user($student->school, 'parent', 'parent'.$student->id);
        $student->guardian->update(['user_id' => $parent->id]);

        return $parent;
    }

    public function test_parent_uploads_proof_and_approval_creates_a_receipt(): void
    {
        Storage::fake('local');
        $admin = $this->school();
        $student = $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $parent = $this->parentFor($student);

        Livewire::actingAs($parent)->test(Home::class)
            ->call('startPay', $voucher->id)
            ->set('amount', 1000)
            ->set('reference', 'TXN12345')
            ->set('screenshot', UploadedFile::fake()->image('pay.png'))
            ->call('submitProof')
            ->assertHasNoErrors();

        $proof = PaymentProof::withoutGlobalScopes()->firstOrFail();
        Storage::disk('local')->assertExists($proof->image_path);
        $this->assertSame('unpaid', $voucher->fresh()->status);

        Auth::logout();
        $this->actingAs($admin);
        $payment = app(FeeService::class)->approveProof($proof);

        $this->assertSame('paid', $voucher->fresh()->status);
        $this->assertSame('TXN12345', $payment->reference);

        $this->expectException(InvalidArgumentException::class);
        app(FeeService::class)->approveProof($proof->fresh());
    }

    public function test_reused_transaction_id_is_flagged(): void
    {
        $admin = $this->school();
        $student = $this->student($this->section($admin->school, 1000));
        $voucher = $this->voucher($admin->school);
        $base = ['school_id' => $admin->school_id, 'fee_voucher_id' => $voucher->id, 'guardian_id' => $student->guardian_id, 'amount' => 100, 'method' => 'jazzcash', 'reference' => 'SAME', 'image_path' => 'x'];

        $first = PaymentProof::create($base);
        $second = PaymentProof::create($base);

        $this->assertTrue($second->is_duplicate);
        $this->assertTrue($first->is_duplicate);
    }

    public function test_a_parent_cannot_reach_another_familys_documents(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school, 1000);
        $mine = $this->student($section, '03001111111');
        $this->student($section, '03002222222');
        app(FeeService::class)->generateMonthly($admin->school, Carbon::parse('2026-09-01'));
        $parent = $this->parentFor($mine);
        $myVoucher = FeeVoucher::where('guardian_id', $mine->guardian_id)->firstOrFail();
        $theirs = FeeVoucher::where('guardian_id', '!=', $mine->guardian_id)->firstOrFail();

        $this->actingAs($parent)->get(route('print.voucher', $myVoucher->id))->assertOk();
        $this->actingAs($parent)->get(route('print.voucher', $theirs->id))->assertForbidden();
        $this->actingAs($parent)->get(route('dashboard'))->assertForbidden();
    }

    public function test_a_parent_cannot_start_paying_another_familys_voucher(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school, 1000);
        $mine = $this->student($section, '03001111111');
        $this->student($section, '03002222222');
        app(FeeService::class)->generateMonthly($admin->school, Carbon::parse('2026-09-01'));
        $parent = $this->parentFor($mine);
        $theirs = FeeVoucher::where('guardian_id', '!=', $mine->guardian_id)->firstOrFail();

        Livewire::actingAs($parent)->test(Home::class)->call('startPay', $theirs->id)->assertNotFound();
    }

    public function test_parents_see_only_published_results(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school);
        $student = $this->student($section);
        $subject = Subject::create(['school_id' => $admin->school_id, 'name' => 'Math']);
        $exam = Exam::create(['school_id' => $admin->school_id, 'name' => 'Hidden Exam']);
        ExamPaper::create(['school_id' => $admin->school_id, 'exam_id' => $exam->id, 'school_class_id' => $section->school_class_id, 'subject_id' => $subject->id]);
        $parent = $this->parentFor($student);

        Livewire::actingAs($parent)->test(Home::class)->set('tab', 'results')->assertDontSee('Hidden Exam');
        $this->actingAs($parent)->get(route('print.report-card', [$exam->id, $student->id]))->assertForbidden();

        $exam->update(['is_published' => true]);
        Livewire::actingAs($parent)->test(Home::class)->set('tab', 'results')->assertSee('Hidden Exam');
    }

    public function test_portal_switches_to_urdu(): void
    {
        $admin = $this->school();
        $student = $this->student($this->section($admin->school));
        $parent = $this->parentFor($student);
        $parent->update(['locale' => 'ur']);

        $this->actingAs($parent)->get(route('portal'))->assertOk()->assertSee('واجب الادا فیس')->assertSee('dir="rtl"', false);
    }

    // Attendance

    public function test_absent_alert_is_queued_once_even_if_saved_twice(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school);
        $student = $this->student($section);

        $page = Livewire::actingAs($admin)->test(MarkAttendance::class)
            ->set('section_id', (string) $section->id)
            ->set('statuses.'.$student->id, 'A');

        $page->call('save');
        $page->call('save');

        $this->assertSame(1, Attendance::count());
        $this->assertSame(1, Message::where('category', 'absent')->count());
    }

    // Timetable

    public function test_a_teacher_cannot_be_double_booked(): void
    {
        $admin = $this->school();
        $first = $this->section($admin->school);
        $second = $this->section($admin->school);
        $subject = Subject::create(['school_id' => $admin->school_id, 'name' => 'Math']);
        $staff = Staff::create(['school_id' => $admin->school_id, 'name' => 'T']);
        $timetable = app(TimetableService::class)->active($admin->school_id);
        $period = TimetablePeriod::create(['school_id' => $admin->school_id, 'timetable_id' => $timetable->id, 'name' => 'P1', 'starts_at' => '08:00', 'ends_at' => '08:45']);
        TimetableEntry::create(['school_id' => $admin->school_id, 'section_id' => $first->id, 'timetable_period_id' => $period->id, 'day' => 1, 'subject_id' => $subject->id, 'staff_id' => $staff->id]);

        Livewire::actingAs($admin)->test(Timetable::class)
            ->set('section_id', (string) $second->id)
            ->call('pick', $period->id, 1)
            ->set('subject_id', (string) $subject->id)
            ->set('staff_id', (string) $staff->id)
            ->call('saveSlot')
            ->assertHasErrors('staff_id');

        $this->assertSame(1, TimetableEntry::count());
    }

    // Results

    public function test_positions_are_shared_on_ties_and_absence_fails_a_paper(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school);
        [$a, $b, $c, $d] = [$this->student($section), $this->student($section), $this->student($section), $this->student($section)];
        $subject = Subject::create(['school_id' => $admin->school_id, 'name' => 'Math']);
        $exam = Exam::create(['school_id' => $admin->school_id, 'name' => 'Mid']);
        $paper = ExamPaper::create(['school_id' => $admin->school_id, 'exam_id' => $exam->id, 'school_class_id' => $section->school_class_id, 'subject_id' => $subject->id, 'total_marks' => 100, 'pass_marks' => 33]);
        foreach ([[$a, 90], [$b, 80], [$c, 80], [$d, null]] as [$student, $score]) {
            Mark::create(['school_id' => $admin->school_id, 'exam_paper_id' => $paper->id, 'student_id' => $student->id, 'obtained' => $score]);
        }
        $this->actingAs($admin);

        $rows = app(ResultService::class)->classResult($exam, $section->school_class_id)->keyBy(fn ($r) => $r['student']->id);

        $this->assertSame(1, $rows[$a->id]['position']);
        $this->assertSame(2, $rows[$b->id]['position']);
        $this->assertSame(2, $rows[$c->id]['position']);
        $this->assertSame(4, $rows[$d->id]['position']);
        $this->assertFalse($rows[$d->id]['passed']);
    }

    // Early warning

    public function test_early_warning_gives_readable_reasons(): void
    {
        $admin = $this->school();
        $section = $this->section($admin->school);
        $flagged = $this->student($section);
        $fine = $this->student($section, '03002222222');
        $today = Carbon::parse('2026-10-20');

        foreach (range(1, 10) as $back) {
            $date = $today->copy()->subDays($back)->toDateString();
            Attendance::create(['school_id' => $admin->school_id, 'student_id' => $flagged->id, 'section_id' => $section->id, 'date' => $date, 'status' => 'A']);
            Attendance::create(['school_id' => $admin->school_id, 'student_id' => $fine->id, 'section_id' => $section->id, 'date' => $date, 'status' => 'P']);
        }

        $rows = app(RiskService::class)->assess($admin->school, $today);

        $this->assertCount(1, $rows);
        $this->assertSame($flagged->id, $rows[0]['student']->id);
        $this->assertSame('high', $rows[0]['level']);
        $this->assertStringContainsString('Attendance 0%', $rows[0]['reasons'][0]);
    }

    // Import

    public function test_import_links_siblings_by_phone_and_normalizes_numbers(): void
    {
        $admin = $this->school();
        $this->actingAs($admin);
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, "\xEF\xBB\xBFname,father_name,phone,class,section,dob\nAli,Raza,0300-1234567,Class 5,A,14/08/2015\nSara,Raza,+92 300 1234567,Class 3,,\n");

        $importer = app(StudentImporter::class);
        $parsed = $importer->parse($path);
        $this->assertSame([], $parsed['errors']);

        $result = $importer->import($admin->school_id, $parsed['rows']);

        $this->assertSame(2, $result['created']);
        $this->assertSame(1, Guardian::count());
        $this->assertSame(2, SchoolClass::count());
        $this->assertSame('2015-08-14', Student::where('name', 'Ali')->first()->dob->toDateString());
        unlink($path);
    }

    public function test_import_reports_bad_rows_without_saving(): void
    {
        $admin = $this->school();
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, "name,phone,class\nAli,12,Class 5\n");

        $parsed = app(StudentImporter::class)->parse($path);

        $this->assertArrayHasKey(2, $parsed['errors']);
        $this->assertStringContainsString('phone', $parsed['errors'][2]);
        unlink($path);
    }

    // Accounts

    public function test_disabled_user_and_suspended_school_are_signed_out(): void
    {
        $admin = $this->school();
        $admin->school->update(['is_active' => false]);

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_first_login_must_change_password(): void
    {
        $admin = $this->school();
        $admin->update(['must_change_password' => true]);

        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('password.change'));
    }

    public function test_signup_creates_a_separate_school_with_a_trial(): void
    {
        $user = $this->school('New School');

        $this->assertSame('trial', $user->school->plan);
        $this->assertTrue($user->school->trial_ends_at->isFuture());
        $this->assertSame('admin', $user->role);
        $this->assertSame(4, FeeHead::where('school_id', $user->school_id)->count());
    }

    public function test_certificate_verification_is_public_and_unknown_tokens_fail(): void
    {
        $admin = $this->school();
        $student = $this->student($this->section($admin->school));
        $certificate = Certificate::create(['school_id' => $admin->school_id, 'student_id' => $student->id, 'type' => 'character', 'serial' => 'C1', 'token' => 'known-token', 'issued_on' => now()]);

        $this->get(route('certificate.verify', 'known-token'))->assertOk()->assertSee($student->name)->assertSee('Genuine');
        $this->get(route('certificate.verify', 'made-up'))->assertNotFound()->assertSee('Not found');
        $this->assertNotNull($certificate->id);
    }

    public function test_every_office_page_loads_for_an_admin(): void
    {
        $admin = $this->school();
        $this->student($this->section($admin->school));

        foreach (Navigation::for($admin) as $links) {
            foreach ($links as [$label, $route]) {
                $this->actingAs($admin)->get(route($route))->assertOk();
            }
        }

        Livewire::actingAs($admin)->test(StudentIndex::class)->assertOk();
        Livewire::actingAs($admin)->test(Vouchers::class)->assertOk();
        $this->assertSame(0, ClassSubject::count());
    }
}
