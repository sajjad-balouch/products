<?php

namespace App\Services;

use App\Models\Guardian;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Support\Phone;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Moves a school off Excel. The same phone number links siblings into one
 * family, and classes and sections are created as they appear in the file.
 */
class StudentImporter
{
    public const COLUMNS = ['admission_no', 'name', 'father_name', 'phone', 'class', 'section', 'gender', 'dob', 'roll_no', 'address', 'monthly_concession'];

    /** @return array{rows: array<int, array>, errors: array<int, string>} */
    public function parse(string $path): array
    {
        $handle = fopen($path, 'r');
        $rawHeader = fgetcsv($handle) ?: [];
        // Excel adds a byte-order mark when saving "CSV UTF-8".
        $header = array_map(fn ($h) => strtolower(trim(str_replace(' ', '_', preg_replace('/^\xEF\xBB\xBF/', '', (string) $h)))), $rawHeader);

        $errors = [];
        foreach (['name', 'phone', 'class'] as $required) {
            if (! in_array($required, $header, true)) {
                $errors[1] = "Column \"$required\" is missing from the first row.";
            }
        }

        $rows = [];
        $line = 1;
        while (! $errors && ($values = fgetcsv($handle)) !== false) {
            $line++;
            if (count(array_filter($values, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $row = array_intersect_key(array_combine($header, array_pad(array_slice($values, 0, count($header)), count($header), '')), array_flip(self::COLUMNS));
            $row = array_map(fn ($v) => trim((string) $v), $row) + array_fill_keys(self::COLUMNS, '');
            $row['phone'] = Phone::normalize($row['phone']);

            $problems = [];
            if ($row['name'] === '') {
                $problems[] = 'name is empty';
            }
            if (strlen($row['phone']) < 10) {
                $problems[] = 'phone is not valid';
            }
            if ($row['class'] === '') {
                $problems[] = 'class is empty';
            }

            if ($problems) {
                $errors[$line] = implode(', ', $problems);
            } else {
                $row['line'] = $line;
                $rows[] = $row;
            }
        }
        fclose($handle);

        return ['rows' => $rows, 'errors' => $errors];
    }

    /** All rows are saved together, or none are. @return array{created: int, updated: int} */
    public function import(int $schoolId, array $rows): array
    {
        return DB::transaction(function () use ($schoolId, $rows) {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $class = SchoolClass::firstOrCreate(['school_id' => $schoolId, 'name' => $row['class'], 'stream' => null], ['sort' => (int) preg_replace('/\D/', '', $row['class'])]);
                $section = Section::firstOrCreate(['school_id' => $schoolId, 'school_class_id' => $class->id, 'name' => $row['section'] ?: 'A']);
                $guardian = Guardian::firstOrCreate(['school_id' => $schoolId, 'phone' => $row['phone']], ['name' => $row['father_name'] ?: 'Guardian of '.$row['name'], 'address' => $row['address'] ?: null]);

                $student = $row['admission_no'] !== ''
                    ? Student::where('school_id', $schoolId)->where('admission_no', $row['admission_no'])->first()
                    : null;

                $values = [
                    'guardian_id' => $guardian->id,
                    'section_id' => $section->id,
                    'name' => $row['name'],
                    'father_name' => $row['father_name'] ?: null,
                    'gender' => strtolower(substr($row['gender'], 0, 1)) === 'f' ? 'female' : 'male',
                    'dob' => $this->date($row['dob']),
                    'roll_no' => $row['roll_no'] ?: null,
                    'monthly_concession' => max(0, (int) $row['monthly_concession']),
                ];

                if ($student) {
                    $student->update($values);
                    $updated++;
                } else {
                    Student::create($values + [
                        'school_id' => $schoolId,
                        'admission_no' => $row['admission_no'] ?: static::nextAdmissionNo($schoolId),
                        'admitted_on' => now(),
                    ]);
                    $created++;
                }
            }

            return ['created' => $created, 'updated' => $updated];
        });
    }

    public static function nextAdmissionNo(int $schoolId): string
    {
        $year = now()->format('y');
        $last = Student::withoutGlobalScopes()->where('school_id', $schoolId)->where('admission_no', 'like', "A$year-%")->orderByDesc('id')->value('admission_no');

        return "A$year-".str_pad((string) (($last ? (int) substr($last, 4) : 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    private function date(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        try {
            // Pakistani spreadsheets write day first: 14/08/2015.
            $value = preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value) ? str_replace('/', '-', $value) : $value;

            return Carbon::parse($value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }
}
