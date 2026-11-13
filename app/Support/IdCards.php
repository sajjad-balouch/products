<?php

namespace App\Support;

use App\Models\AcademicSession;
use App\Models\Guardian;
use App\Models\PickupPerson;
use App\Models\School;
use App\Models\Site;
use App\Models\SiteMedia;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Student and guardian ID cards. Every card is CR80, the size of a bank card: 85.6 × 54 mm.
 * The templates live in resources/views/cards; this class only gathers what goes on them.
 */
class IdCards
{
    public const WIDTH_MM = 85.6;

    public const HEIGHT_MM = 54;

    /** key => [name, orientation, one line about it] */
    public const TEMPLATES = [
        'classic' => ['Classic', 'portrait', 'Coloured top with a round photo. Clean and familiar.'],
        'wave' => ['Wave', 'portrait', 'Flowing waves top and bottom, rounded photo.'],
        'crest' => ['Royal', 'portrait', 'Deep colour with a gold ring photo. Looks premium.'],
        'horizon' => ['Horizon', 'landscape', 'Wide card with header band, photo and QR code.'],
        'slate' => ['Minimal', 'landscape', 'White with a side stripe. Simple and modern.'],
    ];

    public const TYPES = ['student' => 'Student cards', 'guardian' => 'Parent & pickup cards'];

