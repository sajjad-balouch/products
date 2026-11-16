<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\Biz\Brand;
use App\Models\Biz\BrandModel;
use App\Models\Biz\Category;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\ClassSubject;
use App\Models\FeeHead;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\TimetablePeriod;
use App\Services\Biz\ChartOfAccounts;
use App\Services\Biz\StarterCatalog;
use App\Support\BusinessKinds;
use Illuminate\Support\Facades\DB;

/**
 * What a new account starts with, so it is usable on the first day.
 * A school gets its session, classes with section A, subjects for each class, fee heads and a day of periods.
 * A business gets its chart of accounts, product types, categories, brands and a few starter products.
 *
 * Each area is filled only while it is still empty, so running it again (or on an account that set things up
 * its own way) adds nothing twice and never puts "Class 1" next to a school's own "One".
 */
class StarterData
{
    /** [class, college group, sort, subjects] for the school side */
    public const SCHOOL_CLASSES = [
        ['Playgroup', null, 1, ['English', 'Urdu', 'Mathematics', 'General Knowledge']],
        ['Nursery', null, 2, ['English', 'Urdu', 'Mathematics', 'General Knowledge']],
        ['Prep', null, 3, ['English', 'Urdu', 'Mathematics', 'General Knowledge']],
        ['Class 1', null, 4, ['English', 'Urdu', 'Mathematics', 'General Knowledge', 'Islamiat']],
        ['Class 2', null, 5, ['English', 'Urdu', 'Mathematics', 'General Knowledge', 'Islamiat']],
        ['Class 3', null, 6, ['English', 'Urdu', 'Mathematics', 'General Knowledge', 'Islamiat']],
        ['Class 4', null, 7, ['English', 'Urdu', 'Mathematics', 'Science', 'Social Studies', 'Islamiat']],
        ['Class 5', null, 8, ['English', 'Urdu', 'Mathematics', 'Science', 'Social Studies', 'Islamiat']],
        ['Class 6', null, 9, ['English', 'Urdu', 'Mathematics', 'Science', 'Social Studies', 'Islamiat', 'Computer Science']],
        ['Class 7', null, 10, ['English', 'Urdu', 'Mathematics', 'Science', 'Social Studies', 'Islamiat', 'Computer Science']],
        ['Class 8', null, 11, ['English', 'Urdu', 'Mathematics', 'Science', 'Social Studies', 'Islamiat', 'Computer Science']],
        ['Class 9', null, 12, ['English', 'Urdu', 'Mathematics', 'Islamiat', 'Physics', 'Chemistry', 'Biology', 'Computer Science']],
        ['Class 10', null, 13, ['English', 'Urdu', 'Mathematics', 'Pakistan Studies', 'Physics', 'Chemistry', 'Biology', 'Computer Science']],
    ];

    /** college group => elective subjects; English, Urdu and Islamiat (XI) or Pakistan Studies (XII) are added to each */
    public const COLLEGE_GROUPS = [
        'Pre-Medical' => ['Physics', 'Chemistry', 'Biology'],
        'Pre-Engineering' => ['Physics', 'Chemistry', 'Mathematics'],
        'ICS' => ['Physics', 'Mathematics', 'Computer Science'],
        'I.Com' => ['Principles of Accounting', 'Principles of Economics', 'Business Mathematics', 'Principles of Commerce'],
        'F.A.' => ['Economics', 'Civics', 'Education'],
    ];

    /** [name, starts, ends, is break] */
    public const PERIODS = [
        ['Period 1', '08:00', '08:40', false],
        ['Period 2', '08:40', '09:20', false],
        ['Period 3', '09:20', '10:00', false],
        ['Period 4', '10:00', '10:40', false],
        ['Break', '10:40', '11:10', true],
        ['Period 5', '11:10', '11:50', false],
        ['Period 6', '11:50', '12:30', false],
        ['Period 7', '12:30', '13:10', false],
    ];

    /** fee head => charged every month */
    public const FEE_HEADS = ['Tuition Fee' => true, 'Admission Fee' => false, 'Annual Charges' => false, 'Exam Fee' => false];

