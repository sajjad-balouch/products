<?php

namespace App\Livewire\Students;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\FeeVoucher;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\MessageService;
use App\Services\ResultService;
use App\Services\RiskService;
use App\Support\Phone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class Profile extends Component
{
    public Student $student;

    public ?string $newPassword = null;

    /** Creates or resets the parent's login. The password is shown once and only its hash is kept. */
    public function createParentLogin(MessageService $messages): void
    {
        abort_unless(Auth::user()->isRole('admin'), 403);

        $guardian = $this->student->guardian;
        $password = Str::password(10, symbols: false);
        $user = $guardian->user;

        if ($user) {
            $user->update(['password' => Hash::make($password), 'must_change_password' => true]);
        } else {
            $username = User::where('username', $guardian->phone)->exists()
                ? $guardian->phone.'-'.Auth::user()->school->slug
                : $guardian->phone;

            $user = User::create([
                'school_id' => $guardian->school_id,
                'name' => $guardian->name,
                'username' => $username,
                'phone' => $guardian->phone,
                'role' => 'parent',
                'locale' => 'ur',
                'password' => Hash::make($password),
                'must_change_password' => true,
            ]);
            $guardian->update(['user_id' => $user->id]);
        }

        AuditLog::record('parent_login_issued', $guardian, null, null, 'Login issued for '.$user->username);
        $this->newPassword = $password;
    }

    public function render(ResultService $results, RiskService $risk)
    {
        $student = $this->student->load(['section.schoolClass', 'guardian.students', 'transportRoute']);

        $attendance = Attendance::where('student_id', $student->id)->whereDate('date', '>=', now()->subDays(30))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $days = $attendance->sum();
        $attended = ($attendance['P'] ?? 0) + ($attendance['L'] ?? 0);

        $exams = Exam::whereHas('papers', fn ($q) => $q->where('school_class_id', $student->section->school_class_id))
            ->latest('starts_on')->limit(4)->get()->map(function ($exam) use ($results, $student) {
                $row = $results->classResult($exam, $student->section->school_class_id)->firstWhere(fn ($r) => $r['student']->id === $student->id);

                return $row ? ['exam' => $exam, 'row' => $row] : null;
            })->filter();

        $flag = $risk->assess(School::findOrFail($student->school_id))->first(fn ($r) => $r['student']->id === $student->id);

        return view('livewire.students.profile', [
            'attendancePercent' => $days ? round($attended / $days * 100) : null,
            'attendance' => $attendance,
            'vouchers' => FeeVoucher::where('guardian_id', $student->guardian_id)->latest('month')->limit(8)->get(),
            'exams' => $exams,
            'flag' => $flag,
            'whatsappPassword' => $this->newPassword
                ? 'https://wa.me/'.Phone::international($student->guardian->phone).'?text='.rawurlencode('Assalam o Alaikum. Parent app login: '.route('login')." | Username: {$student->guardian->user?->username} | Password: {$this->newPassword}")
                : null,
        ])->title($student->name);
    }
}
