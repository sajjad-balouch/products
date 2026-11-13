<?php

namespace App\Support;

use App\Models\School;
use App\Models\Site;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Describes every editable part of a website. The editor screens, their validation
 * and what gets saved all come from here, so a new field only needs adding once.
 * Editors can be non-admin staff, so no field accepts raw HTML or script.
 */
class SiteSchema
{
    private static function text(string $label, ?string $hint = null): array
    {
        return ['label' => $label, 'type' => 'text', 'hint' => $hint];
    }

    private static function area(string $label, ?string $hint = null): array
    {
        return ['label' => $label, 'type' => 'textarea', 'hint' => $hint];
    }

    private static function formatted(string $label): array
    {
        return ['label' => $label, 'type' => 'textarea', 'hint' => 'Formatting: **bold**, ## heading, - list, [link](https://example.com)'];
    }

    private static function link(string $label): array
    {
        return ['label' => $label, 'type' => 'url', 'hint' => 'A page such as /site/my-school/contact, or a full https:// address'];
    }

    private static function image(string $label): array
    {
        return ['label' => $label, 'type' => 'image', 'hint' => 'JPG, PNG or WebP, up to 4 MB'];
    }

    private static function toggle(string $label): array
    {
        return ['label' => $label, 'type' => 'toggle'];
    }

    private static function choice(string $label, array $options): array
    {
        return ['label' => $label, 'type' => 'select', 'options' => $options];
    }

    /** Website-wide settings, one group per tab. */
    public static function settings(): array
    {
        $styles = ['light' => 'White', 'brand' => 'Brand colour', 'dark' => 'Dark'];

        return [
            'design' => [
                'site_name' => self::text('Website name'),
                'logo' => self::image('Logo'),
                'show_name' => self::toggle('Show the name next to the logo'),
                'primary' => ['label' => 'Main colour', 'type' => 'color'],
                'accent' => ['label' => 'Accent colour', 'type' => 'color'],
                'font' => self::choice('Font', ['sans' => 'Clean (Inter)', 'serif' => 'Classic (serif)', 'urdu' => 'Urdu (Nastaliq)']),
                'direction' => self::choice('Text direction', ['ltr' => 'Left to right (English)', 'rtl' => 'Right to left (Urdu)']),
                'seo_description' => self::area('Description for search engines'),
            ],
            'header' => [
                'style' => self::choice('Header colour', $styles),
                'sticky' => self::toggle('Keep the header on screen while scrolling'),
                'topbar_show' => self::toggle('Show a strip above the header'),
                'topbar_text' => self::text('Strip text', 'For example: Admissions open for the new session'),
                'login_show' => self::toggle('Show the sign-in button'),
                'login_label' => self::text('Sign-in button text'),
                'cta_show' => self::toggle('Show a highlighted button'),
                'cta_label' => self::text('Highlighted button text'),
                'cta_url' => self::link('Highlighted button link'),
            ],
            'footer' => [
                'show' => self::toggle('Show the footer'),
                'style' => self::choice('Footer colour', $styles),
                'about' => self::area('About text'),
                'show_menu' => self::toggle('Show the footer menu'),
                'show_contact' => self::toggle('Show contact details'),
                'show_social' => self::toggle('Show social links'),
                'show_campuses' => self::toggle('Show campus locations'),
                'copyright' => self::text('Copyright line', 'Leave empty to use the website name and year'),
                'facebook' => self::link('Facebook'),
                'instagram' => self::link('Instagram'),
                'youtube' => self::link('YouTube'),
                'linkedin' => self::link('LinkedIn'),
                'x' => self::link('X (Twitter)'),
            ],
            'contact' => [
                'phone' => self::text('Phone'),
                'whatsapp' => self::text('WhatsApp number', 'With country code, for example 923001234567'),
                'email' => self::text('Email'),
                'address' => self::area('Address'),
                'map_address' => self::text('Address to show on the map', 'Leave empty to use the address above'),
            ],
            'popup' => [
                'show' => self::toggle('Show this notice when someone opens the website'),
                'heading' => self::text('Heading', 'For example: Admissions are open'),
                'text' => self::area('Text'),
                'image' => self::image('Picture'),
                'button_label' => self::text('Button text'),
                'button_url' => self::link('Button link'),
            ],
        ];
    }

