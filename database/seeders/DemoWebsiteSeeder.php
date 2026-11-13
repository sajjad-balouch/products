<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\School;
use App\Models\Site;
use App\Models\SiteMedia;
use App\Models\SitePage;
use App\Models\SiteSection;
use App\Services\SiteBuilder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Fills the owner's website and the demo school's website with photos and sample text,
 * so the product can be shown off. Photos are free Unsplash images, see demo-images/CREDITS.md.
 * A site is only rebuilt from the template while nobody has edited its sections, and a
 * picture someone already uploaded is never replaced. Sample text is, so it is only for demos.
 */
class DemoWebsiteSeeder extends Seeder
{
    public function run(SiteBuilder $builder): void
    {
        $this->platform($builder);

        $school = School::where('slug', 'demo-public-school-and-college')->first() ?? School::orderBy('id')->first();
        if ($school) {
            $this->school($builder, $school);
        }
    }

    /** Copies a demo photo into the site's own pictures and returns its id. The same photo is reused on a rerun. */
    private function photo(Site $site, string $name): int
    {
        $original = 'demo-'.$name.'.jpg';
        $path = 'site-media/'.$site->id.'/'.$original;
        Storage::disk('local')->put($path, file_get_contents(database_path('seeders/demo-images/'.$name.'.jpg')));

        return SiteMedia::firstOrCreate(['site_id' => $site->id, 'original_name' => $original], ['path' => $path])->id;
    }

    /** Merges values into the section that has this menu link name, keeping any picture already there. */
    private function patch(SitePage $page, string $anchor, array $data): void
    {
        $section = $page->sections()->get()->first(fn ($s) => ($s->data['_anchor'] ?? null) === $anchor);
        if (! $section) {
            return;
        }

        $old = $section->data;
        if (! empty($old['image'])) {
            $data['image'] = $old['image'];
        }
        foreach ($data['items'] ?? [] as $i => $item) {
            if (! empty($old['items'][$i]['image'])) {
                $data['items'][$i]['image'] = $old['items'][$i]['image'];
            }
        }

        $section->update(['data' => array_merge($old, $data)]);
    }

    /** Puts pictures on a section's cards by position, keeping their text and any picture already there. */
    private function patchItems(SitePage $page, string $anchor, array $images): void
    {
        $section = $page->sections()->get()->first(fn ($s) => ($s->data['_anchor'] ?? null) === $anchor);
        if (! $section) {
            return;
        }

        $data = $section->data;
        foreach ($images as $i => $image) {
            if (isset($data['items'][$i]) && empty($data['items'][$i]['image'])) {
                $data['items'][$i]['image'] = $image;
            }
        }
        $section->update(['data' => $data]);
    }

    /** True while no person has changed its pages or sections. This seeder's own changes do not count. */
    private function untouched(Site $site): bool
    {
        $pages = $site->pages()->pluck('id');
        $sections = SiteSection::whereIn('site_page_id', $pages)->pluck('id');

        return ! AuditLog::withoutGlobalScopes()->where('action', 'updated')->whereNotNull('user_id')
            ->where(fn ($q) => $q->where(fn ($w) => $w->where('subject_type', 'SiteSection')->whereIn('subject_id', $sections))
                ->orWhere(fn ($w) => $w->where('subject_type', 'SitePage')->whereIn('subject_id', $pages)))
            ->exists();
    }

    private function platform(SiteBuilder $builder): void
    {
        $site = $builder->platform();
        if ($this->untouched($site)) {
            $builder->applyPlatformTemplate($site);
        }
        $home = $site->home();
        $p = fn ($name) => $this->photo($site, $name);

        $this->patch($home, 'top', ['image' => $p('app-dashboard'), 'image2' => $p('app-portal')]);
        $this->patchItems($home, 'features', [$p('app-fees'), $p('app-attendance'), $p('app-portal'), $p('app-students'), $p('girls-graduation'), $p('campus-building')]);
        $this->patch($home, 'timetable', ['image' => $p('app-timetable')]);
        $this->patch($home, 'parents', ['image' => $p('girl-book')]);

        // The product site gets its own colours, unless the owner already chose some.
        $settings = $site->fresh()->settings ?? [];
        if (in_array($settings['design']['primary'] ?? null, [null, '', '#0e6a5a'], true)) {
            $settings['design']['primary'] = '#4f46e5';
            $settings['design']['accent'] = '#f97316';
            $site->update(['settings' => $settings]);
        }
    }

