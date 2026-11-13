<?php

use App\Http\Controllers\App\SalesAppPageController;
use App\Http\Controllers\BiometricController;
use App\Http\Controllers\BizPrintController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FingerprintHelperController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\SiteController;
use App\Livewire;
use App\Models\SubscriptionEntry;
use App\Support\Impersonation;
use App\Support\SiteHost;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// The salesman / owner phone app, at /sales (not /app: that is the code folder, blocked on shared hosting). It signs in on its own (routes/app.php), so these pages are public.
Route::get('/sales', [SalesAppPageController::class, 'show'])->name('app');
Route::get('/sales/manifest.webmanifest', [SalesAppPageController::class, 'manifest'])->name('app.manifest');
Route::get('/sales/sw.js', [SalesAppPageController::class, 'worker'])->name('app.sw');
Route::get('/.well-known/assetlinks.json', [SalesAppPageController::class, 'assetLinks'])->name('app.assetlinks');

Route::get('/', [SiteController::class, 'home'])->name('home');

// Public websites
Route::get('/p/{page}', [SiteController::class, 'page'])->name('site.page');
Route::get('/site/{school}', [SiteController::class, 'schoolHome'])->name('site.school');
Route::get('/site/{school}/{page}', [SiteController::class, 'schoolPage'])->name('site.school.page');
Route::get('/media/{media}', [SiteController::class, 'media'])->name('site.media');
// Asked by the web server (Caddy on-demand TLS) before it makes an SSL certificate for an address.
Route::get('/domains/allowed', fn (Request $request) => SiteHost::resolve($request->query('domain')) || SiteHost::isAppHost((string) $request->query('domain')) ? response('ok') : response('unknown', 404))->name('domains.allowed');
Route::post('/site-contact/{site}', [SiteController::class, 'contact'])->middleware('throttle:5,1')->name('site.contact');

// Attendance machines. They cannot send a CSRF token or keep a session, so they are checked by serial number or token.
Route::withoutMiddleware([ValidateCsrfToken::class])->middleware('throttle:240,1')->group(function () {
    Route::match(['get', 'post'], '/iclock/cdata', [BiometricController::class, 'cdata'])->name('biometric.cdata');
    Route::match(['get', 'post'], '/iclock/cdata.aspx', [BiometricController::class, 'cdata']);
    Route::get('/iclock/getrequest', [BiometricController::class, 'getRequest']);
    Route::get('/iclock/getrequest.aspx', [BiometricController::class, 'getRequest']);
    Route::post('/iclock/devicecmd', [BiometricController::class, 'deviceCommand']);
    Route::post('/iclock/devicecmd.aspx', [BiometricController::class, 'deviceCommand']);
    Route::post('/api/biometric/punches', [BiometricController::class, 'api'])->name('biometric.api');
    Route::post('/kiosk/punch', [KioskController::class, 'punch'])->name('kiosk.punch');
});

// The fingerprint kiosk for USB readers. Recognised by its cookie, nobody signs in.
Route::get('/kiosk/open/{serial}', [KioskController::class, 'open'])->middleware('throttle:20,1')->name('kiosk.open');
Route::get('/kiosk', [KioskController::class, 'show'])->name('kiosk');
Route::get('/kiosk/candidates', [KioskController::class, 'candidates'])->middleware('throttle:30,1')->name('kiosk.candidates');

Route::middleware('guest')->group(function () {
    Route::get('/login', Livewire\Auth\Login::class)->name('login');
    Route::get('/signup', Livewire\Auth\Signup::class)->name('signup');
});

// Public pages
Route::get('/apply/{slug}', Livewire\Admissions\PublicForm::class)->name('apply');
Route::get('/verify/{token}', [DocumentController::class, 'verify'])->name('certificate.verify');