    /** Lists inside a settings tab, edited the same way as the cards in a section. */
    public static function settingItems(): array
    {
        return [
            'contact' => [
                'key' => 'campuses',
                'item' => 'Campus',
                'plural' => 'Campuses',
                'hint' => 'Shown in the footer and in any Campuses section, so each is entered only once.',
                'fields' => [
                    'name' => self::text('Campus name'),
                    'badge' => self::text('Label', 'For example: Main campus'),
                    'address' => self::area('Address'),
                    'phone' => self::text('Phone'),
                    'about' => self::area('About this campus'),
                    'image' => self::image('Picture'),
                ],
            ],
        ];
    }

    public static function settingDefaults(): array
    {
        return [
            'design' => ['site_name' => '', 'logo' => null, 'show_name' => true, 'primary' => '#0e6a5a', 'accent' => '#d9822b', 'font' => 'sans', 'direction' => 'ltr', 'seo_description' => ''],
            'header' => ['style' => 'light', 'sticky' => true, 'topbar_show' => false, 'topbar_text' => '', 'login_show' => true, 'login_label' => 'Sign in', 'cta_show' => false, 'cta_label' => '', 'cta_url' => ''],
            'footer' => ['show' => true, 'style' => 'dark', 'about' => '', 'show_menu' => true, 'show_contact' => true, 'show_social' => true, 'show_campuses' => true, 'copyright' => '', 'facebook' => '', 'instagram' => '', 'youtube' => '', 'linkedin' => '', 'x' => ''],
            'contact' => ['phone' => '', 'whatsapp' => '', 'email' => '', 'address' => '', 'map_address' => '', 'campuses' => []],
            'popup' => ['show' => false, 'heading' => '', 'text' => '', 'image' => null, 'button_label' => '', 'button_url' => ''],
        ];
    }

    /** Fields every section has, for how it looks. */
    public static function sectionStyle(): array
    {
        return [
            '_eyebrow' => self::text('Small line above the heading', 'For example: Leadership'),
            '_bg' => self::choice('Background', ['white' => 'White', 'tint' => 'Light tint', 'brand' => 'Brand colour', 'dark' => 'Dark']),
            '_spacing' => self::choice('Space around it', ['normal' => 'Normal', 'compact' => 'Small', 'large' => 'Large']),
            '_anchor' => self::text('Menu link name', 'For example about. A menu link to #about then scrolls here.'),
        ];
    }

