<?php

namespace Database\Factories;

use App\Modules\Account\Enums\AddressLabel;
use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Location\Models\Upazila;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
#[UseModel(Address::class)]
class AddressFactory extends Factory
{
    /**
     * Uses an upazila with its district and division so the location chain is consistent.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => AddressLabel::Home,
            'recipient_name' => fake()->name(),
            'phone' => fake()->numerify('017########'),
            'area' => null,
            'address_line' => 'House '.fake()->numberBetween(1, 99).', Road '.fake()->numberBetween(1, 30),
            'postal_code' => '1205',
            'is_default' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Address $address): void {
            if ($address->upazila_id !== null) {
                return;
            }

            $upazila = Upazila::factory()->create();
            $address->upazila_id = $upazila->id;
            $address->district_id = $upazila->district_id;
            $address->division_id = $upazila->district->division_id;
        });
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => ['is_default' => true]);
    }
}
