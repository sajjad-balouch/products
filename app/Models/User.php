<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    public const ROLES = ['admin' => 'Principal / Admin', 'accountant' => 'Accountant', 'teacher' => 'Teacher', 'website_editor' => 'Website editor'];

    protected $guarded = ['id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function guardian()
    {
        return $this->hasOne(Guardian::class);
    }

    public function staffProfile()
    {
        return $this->hasOne(Staff::class);
    }

    public function isRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Sections this user may take attendance and write diary for. Null means every section. */
    public function teachableSectionIds(): ?array
    {
        if ($this->isRole('admin', 'super_admin')) {
            return null;
        }

        $staffId = $this->staffProfile?->id;
        if (! $staffId) {
            return [];
        }

        return Section::where('class_teacher_id', $staffId)->pluck('id')
            ->merge(TimetableEntry::live()->where('staff_id', $staffId)->pluck('section_id'))
            ->merge(Section::whereIn('school_class_id', ClassSubject::where('staff_id', $staffId)->pluck('school_class_id'))->pluck('id'))
            ->unique()->values()->all();
    }

    /** Admins may enter any marks; teachers only for a class and subject assigned to them. */
    public function canEnterMarks(ExamPaper $paper): bool
    {
        if ($this->isRole('admin')) {
            return true;
        }

        $staffId = $this->staffProfile?->id;

        return $staffId && ClassSubject::where('staff_id', $staffId)
            ->where('school_class_id', $paper->school_class_id)
            ->where('subject_id', $paper->subject_id)->exists();
    }

    public function homeRoute(): string
    {
        return match ($this->role) {
            'super_admin' => 'platform.dashboard',
            'parent', 'student' => 'portal',
            'website_editor' => 'website.pages',
            'salesman' => 'app',
            default => $this->school?->isBusiness() ? 'biz.dashboard' : 'dashboard',
        };
    }
}
