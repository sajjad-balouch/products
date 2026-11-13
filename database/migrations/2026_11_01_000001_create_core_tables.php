<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->string('type')->default('school'); // school | college | both
            $t->string('phone')->nullable();
            $t->string('email')->nullable();
            $t->string('address')->nullable();
            $t->string('principal_name')->nullable();
            $t->string('plan')->default('trial');
            $t->date('trial_ends_at')->nullable();
            $t->boolean('is_active')->default(true);
            // Fee rules. All money in this system is whole PKR, stored as integers.
            $t->unsignedTinyInteger('fee_due_day')->default(10);
            $t->unsignedInteger('late_fee_per_day')->default(0);
            $t->unsignedInteger('late_fee_max')->default(0);
            $t->unsignedTinyInteger('sibling_discount_percent')->default(0);
            $t->text('bank_details')->nullable();
            $t->string('jazzcash_number')->nullable();
            $t->string('easypaisa_number')->nullable();
            $t->boolean('absent_alerts')->default(true);
            $t->unsignedTinyInteger('risk_attendance_threshold')->default(75);
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('school_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $t->string('username')->nullable()->unique()->after('name');
            $t->string('role')->default('admin')->after('username'); // super_admin, admin, accountant, teacher, parent, student
            $t->string('phone')->nullable()->after('role');
            $t->string('locale', 5)->default('en')->after('phone');
            $t->boolean('is_active')->default(true)->after('locale');
            $t->boolean('must_change_password')->default(false)->after('is_active');
            $t->string('email')->nullable()->change();
        });

        Schema::create('academic_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->boolean('is_current')->default(false);
            $t->timestamps();
        });

        Schema::create('staff', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('designation')->nullable();
            $t->string('phone')->nullable();
            $t->string('cnic')->nullable();
            $t->date('joined_on')->nullable();
            $t->unsignedInteger('basic_salary')->default(0);
            $t->unsignedInteger('allowance')->default(0);
            $t->boolean('is_teacher')->default(true);
            $t->string('status')->default('active');
            $t->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('stream')->nullable(); // college group, e.g. Pre-Medical
            $t->unsignedSmallInteger('sort')->default(0); // promotion order
            $t->timestamps();
            $t->unique(['school_id', 'name', 'stream']);
        });

        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->foreignId('class_teacher_id')->nullable()->constrained('staff')->nullOnDelete();
            $t->unsignedSmallInteger('capacity')->default(40);
            $t->timestamps();
            $t->unique(['school_class_id', 'name']);
        });

        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('code')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        Schema::create('class_subjects', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_class_id', 'subject_id']);
        });

        Schema::create('transport_routes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('vehicle_no')->nullable();
            $t->string('driver_name')->nullable();
            $t->string('driver_phone')->nullable();
            $t->unsignedInteger('monthly_fee')->default(0);
            $t->timestamps();
        });

        Schema::create('guardians', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->string('phone');
            $t->string('cnic')->nullable();
            $t->string('occupation')->nullable();
            $t->string('address')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'phone']); // same phone = same family, which links siblings
        });

        Schema::create('students', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guardian_id')->constrained()->restrictOnDelete();
            $t->foreignId('section_id')->constrained()->restrictOnDelete();
            $t->foreignId('transport_route_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('admission_no');
            $t->string('roll_no')->nullable();
            $t->string('name');
            $t->string('father_name')->nullable();
            $t->string('gender', 10)->default('male');
            $t->date('dob')->nullable();
            $t->string('b_form')->nullable();
            $t->date('admitted_on')->nullable();
            $t->unsignedInteger('monthly_concession')->default(0);
            $t->string('concession_reason')->nullable();
            $t->string('status')->default('active'); // active | left | graduated
            $t->date('left_on')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'admission_no']);
            $t->index(['school_id', 'status']);
        });

        Schema::create('inquiries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $t->string('student_name');
            $t->string('guardian_name')->nullable();
            $t->string('phone');
            $t->string('source')->default('walk-in');
            $t->string('stage')->default('new'); // new, contacted, visited, tested, admitted, lost
            $t->date('follow_up_on')->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'stage']);
        });

        Schema::create('attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->char('status', 1); // P A L(ate) V(leave)
            $t->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['student_id', 'date']);
            $t->index(['section_id', 'date']);
            $t->index(['school_id', 'date']);
        });

        Schema::create('staff_attendances', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->char('status', 1);
            $t->timestamps();
            $t->unique(['staff_id', 'date']);
        });

        Schema::create('timetable_periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('starts_at', 5);
            $t->string('ends_at', 5);
            $t->unsignedTinyInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('timetable_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('timetable_period_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('day'); // 1 = Monday
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $t->timestamps();
            $t->unique(['section_id', 'timetable_period_id', 'day'], 'tt_section_slot');
            $t->unique(['staff_id', 'timetable_period_id', 'day'], 'tt_teacher_slot'); // a teacher cannot be in two rooms
        });

        Schema::create('diaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('section_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->date('date');
            $t->text('body');
            $t->timestamps();
            $t->index(['section_id', 'date']);
        });

        Schema::create('announcements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->text('body');
            $t->date('published_on');
            $t->timestamps();
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guardian_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('phone');
            $t->string('category')->default('general'); // absent, fee, general, login
            $t->text('body');
            $t->string('status')->default('queued'); // queued | sent
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'status']);
        });

        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('user_name')->nullable(); // survives user deletion
            $t->string('action');
            $t->string('subject_type')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->text('summary')->nullable();
            $t->json('changes')->nullable();
            $t->string('ip', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['school_id', 'created_at']);
            $t->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        foreach (['audit_logs', 'messages', 'announcements', 'diaries', 'timetable_entries', 'timetable_periods', 'staff_attendances', 'attendances', 'inquiries', 'students', 'guardians', 'transport_routes', 'class_subjects', 'subjects', 'sections', 'school_classes', 'staff', 'academic_sessions'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('school_id');
            $t->dropColumn(['username', 'role', 'phone', 'locale', 'is_active', 'must_change_password']);
        });
        Schema::dropIfExists('schools');
    }
};
