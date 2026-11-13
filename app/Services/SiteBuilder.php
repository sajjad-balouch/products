<?php

namespace App\Services;

use App\Models\School;
use App\Models\Site;
use App\Models\SitePage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Finds a website, creating it with starter pages the first time it is needed. */
class SiteBuilder
{
    /** The software owner's site is live from the start, because it replaces the sign-in page at /. */
    public function platform(): Site
    {
        return Site::where('owner_key', 'platform')->first() ?? DB::transaction(function () {
            $site = Site::create(['owner_key' => 'platform', 'is_published' => true, 'settings' => [
                'design' => ['site_name' => config('app.name'), 'primary' => '#4f46e5', 'accent' => '#f97316', 'seo_description' => 'School and college management: fees, attendance, results, timetable and a parent portal in one system.'],
                'header' => ['cta_show' => true, 'cta_label' => 'Start free trial', 'cta_url' => '/signup'],
                'footer' => ['about' => 'School and college management software.'],
            ]]);

            $this->platformPages($site);

            return $site;
        });
    }

    /** A school's site stays a draft until its admin publishes it, because it starts with sample text. */
    public function forSchool(School $school): Site
    {
        return Site::where('owner_key', 'school:'.$school->id)->first() ?? DB::transaction(function () use ($school) {
            $site = Site::create(['owner_key' => 'school:'.$school->id, 'school_id' => $school->id, 'settings' => [
                'design' => ['site_name' => $school->name],
                'header' => ['cta_show' => true, 'cta_label' => 'Apply now', 'cta_url' => route('apply', $school->slug, false)],
                'footer' => ['about' => $school->name],
                'contact' => [
                    'phone' => (string) $school->phone,
                    'email' => (string) $school->email,
                    'address' => (string) $school->address,
                    'campuses' => $this->starterCampuses($school),
                ],
                'popup' => $this->starterPopup($school),
            ]]);

            $this->schoolPages($site, $school);

            return $site;
        });
    }

    public function forUser(User $user): Site
    {
        return $user->school_id ? $this->forSchool($user->school) : $this->platform();
    }

    public function addPage(Site $site, string $title, string $slug, bool $inMenu = true, array $sections = [], bool $home = false): SitePage
    {
        $sort = (int) $site->pages()->max('sort') + 1;
        $page = $site->pages()->create(['title' => $title, 'slug' => $slug, 'is_home' => $home, 'sort' => $sort]);

        foreach ($sections as $i => [$type, $data]) {
            $page->sections()->create(['type' => $type, 'data' => $data, 'sort' => $i + 1]);
        }

        if ($inMenu) {
            $site->menuItems()->create(['location' => 'header', 'label' => $title, 'site_page_id' => $page->id, 'sort' => $sort]);
            $site->menuItems()->create(['location' => 'footer', 'label' => $title, 'site_page_id' => $page->id, 'sort' => $sort]);
        }

        return $page;
    }

