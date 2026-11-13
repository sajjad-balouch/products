<?php

namespace App\Support;

use App\Models\Site;
use App\Models\User;

class Navigation
{
    /** The icon shown beside each menu link, by route. See resources/views/components/icon.blade.php. */
    public const ICONS = [
        'dashboard' => 'home', 'platform.dashboard' => 'home', 'risk' => 'warning',
        'students.index' => 'users', 'admissions' => 'user-plus', 'students.import' => 'upload', 'students.promote' => 'arrow-up', 'certificates' => 'award', 'cards' => 'id-card',
        'attendance.mark' => 'check-square', 'attendance.report' => 'chart', 'diary' => 'book-open',
        'fees.vouchers' => 'wallet', 'fees.generate' => 'file-plus', 'fees.proofs' => 'image', 'fees.defaulters' => 'alert-circle', 'fees.closing' => 'cash', 'fees.heads' => 'tag', 'fees.structure' => 'layers',
        'exams.index' => 'clipboard', 'exams.marks' => 'pencil', 'exams.results' => 'trophy',
        'academics.sessions' => 'calendar', 'academics.classes' => 'grid', 'academics.sections' => 'columns', 'academics.subjects' => 'book', 'academics.class-subjects' => 'user-check', 'timetable' => 'clock',
        'staff.index' => 'briefcase', 'staff.attendance' => 'id-card', 'staff.attendance.register' => 'list', 'staff.devices' => 'device', 'staff.fingerprints' => 'fingerprint', 'staff.payroll' => 'banknote',
        'finance.expenses' => 'receipt', 'finance.report' => 'trend',
        'messages.index' => 'message', 'announcements' => 'megaphone',
        'library.books' => 'library', 'library.loans' => 'repeat', 'transport' => 'bus',
        'settings' => 'settings', 'users' => 'key', 'audit' => 'shield',
        'website.pages' => 'globe', 'website.design' => 'palette', 'website.menu' => 'menu', 'website.messages' => 'inbox', 'website.editors' => 'user-cog',
        'platform.schools' => 'building', 'platform.payments' => 'card', 'platform.plans' => 'package', 'platform.domains' => 'link', 'platform.messaging' => 'message',
        'biz.dashboard' => 'home', 'biz.contracts.new' => 'plus', 'biz.collect' => 'cash', 'biz.due' => 'alert-circle', 'biz.contracts' => 'layers', 'biz.customers' => 'users',
        'biz.vouchers' => 'receipt', 'biz.entry' => 'plus', 'biz.orders' => 'inbox', 'biz.vendors' => 'building', 'biz.employees' => 'user-cog', 'biz.reports.salesmen' => 'trend', 'biz.accounts' => 'list', 'biz.ledger' => 'book', 'biz.daybook' => 'calendar',
        'biz.reports.collections' => 'trend', 'biz.reports.aging' => 'clock', 'biz.reports.pl' => 'chart', 'biz.reports.bs' => 'columns', 'biz.reports.trial' => 'check-square',
        'biz.settings' => 'settings', 'biz.products' => 'package', 'biz.reports.profit' => 'percent', 'biz.sales.new' => 'receipt', 'biz.sales' => 'list',
    ];

    /** The menu of a business (installments and accounts) instead of a school. */
    private static function business(User $user): array
    {
        $sales = ['Sales and stock' => [['New sale (cash / wholesale)', 'biz.sales.new'], ['Orders from salesmen', 'biz.orders'], ['Sales and udhaar', 'biz.sales'], ['Products and stock', 'biz.products'], ['Customers', 'biz.customers']]];
        $installments = ['Installments' => [['New installment sale', 'biz.contracts.new'], ['Collect payment', 'biz.collect'], ['Due and recovery', 'biz.due'], ['Installment plans', 'biz.contracts']]];
        // A shop that sells mostly over the counter sees sales first; a dealer selling on qist sees installments first.
        $first = BusinessKinds::focus(BusinessKinds::of($user->school)) === 'sales' ? $sales + $installments : $installments + $sales;

        $links = ['' => [['Dashboard', 'biz.dashboard']]] + $first + [
            'Vendors and staff' => [['Vendors (suppliers)', 'biz.vendors'], ['Employees and salesmen', 'biz.employees'], ['Salesman report', 'biz.reports.salesmen']],
            'Accounts' => [['New entry (what happened?)', 'biz.entry'], ['Vouchers', 'biz.vouchers'], ['Chart of accounts', 'biz.accounts'], ['Ledger', 'biz.ledger'], ['Day book', 'biz.daybook']],
            'Reports' => [['Profit on sales', 'biz.reports.profit'], ['Collections', 'biz.reports.collections'], ['Aging of dues', 'biz.reports.aging'], ['Profit and loss', 'biz.reports.pl'], ['Balance sheet', 'biz.reports.bs'], ['Trial balance', 'biz.reports.trial']],
            'Communication' => [['WhatsApp outbox', 'messages.index']],
        ];

        return $user->isRole('admin') ? $links + ['Admin' => [['Business settings', 'biz.settings'], ['User logins', 'users'], ['Audit log', 'audit']]] : $links;
    }

    public static function icon(string $route): string
    {
        return self::ICONS[$route] ?? 'grid';
    }

