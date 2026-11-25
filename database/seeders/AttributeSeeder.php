<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Product options and shop filters: pack weight / volume for foods, colour and size for clothing.
 */
class AttributeSeeder extends Seeder
{
    /**
     * @var array<string, array<string, string|null>> attribute => [value => swatch hex]
     */
    private const ATTRIBUTES = [
        'Color' => [
            'White' => '#FFFFFF', 'Dark Navy' => '#1D2733', 'Sky Blue' => '#87CEEB', 'Cream' => '#FFFDD0',
            'Maroon' => '#800000', 'Red' => '#DC2626', 'Blue' => '#2563EB', 'Green' => '#2F6B3A', 'Amber' => '#D4921F', 'Olive' => '#6B7A3A', 'Black' => '#1F1F1F',
        ],
        'Size' => ['S' => null, 'M' => null, 'L' => null, 'XL' => null, 'XXL' => null],
        'Weight' => ['250g' => null, '500g' => null, '1kg' => null, '2kg' => null],
        'Volume' => ['500ml' => null, '1L' => null, '2L' => null],
    ];

    public function run(): void
    {
        foreach (self::ATTRIBUTES as $name => $values) {
            $attribute = ProductAttribute::query()->create(['name' => $name, 'slug' => Str::slug($name)]);
            $sortOrder = 0;

            foreach ($values as $value => $colorHex) {
                $attribute->values()->create(['value' => $value, 'color_hex' => $colorHex, 'sort_order' => $sortOrder++]);
            }
        }
    }
}