    /** The product website: a launch-style home page, all features, and contact. Screenshots are added by DemoWebsiteSeeder. */
    private function platformPages(Site $site): void
    {
        $name = config('app.name');
        $card = fn ($title, $text) => ['image' => null, 'title' => $title, 'text' => $text, 'link' => ''];
        $features = [
            ['Fees', 'Monthly vouchers, late fees, sibling discounts, payment proofs from parents and daily cash closing.'],
            ['Attendance', 'Mark a class in seconds and queue WhatsApp messages to parents of absent students.'],
            ['Exams and results', 'Enter marks by subject, publish results and print report cards.'],
            ['Timetable', 'Drag and drop periods, with teacher clash checks and suggestions for what fits.'],
            ['Admissions', 'A public apply form and a pipeline from enquiry to admission.'],
            ['Early warning', 'Flags students at risk from attendance and results, with readable reasons.'],
            ['Parent portal', 'Parents see attendance, results and fees, in English or Urdu.'],
            ['Certificates and ID cards', 'Printed documents with a QR code anyone can verify.'],
            ['Staff and payroll', 'Staff records, attendance, salary runs and salary slips.'],
            ['Library and transport', 'Book loans, transport routes and which student uses them.'],
            ['Finance', 'Expenses and an income and expense report.'],
            ['Public website', 'Every school gets its own website, managed from its admin panel.'],
        ];
        $cards = array_map(fn ($f) => $card($f[0], $f[1]), $features);
        $trial = ['_eyebrow' => '30-day free trial', 'heading' => 'Give your school office its time back', 'text' => 'Sign up and your school is ready in a minute. No card, no installation.', 'button_label' => 'Start free trial', 'button_url' => '/signup', '_bg' => 'brand', '_spacing' => 'large'];

        $this->addPage($site, 'Home', 'home', false, [
            ['hero', ['_anchor' => 'top', '_eyebrow' => 'Made for schools and colleges in Pakistan', 'layout' => 'product', 'heading' => 'Run your whole school from one simple system', 'highlight' => 'one simple system', 'text' => "Fees, attendance, results, timetable and parents in one place. {$name} does the paperwork, so your staff can spend their time on students.", 'button_label' => 'Start free trial', 'button_url' => '/signup', 'button2_label' => 'See how it works', 'button2_url' => '/#how', 'chips' => "30-day free trial\nEnglish and Urdu\nWorks on any phone\nEach school's data kept apart", '_bg' => 'dark', '_spacing' => 'large']],
            ['features', ['_anchor' => 'features', '_eyebrow' => 'Everything in one place', 'heading' => 'One system instead of five registers', 'intro' => 'Everything a school office does every day, in screens your staff learn in an afternoon.', 'style' => 'bento', 'columns' => '3', 'items' => [
                $card('Fee vouchers in one click', 'Vouchers for every family, late fees and sibling discounts worked out for you, and a list of who still owes.'),
                $card('Attendance in seconds', 'Everyone starts present. Tap the absent ones, and parents get a WhatsApp message.'),
                $card('Parents on their phone', 'Attendance, fees, results and homework, in English or Urdu.'),
                $card('Every student in one place', 'Families, siblings, classes and history. Bring them in from Excel.'),
                $card('Results and report cards', 'Marks by subject, positions and printed report cards.'),
                $card('A website for every school', 'Picture slider, campuses, gallery and an online admission form.'),
            ]]],
            ['showcase', ['_anchor' => 'timetable', '_eyebrow' => 'Timetable', 'heading' => 'Drag, drop, done', 'intro' => 'Build the timetable by dragging subjects onto the week. A teacher can never be put in two classes at once, and the system suggests what fits.', 'address' => 'timetable', '_bg' => 'tint']],
            ['steps', ['_anchor' => 'how', '_eyebrow' => 'How it works', 'heading' => 'Up and running in a day', 'items' => [
                ['title' => 'Sign up', 'text' => 'Your school is ready in a minute, with a 30-day free trial.'],
                ['title' => 'Bring in your students', 'text' => 'Add classes, then import students and parents from Excel.'],
                ['title' => 'Send the first vouchers', 'text' => 'Generate fee vouchers and invite parents to their portal.'],
            ]]],
            ['image_text', ['_anchor' => 'parents', '_eyebrow' => 'For parents', 'heading' => 'Parents always know how their child is doing', 'body' => 'Attendance, results, fees and the daily diary in one portal, in English or Urdu. Absent alerts reach parents the same morning.', 'image_side' => 'left', 'highlights' => "Parent portal\nUrdu and English\nAbsent alerts\nReport cards", '_bg' => 'tint']],
            ['plans', ['_anchor' => 'pricing', '_eyebrow' => 'Pricing', 'heading' => 'Simple monthly plans', 'intro' => 'Start with a free 30-day trial. Choose a plan when you are ready.', 'note' => 'Prices in Pakistani rupees. Ask us about a yearly price or more than one campus.', 'button_label' => 'Start free trial', 'button_url' => '/signup']],
            ['faq', ['_anchor' => 'faq', '_eyebrow' => 'Questions', 'heading' => 'Questions schools ask', 'items' => [
                ['question' => 'Do we need to install anything?', 'answer' => 'No. It runs in the browser on a computer or a phone.'],
                ['question' => 'Does it work in Urdu?', 'answer' => 'Yes. Parents can use their portal in Urdu or English.'],
                ['question' => 'Can parents pay fees online?', 'answer' => 'Parents pay by bank, JazzCash or Easypaisa and upload a picture of the payment. The office checks it and marks the voucher paid.'],
                ['question' => 'Can other schools see our data?', 'answer' => 'No. Every school only ever sees its own students, staff and fees.'],
                ['question' => 'What happens after the free trial?', 'answer' => 'Choose a plan to keep going. Your data stays exactly as you left it.'],
            ]]],
            ['cta', $trial],
        ], true);

        $all = $this->addPage($site, 'All features', 'features', false, [
            ['hero', ['layout' => 'center', '_eyebrow' => 'Features', 'heading' => 'Everything your school needs', 'text' => 'Each role sees only what it needs: the principal, the accountant, teachers and parents.', '_bg' => 'tint']],
            ['features', ['columns' => '3', 'items' => $cards]],
            ['cta', $trial],
        ]);

        $contact = $this->addPage($site, 'Contact', 'contact', false, [
            ['contact', ['heading' => 'Talk to us', 'text' => 'Send a message and we will get back to you, usually the same day.', 'show_form' => true, 'show_details' => true, 'show_map' => false]],
        ]);

        $link = fn ($label, $target, $sort, $location = 'header') => $site->menuItems()->create(['location' => $location, 'label' => $label, 'sort' => $sort] + (is_int($target) ? ['site_page_id' => $target] : ['url' => $target]));
        foreach ([['Features', '/#features'], ['How it works', '/#how'], ['Pricing', '/#pricing'], ['Questions', '/#faq'], ['Contact', $contact->id]] as $i => [$label, $target]) {
            $link($label, $target, $i + 1);
        }
        foreach ([['All features', $all->id], ['Pricing', '/#pricing'], ['Contact', $contact->id], ['Sign in', '/login']] as $i => [$label, $target]) {
            $link($label, $target, $i + 1, 'footer');
        }

        $this->addBusinessProductPage($site);
    }

