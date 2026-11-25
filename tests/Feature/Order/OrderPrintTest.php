<?php

use App\Modules\Account\Models\User;
use App\Modules\Content\Data\StoreSettings;
use App\Modules\Content\Services\SettingService;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;

beforeEach(function () {
    app(SettingService::class)->save(new StoreSettings(
        storeName: 'Nischoyota',
        supportPhone: '09612-000000',
        supportEmail: 'support@nischoyota.com',
        storeAddress: 'Dhanmondi, Dhaka',
        supportHours: null,
        freeDeliveryThreshold: null,
        lowStockThreshold: 5,
    ));
    $this->staff = User::factory()->staff()->create();
});

it('prints a packing slip with sender, receiver, COD amount, items and weight', function () {
    $variant = sellableVariant(['weight_grams' => 1500]);
    $order = Order::factory()->create(['customer_name' => 'Rahim Uddin', 'total' => 485000, 'courier_name' => 'Pathao', 'tracking_code' => 'PTH-88213']);
    OrderItem::factory()->for($order)->create(['product_variant_id' => $variant->id, 'quantity' => 2]);

    $this->actingAs($this->staff)->get(route('admin.orders.packing-slip', $order))
        ->assertOk()
        ->assertSee('Nischoyota')
        ->assertSee('09612-000000')
        ->assertSee('Rahim Uddin')
        ->assertSee($order->order_number)
        ->assertSee('Collect (COD)')
        ->assertSee('৳ 4,850')
        ->assertSee('3.00 kg')
        ->assertSee('Pathao · PTH-88213');
});

it('shows PAID instead of an amount to collect, and no weight when unknown', function () {
    $order = Order::factory()->paid()->create();
    OrderItem::factory()->for($order)->create(['product_variant_id' => null]);

    $this->actingAs($this->staff)->get(route('admin.orders.packing-slip', $order))
        ->assertSee('PAID')
        ->assertDontSee('Collect (COD)')
        ->assertSee('<dt>Weight</dt><dd>—</dd>', escape: false);
});

it('marks a cancelled or returned order DO NOT SHIP instead of an amount to collect', function (OrderStatus $status) {
    $order = Order::factory()->create(['status' => $status, 'total' => 88000]);

    $this->actingAs($this->staff)->get(route('admin.orders.packing-slip', $order))
        ->assertSee('DO NOT SHIP')
        ->assertDontSee('Collect (COD)');
})->with([OrderStatus::Cancelled, OrderStatus::Returned]);

it('prints the order copy with items and totals', function () {
    $order = Order::factory()->create(['subtotal' => 500000, 'discount_total' => 12000, 'coupon_code' => 'SAVE120', 'delivery_charge' => 6000, 'total' => 494000]);
    OrderItem::factory()->for($order)->create(['product_name' => 'Ceiling Fan 56"', 'sku' => 'NS-FAN-1', 'unit_price' => 250000, 'quantity' => 2, 'line_total' => 500000]);

    $this->actingAs($this->staff)->get(route('admin.orders.invoice', $order))
        ->assertOk()
        ->assertSee('Order Copy')
        ->assertSee('Ceiling Fan 56&quot;', escape: false)
        ->assertSee('NS-FAN-1')
        ->assertSee('Discount (SAVE120)')
        ->assertSee('৳ 4,940')
        ->assertSee(route('returns'));
});

it('prints the selected orders two per page', function () {
    $orders = Order::factory()->count(3)->create();

    $response = $this->actingAs($this->staff)->post(route('admin.orders.packing-slips'), ['ids' => $orders->pluck('id')->all()])
        ->assertOk()
        ->assertSee('Packing slips (3)');

    expect(substr_count($response->getContent(), 'class="print-sheet print-sheet-slips"'))->toBe(2)
        ->and(substr_count($response->getContent(), '<article class="packing-slip">'))->toBe(3);
});

it('moves only confirmed orders to processing when asked', function () {
    $confirmed = Order::factory()->confirmed()->create();
    $pending = Order::factory()->create();

    $this->actingAs($this->staff)->post(route('admin.orders.packing-slips'), ['ids' => [$confirmed->id, $pending->id], 'move_to_processing' => '1'])
        ->assertOk();

    expect($confirmed->fresh()->status)->toBe(OrderStatus::Processing)
        ->and($confirmed->statusHistory()->sole()->note)->toBe('Packing slip printed')
        ->and($confirmed->statusHistory()->sole()->user_id)->toBe($this->staff->id)
        ->and($pending->fresh()->status)->toBe(OrderStatus::Pending);
});

it('does not move anything without the option', function () {
    $confirmed = Order::factory()->confirmed()->create();

    $this->actingAs($this->staff)->post(route('admin.orders.packing-slips'), ['ids' => [$confirmed->id]]);

    expect($confirmed->fresh()->status)->toBe(OrderStatus::Confirmed);
});

it('needs at least one order', function () {
    $this->actingAs($this->staff)->from(route('admin.orders.index'))->post(route('admin.orders.packing-slips'), [])
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHasErrors(['ids' => 'Select the orders to print.']);
});

it('keeps customers out of print pages', function () {
    $order = Order::factory()->create();
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get(route('admin.orders.invoice', $order))->assertForbidden();
    $this->actingAs($customer)->post(route('admin.orders.packing-slips'), ['ids' => [$order->id]])->assertForbidden();
});
