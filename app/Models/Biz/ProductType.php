<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A kind of product and the details each of its pieces carries, e.g. Motorcycle: engine no., chassis no., colour. */
class ProductType extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_product_types';

    protected $guarded = ['id'];

    protected $casts = ['fields' => 'array'];

    /** Ready-made types offered to a new business. */
    public const STARTERS = [
        'Motorcycle' => ['Engine no.', 'Chassis no.', 'Colour', 'Model year', 'Registration no.'],
        'Mobile phone' => ['IMEI', 'Second IMEI', 'Colour', 'Storage'],
        'Electronics / appliance' => ['Serial no.', 'Model', 'Colour', 'Warranty'],
        'Furniture' => ['Material', 'Colour', 'Size'],
        'Solar system' => ['Panels', 'Inverter serial', 'Batteries', 'Capacity (kW)'],
        'Car' => ['Engine no.', 'Chassis no.', 'Colour', 'Model year', 'Registration no.'],
        'Accessory / general' => [],
    ];

    /** @param  array<int, string>|null  $only  the names to add, or null for all */
    public static function seedStarters(int $schoolId, ?array $only = null): void
    {
        foreach (self::STARTERS as $name => $labels) {
            if ($only !== null && ! in_array($name, $only, true)) {
                continue;
            }
            self::withoutGlobalScopes()->firstOrCreate(['school_id' => $schoolId, 'name' => $name], ['fields' => self::fieldsFrom($labels)]);
        }
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'type_id');
    }

    /** @param  array<int, string>  $labels */
    public static function fieldsFrom(array $labels): array
    {
        return collect($labels)->map(fn ($l) => trim((string) $l))->filter()->unique()->take(12)
            ->map(fn ($l) => ['key' => Str::slug($l, '_') ?: 'field_'.md5($l), 'label' => $l])->values()->all();
    }
}