    /**
     * The page for the second product: installment sales and accounts for any business.
     * Safe to run on a site the owner has edited: it only adds the page and its menu links if missing.
     */
    public function addBusinessProductPage(Site $site): ?SitePage
    {
        if ($site->school_id || $site->pages()->where('slug', 'installments')->exists()) {
            return null;
        }

        $card = fn ($title, $text) => ['image' => null, 'title' => $title, 'text' => $text, 'link' => ''];
        $trial = ['_eyebrow' => 'Free trial', 'heading' => 'Know every rupee your customers owe', 'text' => 'Sign up and your business is ready in a minute. No card, no installation.', 'button_label' => 'Start free trial', 'button_url' => '/signup?product=business', '_bg' => 'brand', '_spacing' => 'large'];

        $page = $this->addPage($site, 'Installments & accounts', 'installments', false, [
            ['hero', ['_anchor' => 'top', '_eyebrow' => 'For any business that sells on installments', 'layout' => 'product', 'heading' => 'Installment sales and accounts, fully under control', 'highlight' => 'fully under control', 'text' => 'Sell motorcycles, phones, appliances, furniture or anything else on qist. Every installment, late customer, receipt and rupee in your books, in one simple system.', 'button_label' => 'Start free trial', 'button_url' => '/signup?product=business', 'button2_label' => 'See what it does', 'button2_url' => '/p/installments#features', 'chips' => "Any item, any plan\nDouble-entry accounts\nWhatsApp reminders\nWorks on any phone", '_bg' => 'dark', '_spacing' => 'large']],
            ['features', ['_anchor' => 'features', '_eyebrow' => 'Everything in one place', 'heading' => 'From the sale to the last installment', 'intro' => 'The way installment businesses already work, without the registers and the guesswork.', 'style' => 'bento', 'columns' => '3', 'items' => [
                $card('A sale in one minute', 'Cash price, markup, down payment and number of installments. The schedule and the monthly amount appear as you type.'),
                $card('Know who is late', 'Due today, late 1–30, 31–60, 61–90 and over 90 days, with the amount and the days. Remind them or their guarantor on WhatsApp in one tap.'),
                $card('Receipts and agreements', 'Printed receipts for every payment, a customer statement, and an installment agreement with guarantors and terms, ready to sign.'),
                $card('Real accounts, done for you', 'Every sale and payment posts double-entry to your books. Markup counts as income only as it is paid, the way accountants do it.'),
                $card('Profit, balance sheet and more', 'Profit and loss, balance sheet, trial balance, ledgers, day book, collections and an aging of dues, at any date.'),
                $card('Guarantors and blacklist', 'Up to two guarantors on every sale, and a warning before selling again to a customer who did not pay.'),
            ]]],
            ['steps', ['_anchor' => 'how', '_eyebrow' => 'How it works', 'heading' => 'Ready the same day', 'items' => [
                ['title' => 'Sign up', 'text' => 'Choose "Shop or business". Your chart of accounts is set up for you.'],
                ['title' => 'Set your terms', 'text' => 'Your usual markup, late fee, grace days and agreement terms.'],
                ['title' => 'Sell and collect', 'text' => 'Make sales, take installments at the counter, and follow up the late ones.'],
            ], '_bg' => 'tint']],
            ['features', ['_eyebrow' => 'All features', 'heading' => 'Everything an installment business needs', 'columns' => '3', 'items' => [
                $card('Customers', 'Name, CNIC, phones, address, photo and every plan they have had.'),
                $card('Flexible plans', 'Monthly, every two weeks or weekly. Markup as a percent or a fixed amount. Installments rounded to 10, 50 or 100.'),
                $card('Counter collection', 'Search by name, phone, CNIC or plan number. The amount due and the late fee are filled in.'),
                $card('Late fees and discounts', 'A late fee per late installment after your grace days, and discounts for early payment.'),
                $card('Cancel safely', 'A wrong receipt is cancelled with a reason. Its entry is reversed, never deleted.'),
                $card('Bad debts', 'Write off what cannot be recovered. The unearned markup is cancelled automatically.'),
                $card('Expenses and vouchers', 'Pay rent, salaries and bills; record capital and loans; make journal entries.'),
                $card('Staff logins', 'An owner login and accountant logins, with every change in the audit log.'),
                $card('Your own website', 'A website for your shop, managed from your panel.'),
            ]]],
            ['plans', ['_anchor' => 'pricing', 'product' => 'business', '_eyebrow' => 'Pricing', 'heading' => 'One simple monthly price', 'intro' => 'Start with a free trial. Choose a plan when you are ready.', 'note' => 'Prices in Pakistani rupees. Ask us about more than one branch.', 'button_label' => 'Start free trial', 'button_url' => '/signup?product=business', '_bg' => 'tint']],
            ['faq', ['_anchor' => 'faq', '_eyebrow' => 'Questions', 'heading' => 'Questions businesses ask', 'items' => [
                ['question' => 'What can I sell on installments?', 'answer' => 'Anything: motorcycles, mobiles, LED TVs, fridges, furniture, solar systems, even plots. You type the item and its details.'],
                ['question' => 'How is the markup worked out?', 'answer' => 'As a percent of what is left after the down payment, or as a fixed amount you enter. The total, the installment and the schedule are shown before you save.'],
                ['question' => 'Do I need to know accounting?', 'answer' => 'No. Sales and payments post to the right accounts by themselves. Your accountant still gets proper ledgers, a trial balance, profit and loss and a balance sheet.'],
                ['question' => 'Can I remind customers on WhatsApp?', 'answer' => 'Yes. One tap opens WhatsApp with the reminder written for you, to the customer or a guarantor. You can also queue reminders for everyone who is late.'],
                ['question' => 'Can other businesses see my data?', 'answer' => 'No. Every business only ever sees its own customers and accounts.'],
            ]]],
            ['cta', $trial],
        ]);

        $sort = (int) $site->menuItems()->where('location', 'header')->max('sort') + 1;
        $site->menuItems()->create(['location' => 'header', 'label' => 'Installments', 'site_page_id' => $page->id, 'sort' => $sort]);
        $site->menuItems()->create(['location' => 'footer', 'label' => 'Installment software', 'site_page_id' => $page->id, 'sort' => (int) $site->menuItems()->where('location', 'footer')->max('sort') + 1]);

        return $page;
    }

