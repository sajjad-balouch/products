<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_heads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->boolean('is_monthly')->default(true); // only monthly heads go on the bulk monthly run
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_head_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('amount');
            $t->timestamps();
            $t->unique(['school_class_id', 'fee_head_id']);
        });

        // One voucher per family per month: siblings share one challan.
        Schema::create('fee_vouchers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guardian_id')->constrained()->restrictOnDelete();
            $t->string('voucher_no');
            $t->date('month');
            $t->date('due_on');
            $t->integer('subtotal')->default(0);
            $t->unsignedInteger('late_fee')->default(0);
            $t->boolean('late_fee_waived')->default(false);
            $t->unsignedInteger('paid')->default(0);
            $t->string('status')->default('unpaid'); // unpaid, partial, paid, void
            $t->string('void_reason')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'voucher_no']);
            $t->index(['school_id', 'month', 'status']);
            $t->index(['guardian_id', 'month']);
        });

        Schema::create('fee_voucher_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('fee_voucher_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $t->string('label');
            $t->integer('amount'); // discounts are negative
            $t->timestamps();
        });

        Schema::create('cash_closings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->date('date');
            $t->unsignedInteger('expected');
            $t->unsignedInteger('counted');
            $t->integer('difference');
            $t->string('note')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'date']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_voucher_id')->constrained()->restrictOnDelete();
            $t->string('receipt_no');
            $t->unsignedInteger('amount');
            $t->string('method')->default('cash'); // cash, bank, jazzcash, easypaisa, raast, cheque
            $t->string('reference')->nullable();
            $t->date('paid_on');
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            // Payments are never deleted. A correction is a void with a reason.
            $t->timestamp('voided_at')->nullable();
            $t->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('void_reason')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'receipt_no']);
            $t->index(['school_id', 'paid_on']);
        });

        Schema::create('payment_proofs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('fee_voucher_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('amount');
            $t->string('method');
            $t->string('reference');
            $t->string('image_path');
            $t->string('status')->default('pending'); // pending, approved, rejected
            $t->string('review_note')->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'status']);
            $t->index(['school_id', 'method', 'reference']); // duplicate transaction detection
        });

        Schema::create('expenses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('category');
            $t->string('description')->nullable();
            $t->unsignedInteger('amount');
            $t->date('spent_on');
            $t->timestamps();
            $t->index(['school_id', 'spent_on']);
        });

        Schema::create('payrolls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_id')->constrained()->cascadeOnDelete();
            $t->date('month');
            $t->unsignedInteger('basic');
            $t->unsignedInteger('allowance')->default(0);
            $t->unsignedTinyInteger('absent_days')->default(0);
            $t->unsignedInteger('deduction')->default(0);
            $t->unsignedInteger('net');
            $t->date('paid_on')->nullable();
            $t->timestamps();
            $t->unique(['staff_id', 'month']);
        });

        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('academic_session_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->date('starts_on')->nullable();
            $t->boolean('is_published')->default(false);
            $t->timestamps();
        });

        Schema::create('exam_papers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $t->foreignId('school_class_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $t->unsignedSmallInteger('total_marks')->default(100);
            $t->unsignedSmallInteger('pass_marks')->default(33);
            $t->date('paper_on')->nullable();
            $t->timestamps();
            $t->unique(['exam_id', 'school_class_id', 'subject_id']);
        });

        Schema::create('marks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('exam_paper_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->decimal('obtained', 6, 2)->nullable(); // null = absent
            $t->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['exam_paper_id', 'student_id']);
        });

        Schema::create('books', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('author')->nullable();
            $t->string('isbn')->nullable();
            $t->unsignedSmallInteger('copies')->default(1);
            $t->timestamps();
        });

        Schema::create('book_loans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('book_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->date('issued_on');
            $t->date('due_on');
            $t->date('returned_on')->nullable();
            $t->timestamps();
        });

        Schema::create('certificates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('student_id')->constrained()->cascadeOnDelete();
            $t->string('type'); // leaving, character, bonafide
            $t->string('serial');
            $t->string('token', 40)->unique(); // public QR verification
            $t->date('issued_on');
            $t->text('remarks')->nullable();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'serial']);
        });
    }

    public function down(): void
    {
        foreach (['certificates', 'book_loans', 'books', 'marks', 'exam_papers', 'exams', 'payrolls', 'expenses', 'payment_proofs', 'payments', 'cash_closings', 'fee_voucher_lines', 'fee_vouchers', 'fee_structures', 'fee_heads'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
