<?php

namespace App\Services\Biz;

use App\Models\Biz\Brand;
use App\Models\Biz\BrandModel;
use App\Models\Biz\Category;
use App\Models\Biz\ProductType;
use App\Support\BusinessKinds;

/**
 * What a new business starts with: categories, product types and the usual brands and models
 * sold in Pakistan. All of it can be edited, and more added, from Products and stock.
 * Running it again only adds what is missing.
 */
class StarterCatalog
{
    public const CATEGORIES = [
        'Mobile phones', 'Mobile accessories', 'Chargers and cables', 'Covers and protectors', 'Earphones and audio', 'Power banks',
        'Motorcycles', 'Motorcycle parts', 'Home appliances', 'Electronics', 'Furniture', 'Solar', 'Cars', 'Other',
    ];

    /** type => brand => models */
    public const BRANDS = [
        'Mobile phone' => [
            'Samsung' => ['Galaxy A05', 'Galaxy A06', 'Galaxy A15', 'Galaxy A16', 'Galaxy A25', 'Galaxy A35', 'Galaxy A55', 'Galaxy S24', 'Galaxy S24 Ultra', 'Galaxy S25'],
            'Apple' => ['iPhone 11', 'iPhone 12', 'iPhone 13', 'iPhone 14', 'iPhone 15', 'iPhone 15 Pro', 'iPhone 15 Pro Max', 'iPhone 16', 'iPhone 16 Pro Max'],
            'Infinix' => ['Smart 8', 'Smart 9', 'Hot 40', 'Hot 40i', 'Hot 50', 'Note 40', 'Note 40 Pro', 'Zero 40'],
            'Tecno' => ['Pop 8', 'Pop 9', 'Spark 20', 'Spark 20C', 'Spark 30', 'Camon 30', 'Pova 6'],
            'Vivo' => ['Y03', 'Y18', 'Y19s', 'Y28', 'Y29', 'V30', 'V40'],
            'Oppo' => ['A3x', 'A18', 'A38', 'A60', 'Reno 12', 'Reno 12F'],
            'Xiaomi' => ['Redmi A3', 'Redmi 13C', 'Redmi 14C', 'Redmi Note 13', 'Redmi Note 14', 'Poco X6'],
            'Realme' => ['C53', 'C61', 'C63', 'C65', 'Note 60', 'Realme 12'],
            'Nokia' => ['105', '106', '110', '130'],
            'itel' => ['A50', 'A70', 'S24', 'P55'],
            'Huawei' => ['Nova 12', 'Nova Y72'],
            'Honor' => ['X6b', 'X7b', 'X8b', '200'],
            'OnePlus' => ['Nord CE4', '12R', '12'],
            'Google' => ['Pixel 7', 'Pixel 8', 'Pixel 9'],
        ],
        'Motorcycle' => [
            'Honda' => ['CD 70', 'CD 70 Dream', 'Pridor', 'CG 125', 'CG 125 Special', 'CB 125F', 'CB 150F'],
            'Yamaha' => ['YB 125Z', 'YB 125Z-DX', 'YBR 125', 'YBR 125G'],
            'Suzuki' => ['GD 110S', 'GS 150', 'GS 150 SE', 'GR 150', 'GSX 125'],
            'United' => ['US 70', 'US 100', 'US 125'],
            'Road Prince' => ['RP 70', 'RP 110', 'Wego 150', 'Robinson 150'],
            'Unique' => ['UD 70', 'UD 100', 'UD 125'],
            'Super Power' => ['SP 70', 'SP 100', 'Archi 150'],
            'Hi-Speed' => ['SR 70', 'Infinity 150', 'Alpha 100'],
            'Crown' => ['CR 70', 'CR 125'],
            'Metro' => ['MR 70', 'E8S electric'],
        ],
        'Electronics / appliance' => [
            'Haier' => ['Refrigerator', 'Deep freezer', 'Inverter AC', 'LED TV', 'Washing machine', 'Microwave'],
            'Dawlance' => ['Refrigerator', 'Deep freezer', 'Inverter AC', 'Washing machine', 'Microwave', 'Water dispenser'],
            'PEL' => ['Refrigerator', 'Deep freezer', 'Inverter AC', 'Washing machine'],
            'Orient' => ['Refrigerator', 'Inverter AC', 'LED TV', 'Water dispenser'],
            'Gree' => ['Inverter AC 1 ton', 'Inverter AC 1.5 ton', 'Inverter AC 2 ton'],
            'Kenwood' => ['Inverter AC', 'Refrigerator', 'Microwave'],
            'TCL' => ['LED TV 32"', 'LED TV 43"', 'LED TV 55"', 'Inverter AC'],
            'Samsung' => ['LED TV', 'Refrigerator', 'Washing machine', 'Microwave'],
            'LG' => ['LED TV', 'Refrigerator', 'Washing machine'],
            'Changhong Ruba' => ['LED TV', 'Inverter AC'],
            'Super Asia' => ['Washing machine', 'Room cooler', 'Water dispenser'],
            'Boss' => ['Washing machine', 'Room cooler'],
            'Pak Fan' => ['Ceiling fan', 'Pedestal fan', 'Bracket fan'],
            'GFC' => ['Ceiling fan', 'Pedestal fan', 'Exhaust fan'],
            'Royal' => ['Ceiling fan', 'Pedestal fan'],
        ],
        'Solar system' => [
            'Longi' => ['Hi-MO 6 580W', 'Hi-MO 7 610W'],
            'Jinko' => ['Tiger Neo 585W', 'Tiger Neo 620W'],
            'Canadian Solar' => ['HiKu 550W', 'TOPHiKu 610W'],
            'JA Solar' => ['DeepBlue 4.0 585W'],
            'Inverex' => ['Nitrox 6kW', 'Veyron 4kW', 'Yukon 3kW'],
            'Growatt' => ['SPF 6000 ES', 'MIN 5000'],
            'Fronius' => ['Primo 5kW', 'Symo 10kW'],
            'Tesla' => ['Hybrid 6kW'],
        ],
        'Car' => [
            'Suzuki' => ['Alto', 'Cultus', 'Wagon R', 'Swift', 'Every', 'Bolan'],
            'Toyota' => ['Corolla', 'Yaris', 'Fortuner', 'Hilux'],
            'Honda' => ['City', 'Civic', 'BR-V', 'HR-V'],
            'KIA' => ['Picanto', 'Sportage', 'Stonic'],
            'Hyundai' => ['Elantra', 'Tucson', 'Sonata'],
            'Changan' => ['Alsvin', 'Oshan X7', 'Karvaan'],
            'MG' => ['HS', 'ZS EV'],
        ],
        'Furniture' => [
            'Interwood' => ['Bed set', 'Sofa set', 'Dining table', 'Wardrobe'],
            'Habitt' => ['Sofa', 'Chair', 'Table'],
        ],
        'Accessory / general' => [
            'Anker' => ['Charger 20W', 'Power bank 10000mAh', 'USB-C cable'],
            'Baseus' => ['Charger 25W', 'Power bank 20000mAh', 'Earbuds'],
            'Audionic' => ['Earbuds', 'Bluetooth speaker', 'Handsfree'],
            'Faster' => ['Charger', 'Data cable', 'Power bank'],
            'Ronin' => ['Earbuds', 'Handsfree', 'Charger'],
            'Samsung' => ['Original charger 25W', 'Original cable'],
            'Apple' => ['20W adapter', 'Lightning cable', 'AirPods'],
        ],
    ];

