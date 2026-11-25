<?php

namespace Database\Factories;

use App\Modules\Location\Models\Division;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
#[UseModel(Division::class)]
class DivisionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['name' => fake()->unique()->city(), 'name_bn' => 'বিভাগ'];
    }
}
