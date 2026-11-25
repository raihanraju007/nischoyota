<?php

namespace Database\Factories;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
#[UseModel(Product::class)]
class ProductFactory extends Factory
{
    /**
     * A draft product. Use withDefaultVariant() for one that has price, SKU and stock.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'category_id' => Category::factory(),
            'brand_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'specifications' => null,
            'status' => ProductStatus::Draft,
            'is_featured' => false,
            'allow_preorder' => false,
            'min_price' => 0,
            'max_discount_percent' => 0,
            'published_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::Active,
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ProductStatus::Archived]);
    }

    /**
     * @param  array<string, mixed>  $variantAttributes
     */
    public function withDefaultVariant(array $variantAttributes = []): static
    {
        return $this->afterCreating(function (Product $product) use ($variantAttributes): void {
            $variant = ProductVariant::factory()->for($product)->create(['is_default' => true, ...$variantAttributes]);

            $product->forceFill(['min_price' => $variant->price])->save();
        });
    }
}
