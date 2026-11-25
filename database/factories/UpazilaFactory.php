<?php

namespace Database\Factories;

use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Upazila>
 */
#[UseModel(Upazila::class)]
class UpazilaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['district_id' => District::factory(), 'name' => fake()->unique()->city(), 'name_bn' => 'উপজেলা', 'delivery_zone_id' => null];
    }
}