    /** Adds what a kind of business needs (see BusinessKinds); with no kind, everything. */
    public static function seed(int $schoolId, ?string $kind = null): void
    {
        $onlyTypes = $kind ? BusinessKinds::types($kind) : null;
        $onlyCategories = $kind ? BusinessKinds::categories($kind) : null;

        ProductType::seedStarters($schoolId, $onlyTypes);
        $types = ProductType::withoutGlobalScopes()->where('school_id', $schoolId)->pluck('id', 'name');

        foreach (self::CATEGORIES as $name) {
            if ($onlyCategories === null || in_array($name, $onlyCategories, true)) {
                Category::withoutGlobalScopes()->firstOrCreate(['school_id' => $schoolId, 'name' => $name]);
            }
        }

        foreach (self::BRANDS as $type => $brands) {
            if ($onlyTypes !== null && ! in_array($type, $onlyTypes, true)) {
                continue;
            }
            foreach ($brands as $brandName => $models) {
                $brand = Brand::withoutGlobalScopes()->firstOrCreate(['school_id' => $schoolId, 'type_id' => $types[$type] ?? null, 'name' => $brandName]);
                foreach ($models as $model) {
                    BrandModel::withoutGlobalScopes()->firstOrCreate(['school_id' => $schoolId, 'brand_id' => $brand->id, 'name' => $model]);
                }
            }
        }
    }
}
