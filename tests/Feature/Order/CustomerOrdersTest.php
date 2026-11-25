<?php

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Models\Order;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Shopping\Models\CartItem;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $zone = DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 6000]);
    $this->upazila = Upazila::factory()->for(District::factory()->state(['delivery_zone_id' => $zone->id]))->create(['name' => 'Dhanmondi']);
    $this->user = User::factory()->customer()->create(['phone' => '01712345678']);
});

it('lists the customer\'s orders newest first, with a status filter and pagination', function () {
    $variant = sellableVariant(['stock_quantity' => 100]);
    $first = placedOrder($this->user, [$variant->id => 2]);
    $second = placedOrder($this->user, [$variant->id => 1]);
    $second->update(['status' => OrderStatus::Shipped]);
    Order::factory()->create(['order_number' => 'ORD-99999']);

    $this->actingAs($this->user)->get(route('account.orders'))
        ->assertOk()
        ->assertSeeInOrder([$second->order_number, $first->order_number])
        ->assertSee('2 items')
        ->assertDontSee('ORD-99999');

    $this->get(route('account.orders', ['status' => 'shipped']))
        ->assertSee($second->order_number)
        ->assertDontSee($first->order_number.'<', escape: false);

    Order::factory()->count(10)->create(['user_id' => $this->user->id]);
    $this->get(route('account.orders'))->assertSee('page=2', escape: false);
});

it('shows the empty state without orders', function () {
    $this->actingAs($this->user)->get(route('account.orders'))->assertSee('No orders yet');
});

it('shows an order with its timeline and returns 404 for someone else\'s', function () {
    $variant = sellableVariant(['stock_quantity' => 10], ['name' => 'Ceiling Fan']);
    $order = placedOrder($this->user, [$variant->id => 1]);
    $other = Order::factory()->create();

    $this->actingAs($this->user)->get(route('account.orders.show', $order->order_number))
        ->assertOk()
        ->assertSee('Ceiling Fan')
        ->assertSee('Dhanmondi')
        ->assertSeeInOrder(['Order Placed', 'Confirmed', 'Processing', 'Shipped', 'Delivered'])
        ->assertSee('Estimated')
        ->assertSee('Cancel Order');

    $this->get(route('account.orders.show', $other->order_number))->assertNotFound();
    $this->post(route('account.orders.cancel', $other->order_number))->assertNotFound();
    $this->get(route('account.orders.show', $order->order_number))->assertOk();
});

it('cancels a pending order: stock back, coupon released, history, event', function () {
    Event::fake([OrderCancelled::class]);
    $coupon = Coupon::factory()->create(['code' => 'SAVE120', 'value' => 12000]);
    $variant = sellableVariant(['price' => 50000, 'stock_quantity' => 10]);
    $order = placedOrder($this->user, [$variant->id => 3], 'SAVE120');
    expect($variant->fresh()->stock_quantity)->toBe(7)->and($coupon->fresh()->used_count)->toBe(1);

    $this->actingAs($this->user)->post(route('account.orders.cancel', $order->order_number))
        ->assertRedirect(route('account.orders.show', $order->order_number))
        ->assertSessionHas('success', 'Your order has been cancelled.');

    $order->refresh()->load(['statusHistory', 'payments']);
    expect($order->status)->toBe(OrderStatus::Cancelled)
        ->and($order->payment_status->value)->toBe('cancelled')
        ->and($order->payments->sole()->status->value)->toBe('cancelled')
        ->and($order->statusHistory->last()->only(['user_id', 'note']))->toBe(['user_id' => $this->user->id, 'note' => 'Cancelled by customer'])
        ->and($variant->fresh()->stock_quantity)->toBe(10)
        ->and(StockMovement::query()->where('reason', 'order_cancelled')->sole()->only(['quantity_change', 'order_id']))->toBe(['quantity_change' => 3, 'order_id' => $order->id])
        ->and($coupon->fresh()->used_count)->toBe(0);
    Event::assertDispatched(OrderCancelled::class);

    $this->get(route('account.orders.show', $order->order_number))
        ->assertSeeInOrder(['Order Placed', 'Cancelled'])
        ->assertDontSee('Cancel Order');
});

