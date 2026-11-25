<?php

namespace Database\Factories;

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Review\Enums\ReviewStatus;
use App\Modules\Review\Models\Review;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
#[UseModel(Review::class)]
class ReviewFactory extends Factory
{
    /**
     * A pending 4-star review by a new customer.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory()->customer(),
            'rating' => 4,
            'comment' => fake()->sentence(12),
            'is_verified_purchase' => false,
            'status' => ReviewStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReviewStatus::Approved, 'moderated_at' => now()]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ReviewStatus::Rejected, 'moderated_at' => now()]);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => ['is_verified_purchase' => true]);
    }
}