    /**
     * Who gets a card: the current school's active students, or, for families with active students,
     * the main guardian followed by everyone else the family allows to pick up its children.
     * For one section or the whole school, optionally narrowed by a name, number or phone.
     * Each item has cardKey(), e.g. "student:12", "guardian:4" or "pickup:9".
     */
    public static function people(string $type, ?int $sectionId = null, string $search = ''): Collection
    {
        $search = trim($search);

        if ($type === 'guardian') {
            $like = fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%");

            return Guardian::query()
                ->whereHas('students', fn ($q) => $q->where('status', 'active')->when($sectionId, fn ($q) => $q->where('section_id', $sectionId)))
                ->with(['students' => fn ($q) => $q->where('status', 'active')->with('section.schoolClass')->orderBy('name'), 'pickupPeople'])
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $like($q)->orWhereHas('pickupPeople', $like)))
                ->orderBy('name')->get()
                // A plain collection, so a guardian and a pickup person with the same id are not merged.
                ->toBase()
                ->flatMap(fn (Guardian $family) => [$family, ...$family->pickupPeople->each->setRelation('guardian', $family)->all()]);
        }

        return Student::active()->with(['section.schoolClass', 'guardian'])
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('admission_no', 'like', "%{$search}%")))
            ->orderBy('section_id')->orderBy('name')->get();
    }

    /** @return array<string, mixed> */
    public static function defaults(School $school): array
    {
        // Valid to the end of the current session, or else the coming 30 June.
        $session = AcademicSession::withoutGlobalScopes()->where('school_id', $school->id)->where('is_current', true)->first();
        $june = today()->month > 6 ? today()->addYear()->setMonth(6)->endOfMonth() : today()->setMonth(6)->endOfMonth();

        return [
            'template' => 'classic',
            'primary' => '#0e6a5a',
            'accent' => '#d9822b',
            'valid_until' => ($session?->ends_on ?? $june)->toDateString(),
            'show_back' => true,
            'show_qr' => true,
            'back_note' => 'This card is the property of the school. If found, please return it to the address below.',
            'guardian_note' => 'The holder of this card may pick up the children named on it.',
            'logo_path' => null,
            'signature_path' => null,
        ];
    }

    /** @return array<string, mixed> */
    public static function settings(School $school): array
    {
        return array_merge(self::defaults($school), array_filter($school->card_settings ?? [], fn ($v) => $v !== null && $v !== ''));
    }

    /** @return array{logo: ?string, sign: ?string} picture links for the cards, changing when the picture changes */
    public static function assets(School $school): array
    {
        $logo = self::logoPath($school);
        $sign = self::settings($school)['signature_path'];

        return [
            'logo' => $logo ? route('cards.asset', ['logo', 'v' => substr(md5($logo), 0, 8)]) : null,
            'sign' => $sign ? route('cards.asset', ['signature', 'v' => substr(md5($sign), 0, 8)]) : null,
        ];
    }

    /** The card logo, or else the school website's logo. */
    public static function logoPath(School $school): ?string
    {
        if ($path = self::settings($school)['logo_path']) {
            return $path;
        }

        $media = Site::where('owner_key', 'school:'.$school->id)->first()?->setting('design', 'logo');

        return $media ? SiteMedia::where('id', $media)->value('path') : null;
    }

    public static function initials(string $name): string
    {
        return collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    }

    /** @return array<string, mixed> everything one student card shows */
    public static function student(Student $student, School $school): array
    {
        $phone = $student->guardian?->phone;

        return [
            'type' => 'student',
            'role' => 'Student',
            'name' => $student->name,
            'initials' => self::initials($student->name),
            'photo' => $student->photo_path ? route('cards.photo', ['student', $student->id, 'v' => substr(md5($student->photo_path), 0, 8)]) : null,
            'number' => $student->admission_no,
            'number_label' => 'Adm. no.',
            'headline' => $student->section?->label,
            'rows' => array_filter([
                'Father' => $student->father_name,
                'Roll no.' => $student->roll_no,
                'Date of birth' => $student->dob?->format('d M Y'),
                'Emergency' => $phone,
            ]),
            'address' => $student->guardian?->address,
            'qr' => implode("\n", array_filter([$school->name, $student->name, 'Adm. no. '.$student->admission_no, $student->section?->label ? 'Class '.$student->section->label : null, $phone ? 'Emergency '.$phone : null])),
        ];
    }

    /** @return array<string, mixed> the card for anyone from people() */
    public static function card(Student|Guardian|PickupPerson $person, School $school): array
    {
        return $person instanceof Student ? self::student($person, $school) : self::guardian($person, $school);
    }

    /**
     * A pickup card: the family's main guardian, or anyone else the family allows to collect its children.
     *
     * @return array<string, mixed>
     */
    public static function guardian(Guardian|PickupPerson $holder, School $school): array
    {
        $isMain = $holder instanceof Guardian;
        $family = $isMain ? $holder : $holder->guardian;
        $children = $family->students->where('status', 'active')->values();
        $count = $children->count().' '.Str::plural('child', $children->count());
        $type = $isMain ? 'guardian' : 'pickup';

        return [
            'type' => 'guardian',
            'role' => $isMain && in_array($holder->relation, [null, 'Father', 'Mother'], true) ? 'Parent card' : 'Pickup card',
            'name' => $holder->name,
            'initials' => self::initials($holder->name),
            'photo' => $holder->photo_path ? route('cards.photo', [$type, $holder->id, 'v' => substr(md5($holder->photo_path), 0, 8)]) : null,
            'number' => $isMain ? 'P-'.str_pad((string) $holder->id, 5, '0', STR_PAD_LEFT) : 'A-'.str_pad((string) $holder->id, 5, '0', STR_PAD_LEFT),
            'number_label' => 'Card no.',
            'headline' => $holder->relation ? $holder->relation.' · '.$count : $count,
            'rows' => array_filter([
                'Phone' => $holder->phone,
                'CNIC' => $holder->cnic,
                'Family of' => $isMain ? null : $family->name,
            ]),
            'children' => $children->take(4)->map(fn ($s) => ['name' => $s->name, 'class' => $s->section?->label])->all(),
            'more_children' => max(0, $children->count() - 4),
            'address' => $family->address,
            'qr' => implode("\n", array_filter([
                $school->name,
                ($holder->relation ?: 'Guardian').': '.$holder->name,
                $holder->phone,
                $isMain ? null : 'Allowed by: '.$family->name.' '.$family->phone,
                'Children: '.$children->pluck('name')->implode(', '),
            ])),
        ];
    }
}