    /** The building blocks a page is made of. Each has a view in resources/views/site/sections. */
    public static function sections(): array
    {
        $button = ['button_label' => self::text('Button text'), 'button_url' => self::link('Button link')];

        return [
            'slider' => [
                'label' => 'Picture slider',
                'about' => 'Full-width pictures that change by themselves',
                'fields' => ['interval' => self::choice('Change picture every', ['6' => '6 seconds', '4' => '4 seconds', '9' => '9 seconds', '0' => 'Only when clicked'])],
                'item' => 'Slide',
                'items' => [
                    'image' => self::image('Picture'),
                    'eyebrow' => self::text('Small line above the heading'),
                    'heading' => self::text('Heading'),
                    'text' => self::area('Text'),
                ] + $button,
            ],
            'hero' => [
                'label' => 'Banner',
                'about' => 'Large heading at the top of a page',
                'fields' => [
                    'layout' => self::choice('Layout', ['split' => 'Text beside the picture', 'center' => 'Centred text', 'cover' => 'Text over the picture', 'product' => 'Product launch (screenshot in a browser, glowing background)']),
                    'heading' => self::text('Heading'),
                    'highlight' => self::text('Words to colour in the heading', 'Part of the heading, shown in a colourful gradient'),
                    'text' => self::area('Text'),
                    'image' => self::image('Picture'),
                    'image2' => self::image('Phone screenshot (product layout only)'),
                    'chips' => self::area('Small points under the buttons', 'One on each line, for example: 30-day free trial'),
                ] + $button + ['button2_label' => self::text('Second button text'), 'button2_url' => self::link('Second button link')],
            ],
            'showcase' => [
                'label' => 'Screenshot showcase',
                'about' => 'A large screenshot in a browser window, with an optional phone beside it',
                'fields' => [
                    'heading' => self::text('Heading'),
                    'intro' => self::area('Introduction'),
                    'image' => self::image('Screenshot'),
                    'image2' => self::image('Phone screenshot (optional)'),
                    'address' => self::text('Address shown in the browser bar', 'For example: yourschool.com/timetable'),
                    'caption' => self::text('Caption'),
                ],
            ],
            'steps' => [
                'label' => 'Steps',
                'about' => 'Numbered steps, such as how to get started',
                'fields' => ['heading' => self::text('Heading'), 'intro' => self::area('Introduction')],
                'item' => 'Step',
                'items' => ['title' => self::text('Title'), 'text' => self::area('Text')],
            ],
            'plans' => [
                'label' => 'Plans and prices',
                'about' => "Your plans with their prices and features, kept up to date from the owner panel. Only shows on the owner's own website.",
                'fields' => ['product' => self::choice('Which product', School::PRODUCTS), 'heading' => self::text('Heading'), 'intro' => self::area('Introduction'), 'note' => self::text('Small print under the plans')] + $button,
            ],
            'text' => [
                'label' => 'Text',
                'about' => 'Heading and paragraphs',
                'fields' => ['heading' => self::text('Heading'), 'body' => self::formatted('Text')],
            ],
            'image_text' => [
                'label' => 'Picture and text',
                'about' => 'A picture beside some text',
                'fields' => [
                    'heading' => self::text('Heading'),
                    'body' => self::formatted('Text'),
                    'image' => self::image('Picture'),
                    'image_side' => self::choice('Picture side', ['right' => 'Right', 'left' => 'Left']),
                    'highlights' => self::area('Highlights', 'One on each line. Shown as small tags under the text.'),
                ] + $button,
            ],
            'message' => [
                'label' => 'Message from the head',
                'about' => 'Chairman or principal message with a photo',
                'fields' => [
                    'heading' => self::text('Heading'),
                    'body' => self::formatted('Message'),
                    'image' => self::image('Photo'),
                    'image_side' => self::choice('Photo side', ['left' => 'Left', 'right' => 'Right']),
                    'name' => self::text('Name'),
                    'role' => self::text('Position'),
                    'motto' => self::text('Motto or closing line'),
                ],
            ],
            'campuses' => [
                'label' => 'Campuses',
                'about' => 'Campus cards, from Contact details in Website look',
                'fields' => ['heading' => self::text('Heading'), 'intro' => self::area('Introduction')],
            ],
            'features' => [
                'label' => 'Feature cards',
                'about' => 'Cards in a grid, such as facilities or programmes',
                'fields' => ['heading' => self::text('Heading'), 'intro' => self::area('Introduction'), 'columns' => self::choice('Cards in a row', ['3' => '3', '2' => '2', '4' => '4']), 'style' => self::choice('Style', ['cards' => 'Equal cards', 'bento' => 'Mixed sizes with large pictures (bento)'])],
                'item' => 'Card',
                'items' => ['image' => self::image('Picture (optional)'), 'title' => self::text('Title'), 'text' => self::formatted('Text'), 'link' => self::link('Link (optional)')],
            ],
            'stats' => [
                'label' => 'Numbers',
                'about' => 'Figures such as students or years of service',
                'fields' => ['heading' => self::text('Heading')],
                'item' => 'Number',
                'items' => ['value' => self::text('Number'), 'label' => self::text('What it counts')],
            ],
            'gallery' => [
                'label' => 'Picture gallery',
                'about' => 'A grid of pictures',
                'fields' => ['heading' => self::text('Heading')],
                'item' => 'Picture',
                'items' => ['image' => self::image('Picture'), 'caption' => self::text('Caption'), 'category' => self::text('Category', 'For example Events. Visitors can filter by category.')],
            ],
            'people' => [
                'label' => 'People',
                'about' => 'Principal, faculty or team',
                'fields' => ['heading' => self::text('Heading'), 'intro' => self::area('Introduction')],
                'item' => 'Person',
                'items' => ['image' => self::image('Photo'), 'name' => self::text('Name'), 'role' => self::text('Position'), 'bio' => self::area('About')],
            ],
            'testimonials' => [
                'label' => 'Testimonials',
                'about' => 'What parents or students say',
                'fields' => ['heading' => self::text('Heading')],
                'item' => 'Testimonial',
                'items' => ['quote' => self::area('What they said'), 'name' => self::text('Name'), 'role' => self::text('Who they are')],
            ],
            'notices' => [
                'label' => 'Notices',
                'about' => 'News and dated notices',
                'fields' => ['heading' => self::text('Heading'), 'empty' => self::text('Text when there are no notices')],
                'item' => 'Notice',
                'items' => ['date' => ['label' => 'Date', 'type' => 'date'], 'title' => self::text('Title'), 'body' => self::area('Details')],
            ],
            'faq' => [
                'label' => 'Questions and answers',
                'about' => 'Answers that open on click',
                'fields' => ['heading' => self::text('Heading')],
                'item' => 'Question',
                'items' => ['question' => self::text('Question'), 'answer' => self::area('Answer')],
            ],
            'pricing' => [
                'label' => 'Fees or plans',
                'about' => 'Price cards for fee packages or plans',
                'fields' => ['heading' => self::text('Heading'), 'intro' => self::area('Introduction')],
                'item' => 'Card',
                'items' => [
                    'name' => self::text('Name'),
                    'price' => self::text('Price'),
                    'note' => self::text('Under the price', 'For example: per month'),
                    'lines' => self::area('What is included', 'One point on each line'),
                ] + $button,
            ],
            'video' => [
                'label' => 'YouTube video',
                'about' => 'Plays a YouTube video on the page',
                'fields' => ['heading' => self::text('Heading'), 'url' => ['label' => 'YouTube link', 'type' => 'url'], 'caption' => self::text('Caption')],
            ],
            'cta' => [
                'label' => 'Call to action',
                'about' => 'A short message with a button',
                'fields' => ['heading' => self::text('Heading'), 'text' => self::area('Text')] + $button,
            ],
            'contact' => [
                'label' => 'Contact',
                'about' => 'Contact form, details and map',
                'fields' => [
                    'heading' => self::text('Heading'),
                    'text' => self::area('Text'),
                    'show_form' => self::toggle('Show the contact form'),
                    'show_details' => self::toggle('Show phone, email and address'),
                    'show_map' => self::toggle('Show the map'),
                ],
            ],
        ];
    }