Route::get('/locale/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, ['en', 'ur'], true), 404);
    $request->session()->put('locale', $locale);
    $request->user()?->update(['locale' => $locale]);

    return back();
})->name('locale');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/password', Livewire\Auth\ChangePassword::class)->name('password.change');

    // Print documents check permissions per document.
    Route::get('/print/voucher/{id}', [DocumentController::class, 'voucher'])->name('print.voucher');
    Route::get('/print/receipt/{id}', [DocumentController::class, 'receipt'])->name('print.receipt');
    Route::get('/print/report-card/{exam}/{student}', [DocumentController::class, 'reportCard'])->name('print.report-card');
    Route::get('/proofs/{id}/image', [DocumentController::class, 'proofImage'])->name('proofs.image');

    // Each website screen checks the user's own permissions on the site.
    Route::get('/website', Livewire\Website\Pages::class)->name('website.pages');
    Route::get('/website/pages/{page}', Livewire\Website\PageEditor::class)->name('website.page');
    Route::get('/website/look', Livewire\Website\Design::class)->name('website.design');
    Route::get('/website/menus', Livewire\Website\Menu::class)->name('website.menu');
    Route::get('/website/messages', Livewire\Website\Messages::class)->name('website.messages');
    Route::get('/website/editors', Livewire\Website\Editors::class)->name('website.editors');

    Route::middleware('role:parent,student')->get('/portal', Livewire\Portal\Home::class)->name('portal');

    Route::get('/subscription-ended', fn () => Auth::user()->school?->isLocked() ? view('subscription-expired') : redirect()->route(Auth::user()->homeRoute()))->name('subscription.expired');

    Route::post('/impersonation/stop', function () {
        $schoolId = Impersonation::stop();

        return $schoolId ? redirect()->route('platform.school', $schoolId) : redirect()->route('login');
    })->name('impersonation.stop');

    Route::middleware('role:super_admin')->group(function () {
        Route::get('/platform', Livewire\Platform\Dashboard::class)->name('platform.dashboard');
        Route::get('/platform/schools', Livewire\Platform\Schools::class)->name('platform.schools');
        Route::get('/platform/schools/{school}', Livewire\Platform\SchoolShow::class)->name('platform.school');
        Route::get('/platform/payments', Livewire\Platform\Payments::class)->name('platform.payments');
        Route::get('/platform/plans', Livewire\Platform\Plans::class)->name('platform.plans');
        Route::get('/platform/domains', Livewire\Platform\Domains::class)->name('platform.domains');
        Route::get('/platform/messaging', Livewire\Platform\Messaging::class)->name('platform.messaging');
        // The MSK SMS Gateway app for the owner's own Android phone.
        Route::get('/platform/messaging/gateway-app', fn () => response()->download(resource_path('apps/msk-sms-gateway.apk'), 'msk-sms-gateway.apk', ['Content-Type' => 'application/vnd.android.package-archive']))->name('platform.gateway-app');
        Route::get('/platform/receipts/{entry}', function (SubscriptionEntry $entry) {
            abort_unless($entry->type === 'payment', 404);

            return view('print.subscription-receipt', ['entry' => $entry, 'school' => $entry->school, 'number' => 'S-'.str_pad((string) $entry->id, 5, '0', STR_PAD_LEFT)]);
        })->name('platform.receipt');
    });

    Route::middleware('role:admin,accountant,teacher')->get('/dashboard', Livewire\Dashboard::class)->name('dashboard');

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/attendance', Livewire\Attendance\Mark::class)->name('attendance.mark');
        Route::get('/attendance/report', Livewire\Attendance\Report::class)->name('attendance.report');
        Route::get('/exams/marks', Livewire\Exams\Marks::class)->name('exams.marks');
        Route::get('/diary', Livewire\Diary::class)->name('diary');
        Route::get('/timetable', Livewire\Academics\Timetable::class)->name('timetable');
    });

    Route::middleware('role:admin,accountant')->group(function () {
        Route::get('/students', Livewire\Students\Index::class)->name('students.index');
        Route::get('/students/{student}/profile', Livewire\Students\Profile::class)->name('students.show');
        Route::get('/fees/vouchers', Livewire\Fees\Vouchers::class)->name('fees.vouchers');
        Route::get('/fees/generate', Livewire\Fees\Generate::class)->name('fees.generate');
        Route::get('/fees/proofs', Livewire\Fees\Proofs::class)->name('fees.proofs');
        Route::get('/fees/defaulters', Livewire\Fees\Defaulters::class)->name('fees.defaulters');
        Route::get('/fees/defaulters/export', [DocumentController::class, 'defaultersCsv'])->name('fees.defaulters.export');
        Route::get('/fees/closing', Livewire\Fees\Closing::class)->name('fees.closing');
        Route::get('/fees/heads', Livewire\Fees\Heads::class)->name('fees.heads');
        Route::get('/fees/structure', Livewire\Fees\Structure::class)->name('fees.structure');
        Route::get('/finance/expenses', Livewire\Finance\Expenses::class)->name('finance.expenses');
        Route::get('/finance/report', Livewire\Finance\Report::class)->name('finance.report');
        Route::get('/messages', Livewire\Messages::class)->name('messages.index');
    });

    Route::middleware('role:admin')->group(function () {
        Route::get('/risk', Livewire\Risk::class)->name('risk');
        Route::get('/students/form/{student?}', Livewire\Students\Form::class)->name('students.form');
        Route::get('/students/import', Livewire\Students\Import::class)->name('students.import');
        Route::get('/students/import/template', [DocumentController::class, 'importTemplate'])->name('students.import.template');
        Route::get('/students/promote', Livewire\Students\Promote::class)->name('students.promote');
        Route::get('/admissions', Livewire\Admissions\Board::class)->name('admissions');
        Route::get('/certificates', Livewire\Certificates::class)->name('certificates');
        Route::get('/print/certificate/{id}', [DocumentController::class, 'certificate'])->name('print.certificate');
        Route::get('/print/id-cards/{section}', [DocumentController::class, 'idCards'])->name('print.id-cards');
        Route::get('/cards', Livewire\Cards::class)->name('cards');
        Route::get('/print/cards', [DocumentController::class, 'cards'])->name('print.cards');
        Route::get('/cards/photo/{type}/{id}', [DocumentController::class, 'cardPhoto'])->whereIn('type', ['student', 'guardian', 'pickup'])->whereNumber('id')->name('cards.photo');
        Route::get('/cards/asset/{kind}', [DocumentController::class, 'cardAsset'])->whereIn('kind', ['logo', 'signature'])->name('cards.asset');
        Route::get('/print/payslip/{id}', [DocumentController::class, 'payslip'])->name('print.payslip');
        Route::get('/exams', Livewire\Exams\Index::class)->name('exams.index');
        Route::get('/exams/results', Livewire\Exams\Results::class)->name('exams.results');
        Route::get('/academics/sessions', Livewire\Academics\Sessions::class)->name('academics.sessions');
        Route::get('/academics/classes', Livewire\Academics\Classes::class)->name('academics.classes');
        Route::get('/academics/sections', Livewire\Academics\Sections::class)->name('academics.sections');
        Route::get('/academics/subjects', Livewire\Academics\Subjects::class)->name('academics.subjects');
        Route::get('/academics/class-subjects', Livewire\Academics\ClassSubjects::class)->name('academics.class-subjects');
        Route::get('/staff', Livewire\Staff\Directory::class)->name('staff.index');
        Route::get('/staff/attendance', Livewire\Staff\AttendanceSheet::class)->name('staff.attendance');
        Route::get('/staff/attendance/register', Livewire\Staff\AttendanceRegister::class)->name('staff.attendance.register');
        Route::get('/staff/devices', Livewire\Staff\Devices::class)->name('staff.devices');
        Route::get('/staff/fingerprints', Livewire\Staff\Fingerprints::class)->name('staff.fingerprints');
        Route::get('/staff/fingerprint-helper', FingerprintHelperController::class)->name('staff.fingerprint-helper');
        Route::get('/staff/payroll', Livewire\Staff\PayrollRun::class)->name('staff.payroll');
        Route::get('/announcements', Livewire\Announcements::class)->name('announcements');
        Route::get('/library/books', Livewire\Library\Books::class)->name('library.books');
        Route::get('/library/loans', Livewire\Library\Loans::class)->name('library.loans');
        Route::get('/transport', Livewire\Transport::class)->name('transport');
        Route::get('/settings', Livewire\Settings::class)->name('settings');
        Route::get('/users', Livewire\Users::class)->name('users');
        Route::get('/audit', Livewire\Audit::class)->name('audit');
    });

    // Business product: installment sales and double-entry accounts. Only businesses can open these.
    Route::middleware('role:admin,accountant')->prefix('business')->name('biz.')->group(function () {
        Route::get('/', Livewire\Biz\Dashboard::class)->name('dashboard');
        Route::get('/customers', Livewire\Biz\Customers::class)->name('customers');
        Route::get('/customers/{customer}', Livewire\Biz\CustomerShow::class)->whereNumber('customer')->name('customer');
        Route::get('/customers/{customer}/photo', [BizPrintController::class, 'customerPhoto'])->whereNumber('customer')->name('customer.photo');
        Route::get('/plans', Livewire\Biz\Contracts::class)->name('contracts');
        Route::get('/plans/new', Livewire\Biz\ContractForm::class)->name('contracts.new');
        Route::get('/plans/{contract}', Livewire\Biz\ContractShow::class)->whereNumber('contract')->name('contract');
        Route::get('/collect', Livewire\Biz\Collect::class)->name('collect');
        Route::get('/products', Livewire\Biz\Products::class)->name('products');
        Route::get('/products/{product}', Livewire\Biz\ProductShow::class)->whereNumber('product')->name('product');
        Route::get('/reports/sales-profit', Livewire\Biz\SalesProfit::class)->name('reports.profit');
        Route::get('/sales', Livewire\Biz\SalesList::class)->name('sales');
        Route::get('/sales/new', Livewire\Biz\SaleForm::class)->name('sales.new');
        Route::get('/sales/{invoice}', Livewire\Biz\SaleShow::class)->whereNumber('invoice')->name('sale');
        Route::get('/print/invoice/{invoice}', [BizPrintController::class, 'invoice'])->whereNumber('invoice')->name('print.invoice');
        Route::get('/due', Livewire\Biz\Due::class)->name('due');
        Route::get('/accounts', Livewire\Biz\Accounts::class)->name('accounts');
        Route::get('/vouchers', Livewire\Biz\Vouchers::class)->name('vouchers');
        Route::get('/entry', Livewire\Biz\EasyEntry::class)->name('entry');
        Route::get('/orders', Livewire\Biz\Orders::class)->name('orders');
        Route::get('/vendors', Livewire\Biz\Vendors::class)->name('vendors');
        Route::get('/vendors/{vendor}', Livewire\Biz\VendorShow::class)->name('vendor');
        Route::get('/employees', Livewire\Biz\Employees::class)->name('employees');
        Route::get('/employees/{employee}', Livewire\Biz\EmployeeShow::class)->name('employee');
        Route::get('/reports/salesmen', Livewire\Biz\SalesmanReport::class)->name('reports.salesmen');
        Route::get('/ledger', Livewire\Biz\LedgerReport::class)->name('ledger');
        Route::get('/day-book', Livewire\Biz\DayBook::class)->name('daybook');
        Route::get('/reports/collections', Livewire\Biz\CollectionReport::class)->name('reports.collections');
        Route::get('/reports/aging', Livewire\Biz\AgingReport::class)->name('reports.aging');
        Route::get('/reports/profit-and-loss', Livewire\Biz\ProfitLoss::class)->name('reports.pl');
        Route::get('/reports/balance-sheet', Livewire\Biz\BalanceSheet::class)->name('reports.bs');
        Route::get('/reports/trial-balance', Livewire\Biz\TrialBalance::class)->name('reports.trial');
        Route::get('/print/receipt/{receipt}', [BizPrintController::class, 'receipt'])->whereNumber('receipt')->name('print.receipt');
        Route::get('/print/statement/{contract}', [BizPrintController::class, 'statement'])->whereNumber('contract')->name('print.statement');
        Route::get('/print/agreement/{contract}', [BizPrintController::class, 'agreement'])->whereNumber('contract')->name('print.agreement');
        Route::get('/print/voucher/{entry}', [BizPrintController::class, 'voucher'])->whereNumber('entry')->name('print.voucher');
        Route::get('/settings', Livewire\Biz\Settings::class)->middleware('role:admin')->name('settings');
    });
});
