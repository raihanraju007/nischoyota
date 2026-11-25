<?php

namespace Database\Factories;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
#[UseModel(OrderItem::class)]
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_variant_id' => null,
            'product_name' => fake()->words(3, true),
            'variant_label' => null,
            'sku' => fake()->bothify('NS-??-####'),
            'unit_price' => 50000,
            'quantity' => 2,
            'line_total' => 100000,
            'is_preorder' => false,
        ];
    }
}
