<?php

namespace App\Support;

use App\Models\Biz\Brand;
use App\Models\Biz\Category;
use App\Models\Biz\ProductType;
use App\Models\School;
use App\Services\Biz\StarterCatalog;
use Illuminate\Database\Eloquent\Builder;

/**
 * The kinds of business the product serves. Each gets its own product types, categories and brands,
 * and a dashboard and menu that put its everyday work first: counter sales for a mobile shop,
 * installments for a motorcycle dealer.
 */
class BusinessKinds
{
    /**
     * key => [label, icon, one line, product types, categories, focus (sales | installments)]
     * A null list of types or categories means all of them.
     */
    public const KINDS = [
        'mobile' => ['Mobile phone shop', 'device', 'Phones by IMEI, accessories, retail and wholesale', ['Mobile phone', 'Accessory / general'],
            ['Mobile phones', 'Mobile accessories', 'Chargers and cables', 'Covers and protectors', 'Earphones and audio', 'Power banks', 'Other'], 'sales'],
        'motorcycle' => ['Motorcycle dealer', 'bus', 'Bikes on installments, engine and chassis numbers, parts', ['Motorcycle', 'Accessory / general'],
            ['Motorcycles', 'Motorcycle parts', 'Other'], 'installments'],
        'electronics' => ['Electronics and appliances', 'sun', 'Fridges, ACs, LEDs, washing machines, cash or on installments', ['Electronics / appliance', 'Accessory / general'],
            ['Home appliances', 'Electronics', 'Other'], 'installments'],
        'furniture' => ['Furniture', 'layers', 'Beds, sofas and more, cash or on installments', ['Furniture', 'Accessory / general'],
            ['Furniture', 'Other'], 'installments'],
        'solar' => ['Solar systems', 'sun', 'Panels, inverters and batteries, on installments', ['Solar system', 'Accessory / general'],
            ['Solar', 'Other'], 'installments'],
        'car' => ['Car dealer', 'briefcase', 'Cars on installments, engine and chassis numbers', ['Car', 'Accessory / general'],
            ['Cars', 'Other'], 'installments'],
        'general' => ['General / many products', 'package', 'A bit of everything: phones, bikes, appliances', null, null, 'installments'],
    ];

    public static function of(?School $school): string
    {
        $kind = $school?->business_kind;

        return isset(self::KINDS[$kind]) ? $kind : 'general';
    }

    public static function label(?string $kind): string
    {
        return self::KINDS[$kind ?? 'general'][0] ?? self::KINDS['general'][0];
    }

    /** @return array<int, string>|null product type names, or null for all */
    public static function types(string $kind): ?array
    {
        return self::KINDS[$kind][3] ?? null;
    }

    /** @return array<int, string>|null category names, or null for all */
    public static function categories(string $kind): ?array
    {
        return self::KINDS[$kind][4] ?? null;
    }

    public static function focus(string $kind): string
    {
        return self::KINDS[$kind][5] ?? 'installments';
    }

    /**
     * Product types this business sees: its kind's ready-made ones, any it made itself, and any in use.
     *
     * @return Builder<ProductType>
     */
    public static function typesQuery(School $school)
    {
        $only = self::types(self::of($school));

        return ProductType::query()->when($only !== null, fn ($q) => $q->where(fn ($q) => $q->whereIn('name', $only)
            ->orWhereNotIn('name', array_keys(ProductType::STARTERS))->orWhereHas('products')));
    }

    /** @return Builder<Category> */
    public static function categoriesQuery(School $school)
    {
        $only = self::categories(self::of($school));

        return Category::query()->when($only !== null, fn ($q) => $q->where(fn ($q) => $q->whereIn('name', $only)
            ->orWhereNotIn('name', StarterCatalog::CATEGORIES)->orWhereHas('products')));
    }

    /** Brands of the types this business sees, plus brands for any type. */
    public static function brandsQuery(School $school)
    {
        if (self::types(self::of($school)) === null) {
            return Brand::query();
        }
        $typeIds = self::typesQuery($school)->pluck('id');

        return Brand::query()->where(fn ($q) => $q->whereIn('type_id', $typeIds)->orWhereNull('type_id')->orWhereHas('products'));
    }
}
