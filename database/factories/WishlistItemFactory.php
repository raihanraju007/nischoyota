<?php

namespace Database\Factories;

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Shopping\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WishlistItem>
 */
#[UseModel(WishlistItem::class)]
class WishlistItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
        ];
    }
}
