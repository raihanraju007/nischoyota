<?php

namespace Database\Factories;

use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
#[UseModel(District::class)]
class DistrictFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['division_id' => Division::factory(), 'name' => fake()->unique()->city(), 'name_bn' => 'জেলা', 'delivery_zone_id' => DeliveryZone::factory()];
    }
}
