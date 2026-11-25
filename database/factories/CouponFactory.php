<?php

namespace Database\Factories;

use App\Modules\Promotion\Enums\CouponType;
use App\Modules\Promotion\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
#[UseModel(Coupon::class)]
class CouponFactory extends Factory
{
    /**
     * An active ৳120-off coupon without limits.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('CODE##??')),
            'type' => CouponType::Fixed,
            'value' => 12000,
            'min_order_amount' => null,
            'max_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_user' => null,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function percent(int $percent = 10, ?int $maxDiscount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CouponType::Percent,
            'value' => $percent,
            'max_discount_amount' => $maxDiscount,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
