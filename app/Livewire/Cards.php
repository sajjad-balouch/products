<?php

namespace App\Livewire;

use App\Models\Guardian;
use App\Models\PickupPerson;
use App\Models\Section;
use App\Models\Student;
use App\Support\IdCards;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Student and parent ID cards: pick people, pick a design, add photos, then print or download. */
#[Title('ID cards')]
class Cards extends Component
{
    use WithFileUploads;

    #[Url]
    public string $type = 'student';

    #[Url]
    public string $section = '';

    public string $search = '';

    public const MAX_PICKUP = 6;

    /** Card keys ("student:12") unticked from the current list. */
    public array $excluded = [];

    public ?string $previewKey = null;

    // Someone who may pick up a family's children
    public ?string $personTarget = null;

    public string $personName = '';

    public string $personRelation = '';

    public string $personPhone = '';

    public string $personCnic = '';

    // Design, saved for the school as it changes
    public string $template = 'classic';

    public string $primary = '#0e6a5a';

    public string $accent = '#d9822b';

    public string $validUntil = '';

    public bool $showBack = true;

    public bool $showQr = true;

    public string $backNote = '';

    public string $guardianNote = '';

    // Uploads
    public $logo;

    public $signature;

    public $photo;

    public string $photoFor = '';

    public function mount(): void
    {
        $this->type = isset(IdCards::TYPES[$this->type]) ? $this->type : 'student';
        $s = IdCards::settings(Auth::user()->school);
        $this->template = $s['template'];
        $this->primary = $s['primary'];
        $this->accent = $s['accent'];
        $this->validUntil = (string) $s['valid_until'];
        $this->showBack = (bool) $s['show_back'];
        $this->showQr = (bool) $s['show_qr'];
        $this->backNote = $s['back_note'];
        $this->guardianNote = $s['guardian_note'];
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['type', 'section', 'search'], true)) {
            $this->excluded = [];
            $this->previewKey = null;
            $this->type = isset(IdCards::TYPES[$this->type]) ? $this->type : 'student';
        }

        if (in_array($property, ['template', 'primary', 'accent', 'validUntil', 'showBack', 'showQr', 'backNote', 'guardianNote'], true)) {
            $this->saveDesign();
        }
    }

    private function saveDesign(): void
    {
        $this->validate([
            'template' => [Rule::in(array_keys(IdCards::TEMPLATES))],
            'primary' => ['regex:/^#[0-9a-fA-F]{6}$/'],
            'accent' => ['regex:/^#[0-9a-fA-F]{6}$/'],
            'validUntil' => 'nullable|date',
            'backNote' => 'nullable|string|max:200',
            'guardianNote' => 'nullable|string|max:200',
        ], ['primary.regex' => 'Pick a colour.', 'accent.regex' => 'Pick a colour.']);

        $this->store([
            'template' => $this->template, 'primary' => strtolower($this->primary), 'accent' => strtolower($this->accent),
            'valid_until' => $this->validUntil ?: null, 'show_back' => $this->showBack, 'show_qr' => $this->showQr,
            'back_note' => $this->backNote, 'guardian_note' => $this->guardianNote,
        ]);
    }

    private function store(array $values): void
    {
        $school = Auth::user()->school;
        $school->update(['card_settings' => array_merge($school->card_settings ?? [], $values)]);
    }

    public function updatedLogo(): void
    {
        $this->saveImage('logo', 'logo_path');
    }

    public function updatedSignature(): void
    {
        $this->saveImage('signature', 'signature_path');
    }

    private function saveImage(string $property, string $key): void
    {
        $this->validate([$property => 'image|mimes:jpg,jpeg,png,webp|max:2048']);
        $school = Auth::user()->school;
        $old = $school->card_settings[$key] ?? null;
        $path = $this->{$property}->store('photos/'.$school->id.'/card', 'local');
        $this->store([$key => $path]);
        if ($old) {
            Storage::disk('local')->delete($old);
        }
        $this->reset($property);
        $this->dispatch('toast', message: ucfirst($property).' saved.');
    }

    public function removeImage(string $key): void
    {
        abort_unless(in_array($key, ['logo_path', 'signature_path'], true), 404);
        $school = Auth::user()->school;
        if ($old = $school->card_settings[$key] ?? null) {
            Storage::disk('local')->delete($old);
        }
        $this->store([$key => null]);
    }

    /** The photo box under each person sets photoFor ("student:12") before the file is sent. */
    public function updatedPhoto(): void
    {
        $this->validate(['photo' => 'image|mimes:jpg,jpeg,png,webp|max:3072'], ['photo.max' => 'The photo must be smaller than 3 MB.']);
        $person = $this->person($this->photoFor);
        $folder = match (true) {
            $person instanceof Student => 'students', $person instanceof Guardian => 'guardians', default => 'pickup'
        };
        $old = $person->photo_path;
        $person->update(['photo_path' => $this->photo->store('photos/'.$person->school_id.'/'.$folder, 'local')]);
        if ($old) {
            Storage::disk('local')->delete($old);
        }
        $this->previewKey = $person->cardKey();
        $this->reset('photo', 'photoFor');
    }

    public function removePhoto(string $who): void
    {
        $person = $this->person($who);
        if ($person->photo_path) {
            Storage::disk('local')->delete($person->photo_path);
            $person->update(['photo_path' => null]);
        }
    }

    /** Found within the signed-in school only, by the models' school scope. */
    private function person(string $who): Student|Guardian|PickupPerson
    {
        [$type, $id] = array_pad(explode(':', $who, 2), 2, null);

        return match ($type) {
            'student' => Student::findOrFail((int) $id),
            'guardian' => Guardian::findOrFail((int) $id),
            'pickup' => PickupPerson::findOrFail((int) $id),
            default => abort(404),
        };
    }

    /** Add someone to a family ("new:4"), or edit a pickup person ("pickup:9") or the main guardian ("guardian:4"). */
    public function openPerson(string $target): void
    {
        $this->resetValidation();
        [$kind, $id] = array_pad(explode(':', $target, 2), 2, null);
        $person = $kind === 'new' ? null : $this->person($target);
        if ($kind === 'new') {
            $family = Guardian::findOrFail((int) $id);
            abort_if($family->pickupPeople()->count() >= self::MAX_PICKUP, 422, 'A family can have up to '.self::MAX_PICKUP.' extra people.');
        }
        abort_if($person instanceof Student, 404);

        $this->personTarget = $kind === 'new' ? 'new:'.$family->id : $person->cardKey();
        $this->personName = (string) $person?->name;
        $this->personRelation = (string) ($person?->relation ?? ($kind === 'new' ? '' : 'Father'));
        $this->personPhone = (string) $person?->phone;
        $this->personCnic = (string) $person?->cnic;
    }

    public function savePerson(): void
    {
        [$kind, $id] = array_pad(explode(':', (string) $this->personTarget, 2), 2, null);
        $isMain = $kind === 'guardian';

        $this->validate([
            'personName' => $isMain ? 'nullable' : 'required|string|max:100',
            'personRelation' => ['required', Rule::in(PickupPerson::RELATIONS)],
            'personPhone' => $isMain ? 'nullable' : 'nullable|string|max:30',
            'personCnic' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
        ], ['personCnic.regex' => 'Use numbers and dashes, like 36302-1234567-1.'], [
            'personName' => 'name', 'personRelation' => 'relation', 'personPhone' => 'phone', 'personCnic' => 'CNIC',
        ]);

        $values = ['relation' => $this->personRelation, 'cnic' => $this->personCnic ?: null];
        if ($isMain) {
            // Name and phone belong to the family record, which links brothers and sisters, so only these change here.
            Guardian::findOrFail((int) $id)->update($values);
        } else {
            $values += ['name' => trim($this->personName), 'phone' => $this->personPhone ?: null];
            $person = $kind === 'new'
                ? Guardian::findOrFail((int) $id)->pickupPeople()->create($values + ['school_id' => Auth::user()->school_id])
                : tap(PickupPerson::findOrFail((int) $id))->update($values);
            $this->previewKey = $person->cardKey();
        }

        $this->personTarget = null;
        $this->dispatch('toast', message: 'Saved.');
    }

    public function deletePerson(int $id): void
    {
        $person = PickupPerson::findOrFail($id);
        if ($person->photo_path) {
            Storage::disk('local')->delete($person->photo_path);
        }
        $person->delete();
        $this->excluded = array_values(array_diff($this->excluded, ['pickup:'.$id]));
    }

    public function toggle(string $key): void
    {
        $this->excluded = in_array($key, $this->excluded, true) ? array_values(array_diff($this->excluded, [$key])) : [...$this->excluded, $key];
    }

    public function selectAll(): void
    {
        $this->excluded = [];
    }

    public function selectNone(): void
    {
        $this->excluded = IdCards::people($this->type, $this->sectionId(), $this->search)->map->cardKey()->values()->all();
    }

    private function sectionId(): ?int
    {
        return $this->section !== '' ? (int) $this->section : null;
    }

    public function render()
    {
        $school = Auth::user()->school;
        $people = IdCards::people($this->type, $this->sectionId(), $this->search);
        $chosen = $people->reject(fn ($p) => in_array($p->cardKey(), $this->excluded, true));
        $sample = $people->first(fn ($p) => $p->cardKey() === $this->previewKey) ?? $chosen->first() ?? $people->first();
        $card = $sample ? IdCards::card($sample, $school) : null;

        return view('livewire.cards', [
            'school' => $school,
            'people' => $people,
            'chosenCount' => $chosen->count(),
            'missingPhotos' => $chosen->whereNull('photo_path')->count(),
            'card' => $card,
            'settings' => IdCards::settings($school),
            // The saved design, if what was just typed is not a template.
            'templateInfo' => IdCards::TEMPLATES[$this->template] ?? IdCards::TEMPLATES[IdCards::settings($school)['template']],
            'assets' => IdCards::assets($school),
            'sections' => Section::with('schoolClass')->get()->sortBy('label'),
        ]);
    }
}
