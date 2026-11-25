<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttributeValue>
 */
#[UseModel(AttributeValue::class)]
class AttributeValueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attribute_id' => ProductAttribute::factory(),
            'value' => fake()->unique()->bothify('Value ??##'),
            'color_hex' => null,
            'sort_order' => 0,
        ];
    }

    public function withColor(string $hex = '#1160A8'): static
    {
        return $this->state(fn (array $attributes) => ['color_hex' => $hex]);
    }
}
