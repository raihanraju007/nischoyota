<?php

namespace Database\Factories;

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
#[UseModel(Cart::class)]
class CartFactory extends Factory
{
    /**
     * A guest cart by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'guest_token' => Str::random(64),
            'coupon_code' => null,
        ];
    }

    public function forGuest(string $guestToken): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => null, 'guest_token' => $guestToken]);
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => ['user_id' => $user ?? User::factory(), 'guest_token' => null]);
    }
}