it('gives back only the stock a pre-order took', function () {
    $variant = sellableVariant(['price' => 10000, 'stock_quantity' => 1], ['allow_preorder' => true]);
    $order = placedOrder($this->user, [$variant->id => 4]);
    expect($variant->fresh()->stock_quantity)->toBe(0);

    $this->actingAs($this->user)->post(route('account.orders.cancel', $order->order_number));

    expect($variant->fresh()->stock_quantity)->toBe(1);
});

it('refuses to cancel once the order is being prepared, and a second time', function (OrderStatus $status, string $message) {
    $variant = sellableVariant(['stock_quantity' => 10]);
    $order = placedOrder($this->user, [$variant->id => 2]);
    $order->update(['status' => $status]);

    $this->actingAs($this->user)->post(route('account.orders.cancel', $order->order_number))
        ->assertSessionHas('error', $message);

    expect($order->fresh()->status)->toBe($status)
        ->and($variant->fresh()->stock_quantity)->toBe(8);
})->with([
    [OrderStatus::Processing, 'This order is already being prepared and can no longer be cancelled. Please call us.'],
    [OrderStatus::Shipped, 'This order is already being prepared and can no longer be cancelled. Please call us.'],
    [OrderStatus::Delivered, 'This order is already being prepared and can no longer be cancelled. Please call us.'],
    [OrderStatus::Cancelled, 'This order is already cancelled.'],
]);

it('lets a confirmed order be cancelled', function () {
    $order = placedOrder($this->user, [sellableVariant()->id => 1]);
    $order->update(['status' => OrderStatus::Confirmed]);

    $this->actingAs($this->user)->post(route('account.orders.cancel', $order->order_number))->assertSessionHas('success');
});

it('puts the items back in the cart and names the ones that cannot be bought', function () {
    $fan = sellableVariant(['stock_quantity' => 10], ['name' => 'Ceiling Fan']);
    $lamp = sellableVariant(['stock_quantity' => 10], ['name' => 'Desk Lamp']);
    $order = placedOrder($this->user, [$fan->id => 2, $lamp->id => 1]);

    $this->actingAs($this->user)->post(route('account.orders.buy-again', $order->order_number))
        ->assertRedirect(route('cart'))
        ->assertSessionHas('success', 'The items are in your cart.');
    expect(CartItem::query()->pluck('quantity', 'product_variant_id')->all())->toEqualCanonicalizing([$fan->id => 2, $lamp->id => 1]);

    CartItem::query()->delete();
    $lamp->update(['stock_quantity' => 0]);
    $this->post(route('account.orders.buy-again', $order->order_number))
        ->assertSessionHas('info', 'Added to your cart. Not available any more: Desk Lamp.');

    ProductVariant::query()->whereKey($fan->id)->delete();
    CartItem::query()->delete();
    $this->from(route('account.orders.show', $order->order_number))
        ->post(route('account.orders.buy-again', $order->order_number))
        ->assertSessionHas('error', 'These items can no longer be bought: Ceiling Fan, Desk Lamp.');
});

it('shows recent orders on the account dashboard', function () {
    $order = placedOrder($this->user, [sellableVariant()->id => 1]);

    $this->actingAs($this->user)->get(route('account.dashboard'))
        ->assertSee('Recent Orders')
        ->assertSee($order->order_number);
});

it('does not count a cancelled order towards the per-customer coupon limit', function () {
    Coupon::factory()->create(['code' => 'ONCE', 'value' => 12000, 'usage_limit_per_user' => 1]);
    $variant = sellableVariant(['price' => 50000, 'stock_quantity' => 10]);
    $order = placedOrder($this->user, [$variant->id => 1], 'ONCE');
    $this->actingAs($this->user)->post(route('account.orders.cancel', $order->order_number));

    $again = placedOrder($this->user, [$variant->id => 1], 'ONCE');

    expect($again->coupon_code)->toBe('ONCE');
});
