<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Nischoyota category tree: honey, ghee & oils, natural foods, clothing and ornamental plants. Icons are copied from public/images.
 */
class CategorySeeder extends Seeder
{
    /**
     * @var array<string, array{icon: string, children: array<string, string>}>
     */
    private const TREE = [
        'Honey' => ['icon' => 'honey.svg', 'children' => [
            'Flower Honey' => 'flower-honey.svg', 'Sundarban Honey' => 'sundarban.svg', 'Black Seed Honey' => 'black-seed.svg',
        ]],
        'Ghee & Oils' => ['icon' => 'ghee.svg', 'children' => [
            'Pure Ghee' => 'ghee.svg', 'Mustard Oil' => 'mustard-oil.svg', 'Black Seed Oil' => 'black-seed.svg',
        ]],
        'Natural Foods' => ['icon' => 'natural-foods.svg', 'children' => [
            'Dates & Dry Fruits' => 'dates.svg',
        ]],
        'Clothing' => ['icon' => 'clothing.svg', 'children' => [
            'Panjabi' => 'panjabi.svg', 'T-Shirts' => 'tshirt.svg', 'Shirts' => 'shirt.svg',
        ]],
        'Plants' => ['icon' => 'plant.svg', 'children' => [
            'Indoor Plants' => 'plant.svg',
        ]],
    ];

    public function run(): void
    {
        $departmentOrder = 0;

        foreach (self::TREE as $departmentName => $department) {
            if ($departmentName === 'Clothing' && ! config('shop.clothing_enabled')) {
                continue;
            }

            $parent = $this->createCategory($departmentName, $department['icon'], $departmentOrder++);

            $childOrder = 0;

            foreach ($department['children'] as $childName => $icon) {
                $this->createCategory($childName, $icon, $childOrder++, $parent);
            }
        }
    }

    private function createCategory(string $name, string $icon, int $sortOrder, ?Category $parent = null): Category
    {
        $slug = Str::slug($name);

        return Category::query()->create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'name_bn' => (require database_path('data/catalog_bn.php'))['categories'][$slug] ?? null,
            'slug' => $slug,
            'image' => $this->copyIcon($icon, $slug),
            'sort_order' => $sortOrder,
            'is_active' => true,
        ]);
    }

    private function copyIcon(string $icon, string $slug): string
    {
        $path = "categories/{$slug}.svg";

        Storage::disk('public')->put($path, file_get_contents(public_path("images/{$icon}")));

        return $path;
    }
}