    /**
     * kind => [type, category, brand, model] for a few products to start selling from. Prices start at 0 and
     * the notes say so; the owner sets them on Products and stock.
     */
    public const STARTER_PRODUCTS = [
        'mobile' => [
            ['Mobile phone', 'Mobile phones', 'Samsung', 'Galaxy A15'],
            ['Mobile phone', 'Mobile phones', 'Infinix', 'Hot 40i'],
            ['Mobile phone', 'Mobile phones', 'Tecno', 'Spark 20'],
            ['Accessory / general', 'Chargers and cables', 'Faster', 'Charger'],
            ['Accessory / general', 'Earphones and audio', 'Audionic', 'Earbuds'],
        ],
        'motorcycle' => [
            ['Motorcycle', 'Motorcycles', 'Honda', 'CD 70'],
            ['Motorcycle', 'Motorcycles', 'Honda', 'CG 125'],
            ['Motorcycle', 'Motorcycles', 'Yamaha', 'YBR 125'],
            ['Motorcycle', 'Motorcycles', 'Suzuki', 'GD 110S'],
        ],
        'electronics' => [
            ['Electronics / appliance', 'Home appliances', 'Haier', 'Refrigerator'],
            ['Electronics / appliance', 'Home appliances', 'Gree', 'Inverter AC 1.5 ton'],
            ['Electronics / appliance', 'Electronics', 'TCL', 'LED TV 43"'],
            ['Electronics / appliance', 'Home appliances', 'Super Asia', 'Washing machine'],
        ],
        'furniture' => [
            ['Furniture', 'Furniture', 'Interwood', 'Bed set'],
            ['Furniture', 'Furniture', 'Interwood', 'Sofa set'],
            ['Furniture', 'Furniture', 'Interwood', 'Dining table'],
        ],
        'solar' => [
            ['Solar system', 'Solar', 'Longi', 'Hi-MO 6 580W'],
            ['Solar system', 'Solar', 'Jinko', 'Tiger Neo 585W'],
            ['Solar system', 'Solar', 'Inverex', 'Nitrox 6kW'],
        ],
        'car' => [
            ['Car', 'Cars', 'Suzuki', 'Alto'],
            ['Car', 'Cars', 'Toyota', 'Corolla'],
            ['Car', 'Cars', 'Honda', 'City'],
        ],
        'general' => [
            ['Motorcycle', 'Motorcycles', 'Honda', 'CD 70'],
            ['Mobile phone', 'Mobile phones', 'Samsung', 'Galaxy A15'],
            ['Electronics / appliance', 'Home appliances', 'Haier', 'Refrigerator'],
        ],
    ];

    /**
     * The base (session, fee heads, timetable; or accounts and catalog) is always added. With $full, also the
     * classes, sections, subjects and periods of a school, or a business's starter products.
     *
     * @return array<string, int> what was added, by area
     */
    public function seed(School $school, bool $full = true): array
    {
        return DB::transaction(fn () => $school->isBusiness() ? $this->business($school, $full) : $this->school($school, $full));
    }

    private function school(School $school, bool $full): array
    {
        $id = $school->id;
        $added = [];

        if (! AcademicSession::withoutGlobalScopes()->where('school_id', $id)->exists()) {
            $year = now()->month >= 8 ? now()->year : now()->year - 1;
            AcademicSession::create([
                'school_id' => $id,
                'name' => $year.'-'.substr((string) ($year + 1), -2),
                'starts_on' => "$year-08-01",
                'ends_on' => ($year + 1).'-07-31',
                'is_current' => true,
            ]);
            $added['sessions'] = 1;
        }

        if (! FeeHead::withoutGlobalScopes()->where('school_id', $id)->exists()) {
            foreach (self::FEE_HEADS as $head => $monthly) {
                FeeHead::create(['school_id' => $id, 'name' => $head, 'is_monthly' => $monthly]);
            }
            $added['fee heads'] = count(self::FEE_HEADS);
        }

        $timetable = Timetable::withoutGlobalScopes()->where('school_id', $id)->where('is_active', true)->first()
            ?? Timetable::withoutGlobalScopes()->firstOrCreate(['school_id' => $id, 'name' => 'Main timetable'], ['is_active' => true]);

        if (! $full) {
            return $added;
        }

        if (! TimetablePeriod::withoutGlobalScopes()->where('timetable_id', $timetable->id)->exists()) {
            foreach (self::PERIODS as $sort => [$name, $starts, $ends, $break]) {
                TimetablePeriod::create(['school_id' => $id, 'timetable_id' => $timetable->id, 'name' => $name, 'starts_at' => $starts, 'ends_at' => $ends, 'is_break' => $break, 'sort' => $sort + 1]);
            }
            $added['periods'] = count(self::PERIODS);
        }

        // Classes, their sections and subjects come as one set: only for a school with no classes yet.
        if (! SchoolClass::withoutGlobalScopes()->where('school_id', $id)->exists()) {
            $classes = $this->classesFor($school->type);
            $subjects = [];

            foreach ($classes as [$name, $stream, $sort, $subjectNames]) {
                $class = SchoolClass::withoutGlobalScopes()->firstOrCreate(['school_id' => $id, 'name' => $name, 'stream' => $stream], ['sort' => $sort]);
                Section::withoutGlobalScopes()->firstOrCreate(['school_id' => $id, 'school_class_id' => $class->id, 'name' => 'A']);

                foreach ($subjectNames as $subjectName) {
                    $subjects[$subjectName] ??= Subject::withoutGlobalScopes()->firstOrCreate(['school_id' => $id, 'name' => $subjectName])->id;
                    ClassSubject::withoutGlobalScopes()->firstOrCreate(['school_id' => $id, 'school_class_id' => $class->id, 'subject_id' => $subjects[$subjectName]]);
                }
            }

            $added['classes'] = count($classes);
            $added['sections'] = count($classes);
            $added['subjects'] = count($subjects);
        }

        return $added;
    }

