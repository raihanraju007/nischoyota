<?php

namespace Database\Factories;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopping\Models\Cart;
use App\Modules\Shopping\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
#[UseModel(CartItem::class)]
class CartItemFactory extends Factory
{
    /**
     * added_price follows the variant's price unless given.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'quantity' => 1,
            'added_price' => fn (array $attributes): int => ProductVariant::query()->findOrFail($attributes['product_variant_id'])->price,
        ];
    }
}
