<?php

namespace App\Services;

use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Models\Message;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/** Builds the outbox. Staff send each message with one tap through WhatsApp. */
class MessageService
{
    public function queue(Guardian $guardian, string $category, string $body): Message
    {
        return Message::create([
            'school_id' => $guardian->school_id,
            'guardian_id' => $guardian->id,
            'created_by' => Auth::id(),
            'phone' => $guardian->phone,
            'category' => $category,
            'body' => $body,
        ]);
    }

    public function absentAlert(Student $student, Carbon $date): Message
    {
        $school = $student->school->name;

        return $this->queue($student->guardian, 'absent', "Assalam o Alaikum. {$student->name} aaj ({$date->format('j M')}) school mein hazir nahi hain. Agar chutti ki wajah hai to baraye meharbani school ko aagah karein. - {$school}");
    }

    public function feeReminder(Guardian $guardian, int $balance, ?FeeVoucher $voucher = null): Message
    {
        $school = $guardian->school->name;
        $due = $voucher ? ' Voucher '.$voucher->voucher_no.'.' : '';

        return $this->queue($guardian, 'fee', "Assalam o Alaikum {$guardian->name}. Aap ke zimme Rs ".number_format($balance)." fee baqi hai.{$due} Baraye meharbani jald ada karein, ya app se payment proof upload karein. Shukriya. - {$school}");
    }
}