    /** Replaces the owner's pages and menus with the current product template. Other settings stay. */
    public function applyPlatformTemplate(Site $site): void
    {
        DB::transaction(function () use ($site) {
            $site->menuItems()->delete();
            $site->pages()->get()->each->delete();
            $site->permissions()->where('permission', 'like', 'page:%')->delete();
            $this->platformPages($site);
        });
    }

    /** Replaces every page and menu link with the complete school template. Other settings stay. */
    public function applySchoolTemplate(Site $site): void
    {
        DB::transaction(function () use ($site) {
            $site->menuItems()->delete();
            $site->pages()->get()->each->delete();
            $site->permissions()->where('permission', 'like', 'page:%')->delete();
            $this->schoolPages($site, $site->school);

            // Sites made before campuses and the pop-up existed get their starter values, without touching anything entered.
            $settings = $site->settings ?? [];
            $settings['contact']['campuses'] = ($settings['contact']['campuses'] ?? []) ?: $this->starterCampuses($site->school);
            $settings['popup'] ??= $this->starterPopup($site->school);
            $site->update(['settings' => $settings]);
        });
    }

    private function starterCampuses(School $school): array
    {
        return [['name' => 'Main campus', 'badge' => 'Main campus', 'address' => (string) $school->address, 'phone' => (string) $school->phone, 'about' => 'Replace this with a few lines about this campus.', 'image' => null]];
    }

