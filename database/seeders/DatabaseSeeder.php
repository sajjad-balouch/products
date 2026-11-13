<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassSubject;
use App\Models\Diary;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\Expense;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Models\Inquiry;
use App\Models\Mark;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Models\TransportRoute;
use App\Models\User;
use App\Services\FeeService;
use App\Services\MessageService;
use App\Services\SchoolProvisioner;
use App\Services\TimetableService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

/** Fictional demo school for trying the system. Every login uses the password "password". */
class DatabaseSeeder extends Seeder
{
    private const BOYS = ['Ali', 'Hamza', 'Usman', 'Bilal', 'Ahmed', 'Hassan', 'Zain', 'Saad', 'Talha', 'Omar', 'Faizan', 'Danish', 'Rayan', 'Arham', 'Haris'];

    private const GIRLS = ['Ayesha', 'Fatima', 'Zainab', 'Maryam', 'Hira', 'Sana', 'Iqra', 'Laiba', 'Noor', 'Amna', 'Khadija', 'Areeba', 'Minahil', 'Eman', 'Sidra'];

    private const FAMILY = ['Khan', 'Ahmad', 'Malik', 'Raza', 'Sheikh', 'Butt', 'Qureshi', 'Siddiqui', 'Chaudhry', 'Mirza', 'Ansari', 'Abbasi'];

