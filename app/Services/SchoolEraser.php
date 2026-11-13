<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\School;
use App\Models\Site;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Removes a school for good: every record (the database deletes them with the school) and its uploaded files. */
class SchoolEraser
{
    /** @return array<string, int> what will be lost, for the confirmation screen */
    public function summary(School $school): array
    {
        $id = $school->id;

        return [
            'students' => Student::withoutGlobalScopes()->where('school_id', $id)->count(),
            'families' => Guardian::withoutGlobalScopes()->where('school_id', $id)->count(),
            'staff' => Staff::withoutGlobalScopes()->where('school_id', $id)->count(),
            'logins' => User::where('school_id', $id)->count(),
            'vouchers' => FeeVoucher::withoutGlobalScopes()->where('school_id', $id)->count(),
            'payments' => Payment::withoutGlobalScopes()->where('school_id', $id)->count(),
            'website pages' => Site::where('school_id', $id)->withCount('pages')->first()?->pages_count ?? 0,
        ];
    }

    public function erase(School $school): void
    {
        $userIds = User::where('school_id', $school->id)->pluck('id');
        $siteIds = Site::where('school_id', $school->id)->pluck('id');
        $summary = $this->summary($school);

        DB::transaction(function () use ($school, $userIds) {
            // Signed-in users of the school are thrown out at once.
            DB::table('sessions')->whereIn('user_id', $userIds)->delete();

            // These links refuse a delete, to protect money and student records in normal use, so they go first.
            foreach (['payments', 'fee_vouchers', 'students', 'biz_receipts', 'biz_journal_lines', 'biz_stock_units', 'biz_invoice_lines', 'biz_purchases', 'biz_contracts'] as $table) {
                DB::table($table)->where('school_id', $school->id)->delete();
            }

            // Everything else is removed by the database along with the school.
            $school->delete();
        });

        // Files live outside the database, so they go after the records are gone.
        $disk = Storage::disk('local');
        $disk->deleteDirectory('proofs/'.$school->id);
        $disk->deleteDirectory('photos/'.$school->id);
        foreach ($siteIds as $siteId) {
            $disk->deleteDirectory('site-media/'.$siteId);
        }

        // Kept in the owner's own log, since the school's log is deleted with it.
        AuditLog::create([
            'school_id' => null,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name ?? 'System',
            'action' => 'school_deleted',
            'subject_type' => 'School',
            'subject_id' => $school->id,
            'summary' => $school->name.' deleted with all its data ('.collect($summary)->map(fn ($n, $what) => $n.' '.$what)->implode(', ').')',
            'ip' => request()?->ip(),
        ]);
    }
}