    /** @return array<int, array{0: string, 1: ?string, 2: int, 3: array<int, string>}> */
    private function classesFor(?string $type): array
    {
        $classes = $type === 'college' ? [] : self::SCHOOL_CLASSES;

        if (in_array($type, ['college', 'both'], true)) {
            $sort = 14;
            foreach (['XI' => 'Islamiat', 'XII' => 'Pakistan Studies'] as $year => $compulsory) {
                foreach (self::COLLEGE_GROUPS as $group => $electives) {
                    $classes[] = [$year, $group, $sort, array_merge(['English', 'Urdu', $compulsory], $electives)];
                }
                $sort++;
            }
        }

        return $classes;
    }

    private function business(School $school, bool $full): array
    {
        $id = $school->id;
        $added = [];

        // The accounts with a system key are what sales and collections post to, so missing ones are always added.
        $before = DB::table('biz_accounts')->where('school_id', $id)->count();
        ChartOfAccounts::seed($school);
        if ($after = DB::table('biz_accounts')->where('school_id', $id)->count() - $before) {
            $added['accounts'] = $after;
        }

        if (! ProductType::withoutGlobalScopes()->where('school_id', $id)->exists() && ! Category::withoutGlobalScopes()->where('school_id', $id)->exists()) {
            // Only what this kind of business sells: a mobile shop gets phones and accessories, not motorcycles.
            StarterCatalog::seed($id, $school->business_kind);
            $added['product types'] = ProductType::withoutGlobalScopes()->where('school_id', $id)->count();
            $added['categories'] = Category::withoutGlobalScopes()->where('school_id', $id)->count();
            $added['brands'] = Brand::withoutGlobalScopes()->where('school_id', $id)->count();
        }

        if ($full && ! Product::withoutGlobalScopes()->where('school_id', $id)->exists()) {
            $added['products'] = $this->starterProducts($id, BusinessKinds::of($school));
        }

        return $added;
    }

    private function starterProducts(int $schoolId, string $kind): int
    {
        $types = ProductType::withoutGlobalScopes()->where('school_id', $schoolId)->pluck('id', 'name');
        $categories = Category::withoutGlobalScopes()->where('school_id', $schoolId)->pluck('id', 'name');
        $count = 0;

        foreach (self::STARTER_PRODUCTS[$kind] ?? self::STARTER_PRODUCTS['general'] as [$type, $category, $brandName, $modelName]) {
            $brand = Brand::withoutGlobalScopes()->firstOrCreate(['school_id' => $schoolId, 'type_id' => $types[$type] ?? null, 'name' => $brandName]);
            $model = BrandModel::withoutGlobalScopes()->firstOrCreate(['school_id' => $schoolId, 'brand_id' => $brand->id, 'name' => $modelName]);

            $product = Product::withoutGlobalScopes()->firstOrCreate(
                ['school_id' => $schoolId, 'brand_id' => $brand->id, 'model_id' => $model->id],
                ['type_id' => $types[$type] ?? null, 'category_id' => $categories[$category] ?? null, 'brand' => $brand->name, 'name' => $modelName,
                    'sale_price' => 0, 'wholesale_price' => 0, 'notes' => 'Starter product: set its price, or delete it if you do not sell it.'],
            );
            $count += (int) $product->wasRecentlyCreated;
        }

        return $count;
    }
}
