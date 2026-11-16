<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use App\Support\BusinessKinds;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolProvisioner
{
    /** @param array{school: string, type: string, name: string, email: string, phone: string, password: string} $data */
    public function create(array $data, bool $starterData = false): User
    {
        return DB::transaction(function () use ($data, $starterData) {
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

            // Always: a school's session, fee heads and timetable; a business's chart of accounts and catalog for its kind.
            // With $starterData (sign-up and the owner panel): also classes with section A, subjects and periods,
            // or a few starter products. Seeders and tests that build their own classes leave it off.
            app(StarterData::class)->seed($school, $starterData);

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
