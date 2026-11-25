<?php

use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;

beforeEach(function () {
    $this->order = Order::factory()->create(['customer_phone' => '01812345678', 'customer_name' => 'Karim Mia', 'status' => OrderStatus::Shipped]);
    OrderItem::factory()->for($this->order)->create(['product_name' => 'Electric Kettle']);
    $this->order->statusHistory()->create(['to_status' => OrderStatus::Pending, 'created_at' => now()->subDays(2)]);
    $this->order->statusHistory()->create(['from_status' => OrderStatus::Pending, 'to_status' => OrderStatus::Shipped, 'created_at' => now()]);
});

it('shows the tracking form', function () {
    $this->get(route('orders.track'))->assertOk()->assertSee('Track Your Order')->assertDontSee('Order Status —');
});

it('finds an order by number and phone, whatever the format', function () {
    $this->post(route('orders.track.lookup'), ['order_number' => '#'.strtolower($this->order->order_number), 'phone' => '+880 1812-345678'])
        ->assertOk()
        ->assertSee('Order Status — #'.$this->order->order_number)
        ->assertSee('Electric Kettle')
        ->assertSee('Karim Mia')
        ->assertSeeInOrder(['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Delivered']);
});

it('does not reveal an order when the phone does not match', function () {
    $this->from(route('orders.track'))
        ->post(route('orders.track.lookup'), ['order_number' => $this->order->order_number, 'phone' => '01712345678'])
        ->assertRedirect(route('orders.track'))
        ->assertSessionHasErrors(['order_number' => "We couldn't find an order with that number and phone."]);
});

it('validates the tracking form', function () {
    $this->post(route('orders.track.lookup'), ['order_number' => '', 'phone' => '123'])->assertSessionHasErrors(['order_number', 'phone']);
});