    /** Every route this user's menu leads to, to show only shortcuts they can open. */
    public static function routes(User $user): array
    {
        return collect(static::for($user))->flatten(1)->pluck(1)->all();
    }

    public static function for(User $user): array
    {
        $links = $user->school?->isBusiness() && $user->isRole('admin', 'accountant') ? static::business($user) : static::links($user);
        $website = static::website($user);
        $links = $website ? $links + ['Website' => $website] : $links;

        // Features the owner switched off for this school drop out of the menu.
        $school = $user->school;
        if (! $school) {
            return $links;
        }

        return array_filter(array_map(
            fn ($group) => array_values(array_filter($group, fn ($link) => $school->allowsRoute($link[1]))),
            $links,
        ));
    }

    /** The website links this user's permissions lead to. */
    private static function website(User $user): array
    {
        if ($user->isRole('admin', 'super_admin')) {
            return [['Pages', 'website.pages'], ['Look, header and footer', 'website.design'], ['Menus', 'website.menu'], ['Messages', 'website.messages'], ['Website editors', 'website.editors']];
        }

        if ($user->isRole('parent', 'student')) {
            return [];
        }

        $site = Site::where('owner_key', $user->school_id ? 'school:'.$user->school_id : 'platform')->first();

        if (! $site?->allowsAnything($user)) {
            return $user->isRole('website_editor') ? [['Pages', 'website.pages']] : [];
        }

        return array_values(array_filter([
            ['Pages', 'website.pages'],
            $site->designTabs($user) ? ['Look, header and footer', 'website.design'] : null,
            $site->allows($user, 'menu') ? ['Menus', 'website.menu'] : null,
            $site->allows($user, 'messages') ? ['Messages', 'website.messages'] : null,
        ]));
    }

    private static function links(User $user): array
    {
        return match ($user->role) {
            'super_admin' => ['' => [['Dashboard', 'platform.dashboard'], ['Schools', 'platform.schools'], ['Payments', 'platform.payments'], ['Plans', 'platform.plans'], ['Domains', 'platform.domains'], ['Messaging', 'platform.messaging']]],
            'website_editor' => [],
            'accountant' => [
                '' => [['Dashboard', 'dashboard']],
                'Students' => [['Students', 'students.index']],
                'Fees' => static::fees(),
                'Finance' => [['Expenses', 'finance.expenses'], ['Income & expense', 'finance.report']],
                'Communication' => [['WhatsApp outbox', 'messages.index']],
            ],
            'teacher' => [
                '' => [['Dashboard', 'dashboard']],
                'Teaching' => [
                    ['Mark attendance', 'attendance.mark'],
                    ['Attendance report', 'attendance.report'],
                    ['Enter marks', 'exams.marks'],
                    ['Daily diary', 'diary'],
                    ['Timetable', 'timetable'],
                ],
            ],
            default => [
                '' => [['Dashboard', 'dashboard'], ['Early warning', 'risk']],
                'Students' => [
                    ['Students', 'students.index'],
                    ['Admissions pipeline', 'admissions'],
                    ['Import from Excel', 'students.import'],
                    ['Promote students', 'students.promote'],
                    ['Certificates', 'certificates'],
                    ['ID cards', 'cards'],
                ],
                'Daily' => [
                    ['Mark attendance', 'attendance.mark'],
                    ['Attendance report', 'attendance.report'],
                    ['Daily diary', 'diary'],
                ],
                'Fees' => static::fees(),
                'Exams' => [['Exams & papers', 'exams.index'], ['Enter marks', 'exams.marks'], ['Results', 'exams.results']],
                'Academics' => [
                    ['Sessions', 'academics.sessions'],
                    ['Classes', 'academics.classes'],
                    ['Sections', 'academics.sections'],
                    ['Subjects', 'academics.subjects'],
                    ['Subject teachers', 'academics.class-subjects'],
                    ['Timetable', 'timetable'],
                ],
                'Staff & finance' => [
                    ['Staff', 'staff.index'],
                    ['Staff attendance', 'staff.attendance'],
                    ['Attendance register', 'staff.attendance.register'],
                    ['Attendance machines', 'staff.devices'],
                    ['Staff fingerprints', 'staff.fingerprints'],
                    ['Payroll', 'staff.payroll'],
                    ['Expenses', 'finance.expenses'],
                    ['Income & expense', 'finance.report'],
                ],
                'Communication' => [['WhatsApp outbox', 'messages.index'], ['Announcements', 'announcements']],
                'Campus' => [['Library', 'library.books'], ['Book loans', 'library.loans'], ['Transport', 'transport']],
                'Admin' => [['Settings', 'settings'], ['User logins', 'users'], ['Audit log', 'audit']],
            ],
        };
    }

    private static function fees(): array
    {
        return [
            ['Vouchers & collection', 'fees.vouchers'],
            ['Generate vouchers', 'fees.generate'],
            ['Payment proofs', 'fees.proofs'],
            ['Defaulters', 'fees.defaulters'],
            ['Daily cash closing', 'fees.closing'],
            ['Fee heads', 'fees.heads'],
            ['Fee structure', 'fees.structure'],
        ];
    }
}
