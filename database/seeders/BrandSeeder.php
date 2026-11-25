<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Nischoyota's own product lines.
 */
class BrandSeeder extends Seeder
{
    private const BRANDS = ['Nischoyota', 'Nischoyota Naturals', 'Nischoyota Fashion', 'Nischoyota Garden'];

    public function run(): void
    {
        foreach (self::BRANDS as $name) {
            if ($name === 'Nischoyota Fashion' && ! config('shop.clothing_enabled')) {
                continue;
            }

            $slug = Str::slug($name);

            Brand::query()->create([
                'name' => $name,
                'name_bn' => (require database_path('data/catalog_bn.php'))['brands'][$slug] ?? null,
                'slug' => $slug,
                'is_active' => true,
            ]);
        }
    }
}
