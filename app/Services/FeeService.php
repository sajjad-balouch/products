<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashClosing;
use App\Models\FeeStructure;
use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\School;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FeeService
{
    /**
     * Creates one family voucher per guardian for the month. Running it twice
     * only fills in families that were missed, so it is safe to re-run.
     *
     * @return array{created: int, skipped: int, skipped_no_fee: int}
     */
    public function generateMonthly(School $school, Carbon $month, ?Carbon $dueOn = null, ?int $classId = null): array
    {
        $month = $month->copy()->startOfMonth();
        $dueOn ??= $month->copy()->day(min($school->fee_due_day, $month->daysInMonth));

        $structures = FeeStructure::withoutGlobalScopes()->where('school_id', $school->id)
            ->whereHas('feeHead', fn ($q) => $q->where('is_monthly', true))
            ->with('feeHead')->get()->groupBy('school_class_id');

        $students = Student::withoutGlobalScopes()->where('school_id', $school->id)->where('status', 'active')
            ->with(['section' => fn ($q) => $q->withoutGlobalScopes(), 'transportRoute' => fn ($q) => $q->withoutGlobalScopes()])
            ->when($classId, fn ($q) => $q->whereHas('section', fn ($s) => $s->withoutGlobalScopes()->where('school_class_id', $classId)))
            ->orderBy('id')->get()->groupBy('guardian_id');

        $result = ['created' => 0, 'skipped' => 0, 'skipped_no_fee' => 0];

        foreach ($students as $guardianId => $children) {
            $exists = FeeVoucher::withoutGlobalScopes()->where('school_id', $school->id)
                ->where('guardian_id', $guardianId)->whereDate('month', $month)->where('status', '!=', 'void')->exists();

            if ($exists) {
                $result['skipped']++;

                continue;
            }

            $lines = $this->buildLines($children, $structures, $school, $month);

            if (! $lines) {
                $result['skipped_no_fee']++;

                continue;
            }

            DB::transaction(function () use ($school, $guardianId, $month, $dueOn, $lines) {
                $voucher = FeeVoucher::create([
                    'school_id' => $school->id,
                    'guardian_id' => $guardianId,
                    'voucher_no' => $this->nextNumber($school->id, FeeVoucher::class, 'voucher_no', 'V'.$month->format('ym').'-'),
                    'month' => $month,
                    'due_on' => $dueOn,
                    'subtotal' => max(0, array_sum(array_column($lines, 'amount'))),
                    'created_by' => Auth::id(),
                ]);
                $voucher->lines()->createMany($lines);
            });

            $result['created']++;
        }

        return $result;
    }

    /** Student lines, oldest child first. Second child onward gets the sibling discount on class fees. */
    private function buildLines($children, $structures, School $school, Carbon $month): array
    {
        $lines = [];
        $position = 0;

        foreach ($children as $child) {
            $heads = $structures->get($child->section->school_class_id, collect());
            $classTotal = (int) $heads->sum('amount');

            foreach ($heads as $structure) {
                $lines[] = ['student_id' => $child->id, 'label' => $structure->feeHead->name, 'amount' => $structure->amount];
            }

            // A concession or sibling discount can only reduce this child's class fee, never below zero.
            $remaining = $classTotal;

            if ($child->monthly_concession > 0 && $remaining > 0) {
                $off = min($child->monthly_concession, $remaining);
                $lines[] = ['student_id' => $child->id, 'label' => 'Concession'.($child->concession_reason ? " ({$child->concession_reason})" : ''), 'amount' => -$off];
                $remaining -= $off;
            }

            if ($position > 0 && $school->sibling_discount_percent > 0 && $remaining > 0) {
                $off = (int) round($remaining * $school->sibling_discount_percent / 100);
                if ($off > 0) {
                    $lines[] = ['student_id' => $child->id, 'label' => "Sibling discount {$school->sibling_discount_percent}%", 'amount' => -$off];
                }
            }

            if ($child->transportRoute && $child->transportRoute->monthly_fee > 0) {
                $lines[] = ['student_id' => $child->id, 'label' => 'Transport: '.$child->transportRoute->name, 'amount' => $child->transportRoute->monthly_fee];
            }

            $position++;
        }

        return $lines;
    }

    public function recordPayment(FeeVoucher $voucher, int $amount, string $method = 'cash', ?string $reference = null, ?Carbon $paidOn = null): Payment
    {
        return DB::transaction(function () use ($voucher, $amount, $method, $reference, $paidOn) {
            $voucher = FeeVoucher::lockForUpdate()->findOrFail($voucher->id);
            $paidOn ??= now();

            if ($voucher->status === 'void') {
                throw new InvalidArgumentException('This voucher has been voided.');
            }
            if ($amount <= 0) {
                throw new InvalidArgumentException('Amount must be greater than zero.');
            }
            if ($amount > $voucher->balance) {
                throw new InvalidArgumentException("Amount is more than the balance of Rs {$voucher->balance}.");
            }
            if ($method === 'cash' && $this->isDayClosed($voucher->school_id, $paidOn)) {
                throw new InvalidArgumentException('Cash for this date is already closed.');
            }

            $payment = Payment::create([
                'school_id' => $voucher->school_id,
                'fee_voucher_id' => $voucher->id,
                'receipt_no' => $this->nextNumber($voucher->school_id, Payment::class, 'receipt_no', 'R'.$paidOn->format('ym').'-'),
                'amount' => $amount,
                'method' => $method,
                'reference' => $reference,
                'paid_on' => $paidOn,
                'received_by' => Auth::id(),
            ]);

            $this->recalculate($voucher);

            return $payment;
        });
    }

    public function voidPayment(Payment $payment, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required to void a payment.');
        }
        if ($payment->voided_at) {
            throw new InvalidArgumentException('This payment is already voided.');
        }
        if ($payment->method === 'cash' && $this->isDayClosed($payment->school_id, $payment->paid_on)) {
            throw new InvalidArgumentException('Cash for this date is closed, so this receipt can no longer be voided.');
        }

        DB::transaction(function () use ($payment, $reason) {
            $payment->update(['voided_at' => now(), 'voided_by' => Auth::id(), 'void_reason' => $reason]);
            $this->recalculate(FeeVoucher::lockForUpdate()->findOrFail($payment->fee_voucher_id));
        });
    }

    public function voidVoucher(FeeVoucher $voucher, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required to void a voucher.');
        }
        if ($voucher->payments()->valid()->exists()) {
            throw new InvalidArgumentException('Void the payments on this voucher first.');
        }

        $voucher->update(['status' => 'void', 'void_reason' => $reason]);
    }

    public function waiveLateFee(FeeVoucher $voucher, string $reason): void
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A reason is required to waive a late fee.');
        }

        DB::transaction(function () use ($voucher, $reason) {
            $amount = $voucher->late_fee;
            $voucher->update(['late_fee_waived' => true, 'late_fee' => 0]);
            $this->recalculate($voucher->fresh());
            AuditLog::record('late_fee_waived', $voucher, null, null, "Waived Rs {$amount}: {$reason}");
        });
    }

    /**
     * Late fee is recomputed from the due date, not added to, so running this
     * every day can never charge a family twice.
     */
    public function applyLateFees(School $school, ?Carbon $today = null): int
    {
        $today = ($today ?? now())->copy()->startOfDay();

        if ($school->late_fee_per_day <= 0) {
            return 0;
        }

        $updated = 0;
        FeeVoucher::withoutGlobalScopes()->where('school_id', $school->id)->open()
            ->where('late_fee_waived', false)->whereDate('due_on', '<', $today)
            ->chunkById(200, function ($vouchers) use ($school, $today, &$updated) {
                foreach ($vouchers as $voucher) {
                    $days = (int) $voucher->due_on->diffInDays($today);
                    $fee = $days * $school->late_fee_per_day;
                    if ($school->late_fee_max > 0) {
                        $fee = min($fee, $school->late_fee_max);
                    }
                    if ($fee !== (int) $voucher->late_fee) {
                        $voucher->update(['late_fee' => $fee]);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    public function approveProof(PaymentProof $proof): Payment
    {
        return DB::transaction(function () use ($proof) {
            $proof = PaymentProof::lockForUpdate()->findOrFail($proof->id);

            if ($proof->status !== 'pending') {
                throw new InvalidArgumentException('This proof has already been reviewed.');
            }

            $voucher = $proof->voucher;
            $payment = $this->recordPayment($voucher, min($proof->amount, $voucher->balance), $proof->method, $proof->reference);

            $proof->update(['status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now(), 'payment_id' => $payment->id]);
            AuditLog::record('proof_approved', $proof, null, null, "Rs {$payment->amount}, {$proof->method} {$proof->reference}");

            return $payment;
        });
    }

    public function rejectProof(PaymentProof $proof, string $note): void
    {
        if ($proof->status !== 'pending') {
            throw new InvalidArgumentException('This proof has already been reviewed.');
        }
        if (trim($note) === '') {
            throw new InvalidArgumentException('Tell the parent why the proof was rejected.');
        }

        $proof->update(['status' => 'rejected', 'review_note' => $note, 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);
        AuditLog::record('proof_rejected', $proof, null, null, $note);
    }

    /** Cash the system expects in the drawer for one date. */
    public function expectedCash(int $schoolId, Carbon $date): int
    {
        return (int) Payment::where('school_id', $schoolId)->valid()->where('method', 'cash')->whereDate('paid_on', $date)->sum('amount');
    }

    public function isDayClosed(int $schoolId, Carbon $date): bool
    {
        return CashClosing::withoutGlobalScopes()->where('school_id', $schoolId)->whereDate('date', $date)->exists();
    }

    /** Family's unpaid total from vouchers other than this one, printed as previous dues. */
    public function arrears(FeeVoucher $voucher): int
    {
        return FeeVoucher::where('guardian_id', $voucher->guardian_id)->open()->where('id', '!=', $voucher->id)
            ->whereDate('month', '<', $voucher->month)->get()->sum('balance');
    }

    private function recalculate(FeeVoucher $voucher): void
    {
        $paid = (int) $voucher->payments()->valid()->sum('amount');

        $voucher->paid = $paid;
        if ($voucher->status !== 'void') {
            $voucher->status = match (true) {
                $paid <= 0 => 'unpaid',
                $paid >= $voucher->total => 'paid',
                default => 'partial',
            };
        }
        $voucher->save();
    }

    private function nextNumber(int $schoolId, string $model, string $column, string $prefix): string
    {
        // Called inside a transaction; the row lock keeps two cashiers from taking the same number.
        $last = $model::withoutGlobalScopes()->where('school_id', $schoolId)
            ->where($column, 'like', $prefix.'%')->lockForUpdate()->orderByDesc('id')->value($column);

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