    private function starterPopup(School $school): array
    {
        return ['heading' => 'Admissions are open', 'text' => 'Replace this with your admission notice.', 'button_label' => 'Apply now', 'button_url' => route('apply', $school->slug, false)];
    }

    /** One long home page in the usual order for a school, with the menu scrolling to each part. */
    private function schoolPages(Site $site, School $school): void
    {
        $apply = route('apply', $school->slug, false);
        $base = '/site/'.$school->slug;
        $card = fn ($title, $text) => ['image' => null, 'title' => $title, 'text' => $text, 'link' => ''];

        $home = $this->addPage($site, 'Home', 'home', false, [
            ['slider', ['interval' => '6', '_anchor' => 'home', 'items' => [
                ['image' => null, 'eyebrow' => 'Admissions open', 'heading' => 'Welcome to '.$school->name, 'text' => 'Replace this with a short welcome message, and add your own picture.', 'button_label' => 'Apply now', 'button_url' => $apply],
                ['image' => null, 'eyebrow' => 'Our school', 'heading' => 'Replace this with something you are proud of', 'text' => 'Add as many slides as you like.', 'button_label' => 'About us', 'button_url' => $base.'#about'],
            ]]],
            ['image_text', ['_anchor' => 'about', '_eyebrow' => 'About', 'heading' => 'About our school', 'body' => "Replace this with when your school was founded, what it stands for and what makes it different.\n\nAdd a picture of your campus beside this text.", 'image_side' => 'right', '_bg' => 'tint']],
            ['message', ['_anchor' => 'message', '_eyebrow' => 'Leadership', 'heading' => "Principal's message", 'body' => 'Replace this with a welcome message from your chairman or principal, and add their photo.', 'image_side' => 'left', 'name' => 'Full name', 'role' => 'Principal', 'motto' => '']],
            ['features', ['_anchor' => 'mission', '_eyebrow' => 'What guides us', 'heading' => 'Our mission and vision', 'columns' => '2', '_bg' => 'tint', 'items' => [
                $card('Our mission', 'Replace this with what your school sets out to do every day.'),
                $card('Our vision', 'Replace this with what you want your students to become.'),
            ]]],
            ['features', ['_anchor' => 'values', '_eyebrow' => 'What we believe', 'heading' => 'Our core values', 'columns' => '3', 'items' => [
                $card('Respect', 'Replace with what this value means at your school.'),
                $card('Discipline', 'Replace with what this value means at your school.'),
                $card('Excellence', 'Replace with what this value means at your school.'),
                $card('Integrity', 'Replace with what this value means at your school.'),
                $card('Teamwork', 'Replace with what this value means at your school.'),
                $card('Responsibility', 'Replace with what this value means at your school.'),
            ]]],
            ['image_text', ['_anchor' => 'academics', '_eyebrow' => 'Academics', 'heading' => 'Our academic system', 'body' => "- Classes you offer, from first to last\n- Your curriculum and examination board\n- How students are assessed and supported", 'image_side' => 'left', 'highlights' => "Expert faculty\nStrong curriculum\nRegular assessment\nCharacter building", '_bg' => 'tint']],
            ['features', ['_anchor' => 'admissions', '_eyebrow' => 'Join us', 'heading' => 'Admissions', 'intro' => 'Replace this with a short line about how your school admits students.', 'columns' => '3', 'items' => [
                $card('How it works', "- Replace with your first step\n- Replace with your second step\n- Replace with your third step"),
                $card('Who can apply', "- Minimum age for the first class\n- Documents parents should bring"),
                $card('When to apply', "- Your admission months\n- Any last dates"),
            ]]],
            ['cta', ['heading' => 'Ready to apply?', 'text' => 'Parents can apply online in a few minutes.', 'button_label' => 'Apply now', 'button_url' => $apply, '_bg' => 'brand', '_spacing' => 'compact']],
            ['campuses', ['_anchor' => 'campuses', '_eyebrow' => 'Our network', 'heading' => 'Our campuses', '_bg' => 'tint']],
            ['gallery', ['_anchor' => 'gallery', 'heading' => 'Picture gallery', 'intro' => 'School moments, events and campus life.', 'items' => []]],
            ['notices', ['_anchor' => 'notices', '_eyebrow' => 'Latest', 'heading' => 'Notices', 'empty' => 'There are no notices right now.', 'items' => [], '_bg' => 'tint']],
            ['contact', ['_anchor' => 'contact', 'heading' => 'Contact us', 'text' => 'Send a message and the office will get back to you.', 'show_form' => true, 'show_details' => true, 'show_map' => true]],
        ], true);

        $link = fn ($label, $url, $sort, $parent = null, $location = 'header') => $site->menuItems()->create(['location' => $location, 'label' => $label, 'url' => $url, 'parent_id' => $parent, 'sort' => $sort]);

        $site->menuItems()->create(['location' => 'header', 'label' => 'Home', 'site_page_id' => $home->id, 'sort' => 1]);
        $about = $link('About us', $base.'#about', 2);
        foreach (['About the school' => 'about', "Principal's message" => 'message', 'Mission and vision' => 'mission', 'Core values' => 'values'] as $i => $target) {
            $link($i, $base.'#'.$target, array_search($target, ['about', 'message', 'mission', 'values']) + 1, $about->id);
        }
        foreach (['Academics' => 'academics', 'Admissions' => 'admissions', 'Campuses' => 'campuses', 'Gallery' => 'gallery', 'Notices' => 'notices', 'Contact' => 'contact'] as $label => $target) {
            $link($label, $base.'#'.$target, 3 + array_search($target, ['academics', 'admissions', 'campuses', 'gallery', 'notices', 'contact']));
        }
        $portals = $link('Portals', '#', 9);
        $link('Teachers and staff', '/login', 1, $portals->id);
        $link('Parents', '/login', 2, $portals->id);

        foreach (['About us' => 'about', 'Admissions' => 'admissions', 'Gallery' => 'gallery', 'Contact' => 'contact'] as $label => $target) {
            $link($label, $base.'#'.$target, array_search($target, ['about', 'admissions', 'gallery', 'contact']) + 1, null, 'footer');
        }
    }
}
