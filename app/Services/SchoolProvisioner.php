<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\FeeHead;
use App\Models\School;
use App\Models\Timetable;
use App\Models\User;
use App\Services\Biz\ChartOfAccounts;
use App\Services\Biz\StarterCatalog;
use App\Support\BusinessKinds;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolProvisioner
{
    /** @param array{school: string, type: string, name: string, email: string, phone: string, password: string} $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $school = School::create([
                'name' => $data['school'],
                'slug' => $this->uniqueSlug($data['school']),
                'type' => $data['type'],
                'business_kind' => $data['type'] === 'business' && isset(BusinessKinds::KINDS[$data['kind'] ?? '']) ? $data['kind'] : null,
                'phone' => $data['phone'],
                'email' => $data['email'],
                'principal_name' => $data['name'],
                'plan' => 'trial',
                'trial_ends_at' => now()->addDays(6),
                'access_until' => now()->addDays(6),
                'features_off' => ['sms'], // automatic SMS costs money: it comes with a plan that includes it
            ]);

            // A business gets its chart of accounts instead of classes, sessions and fee heads.
            if ($school->isBusiness()) {
                ChartOfAccounts::seed($school);
                // Only what this kind of business sells: a mobile shop gets phones and accessories, not motorcycles.
                StarterCatalog::seed($school->id, $school->business_kind);

                return $this->admin($school, $data);
            }

            $year = now()->month >= 8 ? now()->year : now()->year - 1;
            AcademicSession::create([
                'school_id' => $school->id,
                'name' => $year.'-'.substr((string) ($year + 1), -2),
                'starts_on' => "$year-08-01",
                'ends_on' => ($year + 1).'-07-31',
                'is_current' => true,
            ]);

            foreach (['Tuition Fee' => true, 'Admission Fee' => false, 'Annual Charges' => false, 'Exam Fee' => false] as $head => $monthly) {
                FeeHead::create(['school_id' => $school->id, 'name' => $head, 'is_monthly' => $monthly]);
            }

            Timetable::create(['school_id' => $school->id, 'name' => 'Main timetable', 'is_active' => true]);

            return $this->admin($school, $data);
        });
    }

    private function admin(School $school, array $data): User
    {
        return User::create([
            'school_id' => $school->id,
            'name' => $data['name'],
            'username' => Str::lower($data['email']),
            'email' => Str::lower($data['email']),
            'phone' => $data['phone'],
            'role' => 'admin',
            'password' => Hash::make($data['password']),
        ]);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'school';
        $slug = $base;
        $n = 2;

        while (School::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$n++;
        }

        return $slug;
    }
}
