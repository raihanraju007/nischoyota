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
            Brand::query()->create(['name' => $name, 'slug' => Str::slug($name), 'is_active' => true]);
        }
    }
}
