<?php

namespace Database\Factories;

use App\Modules\Location\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
#[UseModel(DeliveryZone::class)]
class DeliveryZoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['name' => fake()->unique()->city().' Zone', 'charge' => 6000, 'min_days' => 1, 'max_days' => 2, 'is_active' => true, 'sort_order' => 0];
    }
}