    /** @return array<string, array<int, mixed>> validation rules for these fields under a prefix such as form or items.* */
    public static function rules(array $fields, string $prefix, Site $site): array
    {
        $rules = [];

        foreach ($fields as $key => $field) {
            $rules[$prefix.'.'.$key] = match ($field['type']) {
                'textarea' => ['nullable', 'string', 'max:10000'],
                'url' => ['nullable', 'string', 'max:500', fn ($attribute, $value, $fail) => self::safeUrl((string) $value) || $fail('Use a full https:// address, or a page address starting with /.')],
                'image' => ['nullable', 'integer', Rule::exists('site_media', 'id')->where('site_id', $site->id)],
                'select' => ['nullable', Rule::in(array_map('strval', array_keys($field['options'])))],
                'toggle' => ['boolean'],
                'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
                'date' => ['nullable', 'date'],
                default => ['nullable', 'string', 'max:200'],
            };
        }

        return $rules;
    }

    /** @return array<string, string> readable names for validation messages */
    public static function labels(array $fields, string $prefix): array
    {
        return collect($fields)->mapWithKeys(fn ($field, $key) => [$prefix.'.'.$key => Str::lower($field['label'])])->all();
    }

    /** Keeps only known fields, so nothing unexpected is ever stored. */
    public static function clean(array $fields, array $values): array
    {
        $clean = [];

        foreach ($fields as $key => $field) {
            $value = $values[$key] ?? null;
            $clean[$key] = match ($field['type']) {
                'toggle' => (bool) $value,
                'image' => $value ? (int) $value : null,
                default => $value === null ? '' : (string) $value,
            };
        }

        return $clean;
    }

    /** Blocks javascript: and data: links, which would run script for visitors. */
    public static function safeUrl(string $url): bool
    {
        return $url === '' || (bool) preg_match('~^(https?://|mailto:|tel:|/|#)~i', $url);
    }

    public static function markdown(?string $text): string
    {
        return Str::markdown((string) $text, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
    }

    public static function youtubeId(?string $url): ?string
    {
        return preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', (string) $url, $m) ? $m[1] : null;
    }

    /** Black or white, whichever reads better on this colour. */
    public static function textOn(string $hex): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x') + [0, 0, 0];

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 150 ? '#0f172a' : '#ffffff';
    }
}
