<?php

namespace Database\Factories;

use App\Modules\Location\Models\Upazila;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
#[UseModel(Order::class)]
class OrderFactory extends Factory
{
    /**
     * A pending guest COD order of ৳1,000 + ৳60 delivery. The address comes from a new upazila.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'status' => OrderStatus::Pending,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('017########'),
            'customer_email' => null,
            'upazila_id' => Upazila::factory(),
            'upazila_name' => fn (array $attributes): string => Upazila::query()->findOrFail($attributes['upazila_id'])->name,
            'district_id' => fn (array $attributes): int => Upazila::query()->findOrFail($attributes['upazila_id'])->district_id,
            'district_name' => fn (array $attributes): string => Upazila::query()->findOrFail($attributes['upazila_id'])->district->name,
            'division_id' => fn (array $attributes): int => Upazila::query()->findOrFail($attributes['upazila_id'])->district->division_id,
            'division_name' => fn (array $attributes): string => Upazila::query()->findOrFail($attributes['upazila_id'])->district->division->name,
            'area' => null,
            'address_line' => 'House '.fake()->numberBetween(1, 99).', Road '.fake()->numberBetween(1, 30),
            'postal_code' => null,
            'delivery_zone_id' => null,
            'delivery_zone_name' => 'Inside Dhaka',
            'delivery_min_days' => 1,
            'delivery_max_days' => 2,
            'subtotal' => 100000,
            'discount_total' => 0,
            'delivery_charge' => 6000,
            'total' => 106000,
            'coupon_id' => null,
            'coupon_code' => null,
            'payment_method' => PaymentMethod::CashOnDelivery,
            'payment_status' => PaymentStatus::Pending,
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function confirmed(): static
    {
        return $this->status(OrderStatus::Confirmed);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Shipped, 'courier_name' => 'Pathao']);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Delivered, 'courier_name' => 'Pathao', 'delivered_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Cancelled, 'payment_status' => PaymentStatus::Cancelled, 'cancelled_at' => now()]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['payment_status' => PaymentStatus::Paid]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Order $order): void {
            if ($order->order_number === null) {
                $order->update(['order_number' => Order::numberFor($order->id)]);
            }
        });
    }
}