    private function school(SiteBuilder $builder, School $school): void
    {
        $site = $builder->forSchool($school);
        if ($this->untouched($site)) {
            $builder->applySchoolTemplate($site);
        }
        $home = $site->home();
        $apply = route('apply', $school->slug, false);
        $p = fn ($name) => $this->photo($site, $name);

        $this->patch($home, 'home', ['items' => [
            ['image' => $p('slide-students'), 'eyebrow' => 'Admissions open 2026-27', 'heading' => 'Where every child learns to shine', 'text' => 'Classes from Playgroup to Intermediate, with caring teachers and a campus built for learning.', 'button_label' => 'Apply now', 'button_url' => $apply],
            ['image' => $p('campus-building'), 'eyebrow' => 'Our campus', 'heading' => 'A safe, green campus in the heart of the city', 'text' => 'Bright classrooms, science labs, a library and open playgrounds.', 'button_label' => 'See our campuses', 'button_url' => '#campuses'],
            ['image' => $p('slide-graduation'), 'eyebrow' => 'Results', 'heading' => 'Proud of our graduates, year after year', 'text' => 'Strong board results, and confident young people ready for what comes next.', 'button_label' => 'Our academics', 'button_url' => '#academics'],
        ]]);

        $this->patch($home, 'about', [
            'heading' => 'A school that feels like family',
            'body' => "{$school->name} has grown from a few classrooms into a trusted school and college for families across the city. Every child is known by name, and every teacher cares about how they grow, not only what they score.\n\nWe combine strong academics with good manners, sports and the confidence to speak up.",
            'image' => $p('teacher-children'),
        ]);

        $this->patch($home, 'message', [
            'body' => "It is a pleasure to welcome you to {$school->name}. Our aim has always been simple: a safe place where children love to learn, and where parents know their child is in good hands.\n\nWe measure our success not only by results, but by the kind, responsible and capable young people who leave our gates.",
            'image' => $p('principal'),
            'name' => 'Prof. Tariq Hussain',
            'role' => 'Principal',
            'motto' => 'Learning with discipline and care',
        ]);

        $this->patch($home, 'mission', ['items' => [
            ['image' => null, 'title' => 'Our mission', 'text' => 'To give every student a strong academic base, good character and the confidence to think for themselves, in a disciplined and caring school.', 'link' => ''],
            ['image' => null, 'title' => 'Our vision', 'text' => 'Young people who are honest, skilled and ready to serve their families, their community and Pakistan.', 'link' => ''],
        ]]);

        $this->patch($home, 'values', ['items' => [
            ['image' => null, 'title' => 'Respect', 'text' => 'Kindness and good manners towards everyone, inside and outside class.', 'link' => ''],
            ['image' => null, 'title' => 'Discipline', 'text' => 'On time, in uniform and ready to learn, every day.', 'link' => ''],
            ['image' => null, 'title' => 'Excellence', 'text' => 'Always doing our best, in studies, sports and life.', 'link' => ''],
            ['image' => null, 'title' => 'Integrity', 'text' => 'Honest work, fair play, and owning our mistakes.', 'link' => ''],
            ['image' => null, 'title' => 'Teamwork', 'text' => 'Helping each other succeed in class and on the field.', 'link' => ''],
            ['image' => null, 'title' => 'Responsibility', 'text' => 'Caring for our school, our city and our country.', 'link' => ''],
        ]]);

        $this->patch($home, 'academics', [
            'body' => "- Playgroup to Matric, plus Intermediate in science and arts\n- Oxford books in the early years and the Punjab board in Matric\n- Monthly tests, with a report card parents can see online\n- Science and computer labs for hands-on learning",
            'image' => $p('lab'),
        ]);

        $this->patch($home, 'admissions', [
            'intro' => 'Admission is based on a short entry test from the last class the child studied.',
            'items' => [
                ['image' => null, 'title' => 'How it works', 'text' => "- Apply online or visit the office\n- Short entry test, any working day\n- Result and fee details the same week", 'link' => ''],
                ['image' => null, 'title' => 'Who can apply', 'text' => "- Playgroup from 3 years of age\n- Bring the B-form and last result card\n- Two passport-size photos", 'link' => ''],
                ['image' => null, 'title' => 'When to apply', 'text' => "- Spring intake in February\n- Fall intake in August\n- Seats in some classes all year", 'link' => ''],
            ],
        ]);

        $this->patch($home, 'gallery', ['items' => [
            ['image' => $p('campus-building'), 'caption' => 'Main campus', 'category' => 'Campus'],
            ['image' => $p('boy-desk'), 'caption' => 'A happy start to the day', 'category' => 'Classrooms'],
            ['image' => $p('football'), 'caption' => 'Inter-house football', 'category' => 'Sports'],
            ['image' => $p('girls-graduation'), 'caption' => 'Graduation day', 'category' => 'Events'],
            ['image' => $p('library'), 'caption' => 'Our library', 'category' => 'Campus'],
            ['image' => $p('boy-writing'), 'caption' => 'Learning together', 'category' => 'Classrooms'],
            ['image' => $p('girl-book'), 'caption' => 'College section', 'category' => 'Students'],
            ['image' => $p('graduates'), 'caption' => 'Convocation', 'category' => 'Events'],
            ['image' => $p('kids-carpet'), 'caption' => 'Story time in the junior section', 'category' => 'Classrooms'],
            ['image' => $p('empty-classroom'), 'caption' => 'Ready for a new term', 'category' => 'Campus'],
            ['image' => $p('group-study'), 'caption' => 'Group study', 'category' => 'Students'],
            ['image' => $p('campus-second'), 'caption' => 'Girls campus', 'category' => 'Campus'],
        ]]);

        $this->patch($home, 'notices', ['items' => [
            ['date' => '2026-11-01', 'title' => 'Admissions open for the spring intake', 'body' => 'Forms are available online and at the office. Entry tests every working day.'],
            ['date' => '2026-10-28', 'title' => 'Parent-teacher meeting', 'body' => 'Saturday 8 November, 9 am to 1 pm. Report cards will be shared.'],
            ['date' => '2026-10-20', 'title' => 'Winter uniform', 'body' => 'Winter uniform is required from 1 November.'],
        ]]);

        $settings = $site->fresh()->settings;
        $oldCampuses = $settings['contact']['campuses'] ?? [];
        $settings['contact']['campuses'] = [
            ['name' => 'Main campus', 'badge' => 'Boys and junior section', 'address' => $school->address ?: 'Main Road', 'phone' => (string) $school->phone, 'about' => 'Our main campus, home to the junior section, boys classes and the administration office.', 'image' => $p('campus-building')],
            ['name' => 'Girls campus', 'badge' => 'Girls and college', 'address' => '45 Garden Town', 'phone' => (string) $school->phone, 'about' => 'A separate campus for girls from Class 6 and the college section, with its own labs and library.', 'image' => $p('campus-second')],
        ];
        foreach ($settings['contact']['campuses'] as $i => $campus) {
            $settings['contact']['campuses'][$i]['image'] = ($oldCampuses[$i]['image'] ?? null) ?: $campus['image'];
        }
        $settings['footer']['about'] = 'A trusted school and college, building strong minds and good character.';
        $settings['popup'] = ['show' => (bool) ($settings['popup']['show'] ?? false), 'heading' => 'Admissions open 2026-27', 'text' => 'Seats are filling fast. Apply online in a few minutes, or visit the office for an entry test.', 'image' => $p('slide-students'), 'button_label' => 'Apply now', 'button_url' => $apply];
        $site->update(['settings' => $settings, 'is_published' => true]);
    }
}