    public function run(): void
    {
        throw_if(app()->isProduction(), RuntimeException::class, 'The demo seeder must not run in production.');

        mt_srand(42);

        User::create(['name' => 'Platform Owner', 'username' => 'owner@darsgah.test', 'email' => 'owner@darsgah.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);

        $admin = app(SchoolProvisioner::class)->create([
            'school' => 'Demo Public School and College', 'type' => 'both', 'name' => 'Principal Demo',
            'email' => 'admin@darsgah.test', 'phone' => '03000000001', 'password' => 'password',
        ]);
        $school = $admin->school;
        $school->update([
            'address' => '12 Demo Road, Multan', 'fee_due_day' => 10, 'late_fee_per_day' => 20, 'late_fee_max' => 500,
            'sibling_discount_percent' => 10, 'jazzcash_number' => '03000000002', 'easypaisa_number' => '03000000003',
            'bank_details' => "Demo Bank, Multan Branch\nAccount: Demo Public School\nIBAN: PK00DEMO0000000000000000",
        ]);

        Auth::setUser($admin);

        $accountant = User::create(['school_id' => $school->id, 'name' => 'Accounts Office', 'username' => 'accounts@darsgah.test', 'email' => 'accounts@darsgah.test', 'role' => 'accountant', 'password' => Hash::make('password')]);

        [$teachers, $teacherUser] = $this->staff($school, $admin);
        [$sections, $subjects] = $this->academics($school, $teachers);
        $routes = $this->transport($school);
        $this->feeStructure($school);
        $students = $this->students($school, $sections, $routes);
        $this->timetable($school, $sections, $subjects, $teachers);
        $this->attendance($school, $students);
        $this->exams($school, $students, $teacherUser);

        Auth::setUser($accountant);
        $this->fees($school);
        $this->extras($school, $admin, $students);

        Auth::logout();
        $this->call(DemoWebsiteSeeder::class);
        $this->call(DemoBusinessSeeder::class);
        $this->command?->info('Demo ready. Logins: admin@darsgah.test, accounts@darsgah.test, teacher@darsgah.test, 03001000001 (parent), owner@darsgah.test, shop@darsgah.test (business). Password: password');
    }

    private function staff(School $school, User $admin): array
    {
        $people = [['Sara Malik', 'Senior Teacher'], ['Imran Qureshi', 'Teacher'], ['Nadia Butt', 'Teacher'], ['Kamran Ansari', 'Lecturer'], ['Rabia Siddiqui', 'Lecturer'], ['Tariq Mirza', 'Teacher'], ['Asma Raza', 'Teacher']];
        $teachers = collect();

        foreach ($people as $i => [$name, $designation]) {
            $teachers->push(Staff::create(['school_id' => $school->id, 'name' => $name, 'designation' => $designation, 'phone' => '0301200000'.$i, 'joined_on' => now()->subYears(2), 'basic_salary' => 35000 + $i * 2500, 'allowance' => 3000]));
        }
        Staff::create(['school_id' => $school->id, 'name' => 'Sajid Peon', 'designation' => 'Peon', 'category' => 'support', 'basic_salary' => 25000, 'is_teacher' => false]);
        Staff::create(['school_id' => $school->id, 'name' => 'Naseem Bibi', 'designation' => 'Aya (junior section)', 'category' => 'support', 'basic_salary' => 24000, 'is_teacher' => false]);
        Staff::create(['school_id' => $school->id, 'name' => 'Akram Khan', 'designation' => 'Night guard', 'category' => 'security', 'duty_starts_at' => '20:00', 'duty_ends_at' => '08:00', 'basic_salary' => 28000, 'is_teacher' => false]);
        Staff::create(['school_id' => $school->id, 'name' => 'Rafiq Ahmed', 'designation' => 'Gate guard', 'category' => 'security', 'duty_starts_at' => '07:00', 'duty_ends_at' => '15:00', 'basic_salary' => 27000, 'is_teacher' => false]);
        Staff::create(['school_id' => $school->id, 'name' => 'Javed Iqbal', 'designation' => 'Van driver', 'category' => 'transport', 'duty_starts_at' => '06:30', 'duty_ends_at' => '15:30', 'basic_salary' => 30000, 'is_teacher' => false]);
        Staff::create(['school_id' => $school->id, 'name' => 'Shazia Noor', 'designation' => 'Office clerk', 'category' => 'office', 'basic_salary' => 32000, 'is_teacher' => false]);

        $teacherUser = User::create(['school_id' => $school->id, 'name' => 'Sara Malik', 'username' => 'teacher@darsgah.test', 'email' => 'teacher@darsgah.test', 'role' => 'teacher', 'password' => Hash::make('password')]);
        $teachers[0]->update(['user_id' => $teacherUser->id]);

        return [$teachers, $teacherUser];
    }

    private function academics(School $school, $teachers): array
    {
        $subjects = collect(['English', 'Urdu', 'Mathematics', 'Science', 'Islamiat', 'Physics', 'Chemistry', 'Biology'])
            ->mapWithKeys(fn ($n) => [$n => Subject::create(['school_id' => $school->id, 'name' => $n])]);

        $classes = [
            ['Class 6', null, 6, ['English', 'Urdu', 'Mathematics', 'Science', 'Islamiat'], ['A', 'B']],
            ['Class 7', null, 7, ['English', 'Urdu', 'Mathematics', 'Science', 'Islamiat'], ['A']],
            ['Class 8', null, 8, ['English', 'Urdu', 'Mathematics', 'Science', 'Islamiat'], ['A']],
            ['Class 9', null, 9, ['English', 'Urdu', 'Mathematics', 'Physics', 'Chemistry'], ['A']],
            ['Class 10', null, 10, ['English', 'Urdu', 'Mathematics', 'Physics', 'Chemistry'], ['A']],
            ['XI', 'Pre-Medical', 11, ['English', 'Physics', 'Chemistry', 'Biology'], ['A']],
            ['XII', 'Pre-Medical', 12, ['English', 'Physics', 'Chemistry', 'Biology'], ['A']],
        ];

        $sections = collect();
        $t = 0;

        foreach ($classes as [$name, $stream, $sort, $subjectNames, $sectionNames]) {
            $class = SchoolClass::create(['school_id' => $school->id, 'name' => $name, 'stream' => $stream, 'sort' => $sort]);

            foreach ($subjectNames as $s => $subjectName) {
                ClassSubject::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'subject_id' => $subjects[$subjectName]->id, 'staff_id' => $teachers[($sort + $s) % $teachers->count()]->id]);
            }

            foreach ($sectionNames as $sectionName) {
                $sections->push(Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => $sectionName, 'class_teacher_id' => $teachers[$t++ % $teachers->count()]->id, 'capacity' => 30]));
            }
        }

        return [$sections, $subjects];
    }

    private function transport(School $school)
    {
        return collect([
            TransportRoute::create(['school_id' => $school->id, 'name' => 'Gulgasht Colony', 'vehicle_no' => 'MNA-1234', 'driver_name' => 'Aslam', 'driver_phone' => '03014000001', 'monthly_fee' => 2500]),
            TransportRoute::create(['school_id' => $school->id, 'name' => 'Cantt', 'vehicle_no' => 'MNB-5678', 'driver_name' => 'Rafiq', 'driver_phone' => '03014000002', 'monthly_fee' => 3000]),
        ]);
    }

    private function feeStructure(School $school): void
    {
        $tuition = FeeHead::where('school_id', $school->id)->where('name', 'Tuition Fee')->first();
        $lab = FeeHead::create(['school_id' => $school->id, 'name' => 'Lab Charges', 'is_monthly' => true]);

        foreach (SchoolClass::where('school_id', $school->id)->get() as $class) {
            FeeStructure::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'fee_head_id' => $tuition->id, 'amount' => 3000 + ($class->sort - 6) * 500]);
            if ($class->sort >= 9) {
                FeeStructure::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'fee_head_id' => $lab->id, 'amount' => 800]);
            }
        }
    }

    private function students(School $school, $sections, $routes)
    {
        $students = collect();
        $phone = 1;
        $admission = 1;

        // The first family is the demo parent login: two children, different classes.
        $parent = User::create(['school_id' => $school->id, 'name' => 'Tariq Khan', 'username' => '03001000001', 'phone' => '03001000001', 'role' => 'parent', 'locale' => 'en', 'password' => Hash::make('password')]);
        $family = Guardian::create(['school_id' => $school->id, 'user_id' => $parent->id, 'name' => 'Tariq Khan', 'phone' => '03001000001', 'address' => 'House 5, Multan']);

        foreach ($sections as $s => $section) {
            for ($i = 1; $i <= 11; $i++) {
                $female = mt_rand(0, 1) === 1;
                $last = self::FAMILY[mt_rand(0, count(self::FAMILY) - 1)];

                if ($i === 1 && $s <= 1) {
                    $guardian = $family;
                    $last = 'Khan';
                } elseif ($i % 3 === 2 && $students->count() > 5 && mt_rand(0, 2) === 0) {
                    $guardian = $students->random()->guardian; // sibling
                    $last = explode(' ', $guardian->name)[1] ?? $last;
                } else {
                    $phone++;
                    $father = self::BOYS[mt_rand(0, count(self::BOYS) - 1)].' '.$last;
                    $guardian = Guardian::create(['school_id' => $school->id, 'name' => $father, 'phone' => '0300'.str_pad((string) (1000000 + $phone), 7, '0', STR_PAD_LEFT), 'address' => 'Street '.mt_rand(1, 40).', Multan']);
                }

                $names = $female ? self::GIRLS : self::BOYS;
                $students->push(Student::create([
                    'school_id' => $school->id, 'guardian_id' => $guardian->id, 'section_id' => $section->id,
                    'admission_no' => 'A25-'.str_pad((string) $admission++, 4, '0', STR_PAD_LEFT),
                    'roll_no' => (string) $i, 'name' => $names[mt_rand(0, 14)].' '.$last, 'father_name' => $guardian->name,
                    'gender' => $female ? 'female' : 'male', 'dob' => now()->subYears(11 + $s)->subDays(mt_rand(0, 300)),
                    'admitted_on' => now()->subMonths(mt_rand(6, 30)),
                    'transport_route_id' => mt_rand(0, 4) === 0 ? $routes->random()->id : null,
                    'monthly_concession' => mt_rand(0, 9) === 0 ? 500 : 0,
                    'concession_reason' => null,
                ]));
            }
        }

        return $students->each->load('section');
    }

    private function timetable(School $school, $sections, $subjects, $teachers): void
    {
        $service = app(TimetableService::class);
        $timetable = $service->active($school->id);
        $timetable->update(['name' => 'Summer timetable']);

        TimetablePeriod::create(['school_id' => $school->id, 'timetable_id' => $timetable->id, 'name' => 'Break', 'starts_at' => '10:15', 'ends_at' => '10:45', 'is_break' => true]);

        $periods = collect([['Period 1', '08:00', '08:45'], ['Period 2', '08:45', '09:30'], ['Period 3', '09:30', '10:15'], ['Period 4', '10:45', '11:30'], ['Period 5', '11:30', '12:15']])
            ->map(fn ($p, $i) => TimetablePeriod::create(['school_id' => $school->id, 'timetable_id' => $timetable->id, 'name' => $p[0], 'starts_at' => $p[1], 'ends_at' => $p[2], 'sort' => $i + 1]));

        foreach ($sections as $s => $section) {
            $assigned = ClassSubject::where('school_class_id', $section->school_class_id)->get();

            foreach (range(1, 6) as $day) {
                foreach ($periods as $p => $period) {
                    $assignment = $assigned[($p + $day) % $assigned->count()];
                    // Rotating by section avoids double bookings; with more sections than teachers, the rest stay unassigned.
                    $staffId = $s < $teachers->count() ? $teachers[($p + $day + $s) % $teachers->count()]->id : null;

                    TimetableEntry::create(['school_id' => $school->id, 'section_id' => $section->id, 'timetable_period_id' => $period->id, 'day' => $day, 'subject_id' => $assignment->subject_id, 'staff_id' => $staffId]);
                }
            }
        }

        $service->duplicate($timetable, 'Winter timetable (draft)');
    }

    private function attendance(School $school, $students): void
    {
        $days = collect(range(1, 40))->map(fn ($back) => now()->subDays($back)->startOfDay())->reject(fn ($d) => $d->isSunday())->take(26);
        // A few students with a clear pattern, so early warning has something real to show.
        $poor = $students->slice(10, 4)->pluck('id')->all();
        $streak = $students->slice(30, 2)->pluck('id')->all();
        $rows = [];

        foreach ($students as $student) {
            foreach ($days as $i => $day) {
                $roll = mt_rand(1, 100);
                $status = match (true) {
                    in_array($student->id, $streak, true) && $i < 4 => 'A',
                    in_array($student->id, $poor, true) => $roll <= 40 ? 'A' : 'P',
                    $roll <= 5 => 'A',
                    $roll <= 8 => 'L',
                    $roll <= 10 => 'V',
                    default => 'P',
                };
                $rows[] = ['school_id' => $school->id, 'student_id' => $student->id, 'section_id' => $student->section_id, 'date' => $day->toDateString(), 'status' => $status, 'created_at' => now(), 'updated_at' => now()];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            Attendance::insert($chunk);
        }

        $staffDays = $days->take(20);
        foreach (Staff::where('school_id', $school->id)->get() as $person) {
            $start = Carbon::createFromFormat('H:i', $person->duty_starts_at ?: '08:00');
            foreach ($staffDays as $day) {
                $roll = mt_rand(1, 40);
                $status = match (true) {
                    $roll === 1 => 'A', $roll === 2 => 'V', $roll === 3 => 'H', $roll <= 6 => 'L', default => 'P'
                };
                $in = in_array($status, ['A', 'V'], true) ? null : $start->copy()->addMinutes($status === 'L' ? mt_rand(15, 40) : mt_rand(-15, 8))->format('H:i');
                StaffAttendance::create(['school_id' => $school->id, 'staff_id' => $person->id, 'date' => $day->toDateString(), 'status' => $status, 'check_in' => $in]);
            }
        }
    }

    private function exams($school, $students, User $teacherUser): void
    {
        $exams = [
            Exam::create(['school_id' => $school->id, 'name' => 'First Monthly Test', 'starts_on' => now()->subMonths(2), 'is_published' => true]),
            Exam::create(['school_id' => $school->id, 'name' => 'Mid Term', 'starts_on' => now()->subDays(20), 'is_published' => true]),
        ];
        $strugglers = $students->slice(10, 3)->pluck('id')->all();
        $droppers = $students->slice(45, 3)->pluck('id')->all();

        foreach ($exams as $e => $exam) {
            $rows = [];
            foreach (SchoolClass::where('school_id', $school->id)->get() as $class) {
                $classStudents = $students->filter(fn ($s) => $s->section->school_class_id === $class->id);

                foreach (ClassSubject::where('school_class_id', $class->id)->get() as $assignment) {
                    $paper = ExamPaper::create(['school_id' => $school->id, 'exam_id' => $exam->id, 'school_class_id' => $class->id, 'subject_id' => $assignment->subject_id, 'total_marks' => 100, 'pass_marks' => 33]);

                    foreach ($classStudents as $student) {
                        $score = match (true) {
                            in_array($student->id, $strugglers, true) => mt_rand(15, 38),
                            in_array($student->id, $droppers, true) => $e === 0 ? mt_rand(75, 90) : mt_rand(40, 58),
                            default => mt_rand(45, 96),
                        };
                        $rows[] = ['school_id' => $school->id, 'exam_paper_id' => $paper->id, 'student_id' => $student->id, 'obtained' => $score, 'entered_by' => $teacherUser->id, 'created_at' => now(), 'updated_at' => now()];
                    }
                }
            }
            foreach (array_chunk($rows, 500) as $chunk) {
                Mark::insert($chunk);
            }
        }
    }

    private function fees(School $school): void
    {
        $service = app(FeeService::class);

        foreach (range(5, 0) as $back) {
            $month = now()->startOfMonth()->subMonths($back);
            $service->generateMonthly($school, Carbon::parse($month));

            $vouchers = FeeVoucher::where('school_id', $school->id)->whereDate('month', $month)->get();
            foreach ($vouchers as $voucher) {
                // Older months are mostly collected. The current month has barely started.
                $chance = $back === 0 ? 15 : ($back === 1 ? 70 : 92);
                if (mt_rand(1, 100) > $chance) {
                    continue;
                }

                $paidOn = $month->copy()->addDays($back === 0 ? 0 : mt_rand(1, 14));
                $amount = mt_rand(1, 12) === 1 ? (int) round($voucher->balance / 2) : $voucher->balance;
                $method = ['cash', 'cash', 'cash', 'bank', 'jazzcash', 'easypaisa'][mt_rand(0, 5)];
                $service->recordPayment($voucher, $amount, $method, $method === 'cash' ? null : 'TXN'.mt_rand(100000, 999999), Carbon::parse($paidOn));
            }
        }

        $service->applyLateFees($school);

        foreach (range(1, 8) as $back) {
            Expense::create(['school_id' => $school->id, 'category' => ['Rent', 'Utilities', 'Stationery', 'Maintenance'][$back % 4], 'description' => 'Demo expense', 'amount' => mt_rand(5, 60) * 1000, 'spent_on' => now()->subDays($back * 18)]);
        }
    }

    private function extras(School $school, User $admin, $students): void
    {
        foreach ([['Parent-teacher meeting', 'The parent-teacher meeting is on Saturday at 10 am. Please bring your child\'s diary.'], ['Winter uniform', 'Winter uniform is required from 15 November.']] as $i => [$title, $body]) {
            Announcement::create(['school_id' => $school->id, 'title' => $title, 'body' => $body, 'published_on' => now()->subDays($i * 3)]);
        }

        foreach (Section::where('school_id', $school->id)->take(3)->get() as $section) {
            foreach (range(1, 3) as $back) {
                Diary::create(['school_id' => $school->id, 'section_id' => $section->id, 'subject_id' => Subject::where('school_id', $school->id)->inRandomOrder()->value('id'), 'date' => now()->subDays($back), 'body' => 'Revise the last chapter and complete exercise '.mt_rand(1, 9).'.', 'created_by' => $admin->id]);
            }
        }

        $stages = ['new', 'new', 'contacted', 'contacted', 'visited', 'tested', 'admitted', 'lost'];
        foreach ($stages as $i => $stage) {
            Inquiry::create(['school_id' => $school->id, 'school_class_id' => SchoolClass::where('school_id', $school->id)->inRandomOrder()->value('id'), 'student_name' => self::BOYS[$i].' '.self::FAMILY[$i], 'guardian_name' => self::BOYS[$i + 3].' '.self::FAMILY[$i], 'phone' => '0333100000'.$i, 'source' => ['walk-in', 'facebook', 'whatsapp', 'referral'][$i % 4], 'stage' => $stage, 'follow_up_on' => $i < 5 ? now()->addDays($i - 2) : null]);
        }

        $messages = app(MessageService::class);
        foreach ($students->take(3) as $student) {
            $messages->absentAlert($student->fresh(['guardian', 'school']), now()->subDay());
        }
    }
}
