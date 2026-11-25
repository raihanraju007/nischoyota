<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
#[UseModel(Brand::class)]
class BrandFactory extends Factory
{
    /**
     * An active brand without a logo.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'logo' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function withLogo(string $path = 'brands/example.png'): static
    {
        return $this->state(fn (array $attributes) => ['logo' => $path]);
    }
}
