<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
#[UseModel(Category::class)]
class CategoryFactory extends Factory
{
    /**
     * A top-level, active category without an image.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'image' => null,
            'sort_order' => 0,
            'is_active' => true,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function childOf(?Category $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Category::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function withImage(string $path = 'categories/example.png'): static
    {
        return $this->state(fn (array $attributes) => ['image' => $path]);
    }
}
