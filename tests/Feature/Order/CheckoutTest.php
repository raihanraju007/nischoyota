<?php

use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;
use App\Modules\Order\Events\OrderPlaced;
use App\Modules\Order\Models\Order;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Shopping\Models\Cart;
use App\Modules\Shopping\Models\CartItem;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->zone = DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 6000, 'min_days' => 1, 'max_days' => 2]);
    $this->upazila = Upazila::factory()->for(District::factory()->state(['delivery_zone_id' => $this->zone->id]))->create(['name' => 'Dhanmondi']);
    $this->user = User::factory()->customer()->create(['name' => 'Rahim Uddin', 'phone' => '01712345678']);
    $this->variant = sellableVariant(['price' => 50000, 'stock_quantity' => 10, 'sku' => 'NS-EL-0001'], ['name' => 'Ceiling Fan']);
});

/**
 * @return array<string, mixed>
 */
function newAddressInput(array $overrides = []): array
{
    $upazila = test()->upazila;

    return [
        'name' => 'Rahim Uddin',
        'phone' => '01712-345678',
        'email' => '',
        'division_id' => $upazila->district->division_id,
        'district_id' => $upazila->district_id,
        'upazila_id' => $upazila->id,
        'area' => 'Road 5',
        'address_line' => 'House 12',
        'postal_code' => '1205',
        'payment_method' => 'cod',
        'terms' => '1',
        ...$overrides,
    ];
}

/**
 * Opens the checkout page (for its one-time token) and submits it with the total the page showed.
 */
function submitCheckout(array $input): TestResponse
{
    test()->get(route('checkout'))->assertOk();

    return test()->post(route('checkout.store'), [
        'checkout_token' => session('checkout.token'),
        'expected_total' => $input['expected_total'] ?? 56000 * 1,
        ...$input,
    ]);
}

function addToCart(int $variantId, int $quantity = 1): void
{
    test()->post(route('cart.items.store'), ['variant_id' => $variantId, 'quantity' => $quantity])->assertSessionHas('success');
}

it('sends an empty cart or one with issues back to the cart', function () {
    $this->actingAs($this->user)->get(route('checkout'))->assertRedirect(route('cart'));

    addToCart($this->variant->id, 3);
    $this->variant->update(['stock_quantity' => 1]);

    $this->get(route('checkout'))->assertRedirect(route('cart'))->assertSessionHas('error');
});

it('places a signed-in order with every record in one go', function () {
    Event::fake([OrderPlaced::class]);
    $this->actingAs($this->user);
    addToCart($this->variant->id, 2);

    submitCheckout(newAddressInput(['expected_total' => 106000]))
        ->assertRedirect(route('checkout.success'));

    $order = Order::query()->with(['items', 'statusHistory', 'payments'])->sole();
    expect($order->order_number)->toBe('ORD-'.(10000 + $order->id))
        ->and($order->user_id)->toBe($this->user->id)
        ->and($order->status->value)->toBe('pending')
        ->and($order->customer_phone)->toBe('01712345678')
        ->and($order->upazila_name)->toBe('Dhanmondi')
        ->and($order->delivery_zone_name)->toBe('Inside Dhaka')
        ->and([$order->subtotal, $order->discount_total, $order->delivery_charge, $order->total])->toBe([100000, 0, 6000, 106000])
        ->and($order->items->sole()->only(['product_name', 'sku', 'unit_price', 'quantity', 'line_total', 'is_preorder']))
        ->toBe(['product_name' => 'Ceiling Fan', 'sku' => 'NS-EL-0001', 'unit_price' => 50000, 'quantity' => 2, 'line_total' => 100000, 'is_preorder' => false])
        ->and($order->statusHistory->sole()->to_status->value)->toBe('pending')
        ->and($order->payments->sole()->only(['amount']))->toBe(['amount' => 106000])
        ->and($this->variant->fresh()->stock_quantity)->toBe(8)
        ->and(StockMovement::query()->sole()->only(['quantity_change', 'stock_after', 'order_id']))->toBe(['quantity_change' => -2, 'stock_after' => 8, 'order_id' => $order->id])
        ->and(CartItem::query()->count())->toBe(0);
    Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event): bool => $event->orderId === $order->id);

    $this->get(route('checkout.success'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('৳ 1,060')
        ->assertSee('1–2 business days');
});

it('uses a saved address and saves a new one when asked', function () {
    $saved = Address::factory()->default()->for($this->user)->create(['upazila_id' => $this->upazila->id, 'district_id' => $this->upazila->district_id, 'division_id' => $this->upazila->district->division_id, 'address_line' => 'Saved House 9']);
    $this->actingAs($this->user);
    addToCart($this->variant->id);

    $this->get(route('checkout'))->assertSee('Saved House 9')->assertSee('Delivery (Inside Dhaka)');
    submitCheckout(['name' => 'Rahim Uddin', 'phone' => '01712345678', 'address_id' => $saved->id, 'payment_method' => 'cod', 'terms' => '1'])
        ->assertRedirect(route('checkout.success'));
    expect(Order::query()->sole()->address_line)->toBe('Saved House 9');

    addToCart($this->variant->id);
    submitCheckout(newAddressInput(['address_line' => 'New House 3', 'save_address' => '1']))->assertRedirect(route('checkout.success'));
    expect($this->user->addresses()->pluck('address_line')->all())->toContain('New House 3');
});

it('refuses someone else\'s saved address', function () {
    $otherAddress = Address::factory()->create();
    $this->actingAs($this->user);
    addToCart($this->variant->id);

    submitCheckout(['name' => 'Rahim', 'phone' => '01712345678', 'address_id' => $otherAddress->id, 'payment_method' => 'cod', 'terms' => '1'])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error', 'Please choose a delivery address.');

    expect(Order::query()->count())->toBe(0);
});

it('asks a guest for a phone code before placing the order', function () {
    addToCart($this->variant->id);

    submitCheckout(newAddressInput(['phone' => '01812345678']))
        ->assertRedirect(route('checkout.verify'));
    expect(Order::query()->count())->toBe(0);
    $this->get(route('checkout.verify'))->assertSee('018•••••678');

    $code = sentSms()->lastCodeFor('01812345678');
    $this->post(route('checkout.verify.store'), ['code' => $code === '000000' ? '111111' : '000000'])
        ->assertSessionHasErrors('code');

    $this->post(route('checkout.verify.store'), ['code' => $code])->assertRedirect(route('checkout.success'));
    expect(Order::query()->sole()->only(['user_id', 'customer_phone']))->toBe(['user_id' => null, 'customer_phone' => '01812345678']);

    addToCart($this->variant->id);
    submitCheckout(newAddressInput(['phone' => '01812345678']))->assertRedirect(route('checkout.success'));
    expect(Order::query()->count())->toBe(2)
        ->and(sentSms()->sentTo('01812345678'))->toHaveCount(1);
});

it('sends no phone code to a blocked account\'s phone', function () {
    User::factory()->customer()->blocked()->create(['phone' => '01812345678']);
    addToCart($this->variant->id);

    submitCheckout(newAddressInput(['phone' => '01812345678']))
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error', "We can't take orders from this account. Please contact customer support.");
    expect(sentSms()->sentTo('01812345678'))->toBeEmpty()
        ->and(Order::query()->count())->toBe(0);
});

it('places nothing when stock runs out mid-checkout', function () {
    $this->actingAs($this->user);
    addToCart($this->variant->id, 3);
    $this->get(route('checkout'));
    $token = session('checkout.token');

    $this->variant->update(['stock_quantity' => 2]);
    $this->variant->product->update(['allow_preorder' => false]);
    Cart::query()->sole()->items()->update(['quantity' => 3]);

    $this->post(route('checkout.store'), [...newAddressInput(), 'checkout_token' => $token, 'expected_total' => 156000])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error', 'Please check your cart: some items need your attention.');

    expect(Order::query()->count())->toBe(0)
        ->and($this->variant->fresh()->stock_quantity)->toBe(2)
        ->and(CartItem::query()->sole()->quantity)->toBe(3);
});

it('reports a variant that sold out after the cart check and rolls everything back', function () {
    $this->actingAs($this->user);
    addToCart($this->variant->id, 3);
    $other = sellableVariant(['price' => 10000, 'stock_quantity' => 5]);
    addToCart($other->id);

    Order::created(fn () => ProductVariant::query()->whereKey(test()->variant->id)->update(['stock_quantity' => 1]));

    submitCheckout(newAddressInput(['expected_total' => 166000]))
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error', 'Sorry, Ceiling Fan just sold out. Only 1 left.');

    expect(Order::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and($other->fresh()->stock_quantity)->toBe(5)
        ->and(CartItem::query()->count())->toBe(2);
});

it('refuses a coupon that stopped applying', function () {
    $coupon = Coupon::factory()->create(['code' => 'SAVE120', 'value' => 12000]);
    $this->actingAs($this->user);
    addToCart($this->variant->id, 2);
    $this->post(route('cart.coupon.store'), ['code' => 'SAVE120']);
    $this->get(route('checkout'))->assertSee('− ৳ 120');
    $token = session('checkout.token');

    $coupon->update(['expires_at' => now()->subMinute()]);

    $this->post(route('checkout.store'), [...newAddressInput(), 'checkout_token' => $token, 'expected_total' => 94000])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error', 'This coupon has expired.');
    expect(Order::query()->count())->toBe(0);
});

it('counts a coupon use and the per-customer limit', function () {
    $coupon = Coupon::factory()->create(['code' => 'ONCE', 'value' => 12000, 'usage_limit_per_user' => 1]);
    $this->actingAs($this->user);
    addToCart($this->variant->id, 2);
    $this->post(route('cart.coupon.store'), ['code' => 'ONCE']);

    submitCheckout(newAddressInput(['expected_total' => 94000]))->assertRedirect(route('checkout.success'));
    expect(Order::query()->sole()->only(['discount_total', 'coupon_code', 'total']))->toBe(['discount_total' => 12000, 'coupon_code' => 'ONCE', 'total' => 94000])
        ->and($coupon->fresh()->used_count)->toBe(1);

    addToCart($this->variant->id, 2);
    $this->post(route('cart.coupon.store'), ['code' => 'ONCE'])->assertRedirect(route('cart'));
    $this->get(route('checkout'))->assertSee('You have already used this coupon.');
    submitCheckout(newAddressInput(['expected_total' => 106000]))->assertSessionHas('error', 'You have already used this coupon.');
    expect(Order::query()->count())->toBe(1);
});

it('counts the per-customer limit across the account and its phone', function () {
    Coupon::factory()->create(['code' => 'ONCE', 'value' => 12000, 'usage_limit_per_user' => 1]);
    $this->actingAs($this->user);
    addToCart($this->variant->id, 2);
    $this->post(route('cart.coupon.store'), ['code' => 'ONCE']);
    submitCheckout(newAddressInput(['expected_total' => 94000]))->assertRedirect(route('checkout.success'));

    auth()->logout();
    $this->withSession(['checkout.confirmed_phones' => ['01712345678']]);
    addToCart($this->variant->id, 2);
    $this->post(route('cart.coupon.store'), ['code' => 'ONCE']);

    submitCheckout(newAddressInput(['expected_total' => 94000]))->assertSessionHas('error', 'You have already used this coupon.');
    expect(Order::query()->count())->toBe(1);
});

it('counts a guest use of the coupon against the account with that phone', function () {
    Coupon::factory()->create(['code' => 'ONCE', 'value' => 12000, 'usage_limit_per_user' => 1]);
    $this->withSession(['checkout.confirmed_phones' => ['01712345678']]);
    addToCart($this->variant->id, 2);
    $this->post(route('cart.coupon.store'), ['code' => 'ONCE']);
    submitCheckout(newAddressInput(['expected_total' => 94000]))->assertRedirect(route('checkout.success'));

    $this->actingAs($this->user);
    addToCart($this->variant->id, 2);
    $this->post(route('cart.coupon.store'), ['code' => 'ONCE']);

    $this->get(route('checkout'))->assertSee('You have already used this coupon.');
    submitCheckout(newAddressInput(['expected_total' => 94000]))->assertSessionHas('error', 'You have already used this coupon.');
    expect(Order::query()->count())->toBe(1);
});

it('stops when the price changed after it was added to the cart', function () {
    $this->actingAs($this->user);
    addToCart($this->variant->id, 2);
    $this->get(route('checkout'));
    $token = session('checkout.token');

    $this->variant->update(['price' => 55000]);

    $this->post(route('checkout.store'), [...newAddressInput(), 'checkout_token' => $token, 'expected_total' => 106000])
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error', 'Prices changed — please check your order and place it again.');
    expect(Order::query()->count())->toBe(0);

    $this->get(route('checkout'))->assertSee('৳ 1,160');
    submitCheckout(newAddressInput(['expected_total' => 116000]))->assertRedirect(route('checkout.success'));
    expect(Order::query()->sole()->items->sole()->unit_price)->toBe(55000);
});

it('refuses an area without delivery', function () {
    $this->zone->update(['is_active' => false]);
    $this->actingAs($this->user);
    addToCart($this->variant->id);

    submitCheckout(newAddressInput())
        ->assertRedirect(route('checkout'))
        ->assertSessionHas('error', "We don't deliver to this area yet.");
    expect(Order::query()->count())->toBe(0);
});

it('creates one order when the page is submitted twice', function () {
    $this->actingAs($this->user);
    addToCart($this->variant->id);
    $this->get(route('checkout'));
    $input = [...newAddressInput(), 'checkout_token' => session('checkout.token'), 'expected_total' => 56000];

    $this->post(route('checkout.store'), $input)->assertRedirect(route('checkout.success'));
    $this->post(route('checkout.store'), $input)->assertRedirect(route('checkout.success'));

    expect(Order::query()->count())->toBe(1);
});

it('takes what is in stock for a pre-order product', function () {
    $preorder = sellableVariant(['price' => 10000, 'stock_quantity' => 1], ['allow_preorder' => true]);
    $this->actingAs($this->user);
    addToCart($preorder->id, 3);

    submitCheckout(newAddressInput(['expected_total' => 36000]))->assertRedirect(route('checkout.success'));

    expect(Order::query()->sole()->items->sole()->is_preorder)->toBeTrue()
        ->and($preorder->fresh()->stock_quantity)->toBe(0);
});

it('validates the checkout form', function (array $overrides, string $field) {
    $this->actingAs($this->user);
    addToCart($this->variant->id);

    submitCheckout(newAddressInput($overrides))->assertSessionHasErrors($field);
})->with([
    'name' => [['name' => ''], 'name'],
    'phone' => [['phone' => '12345'], 'phone'],
    'terms' => [['terms' => ''], 'terms'],
    'upazila outside district' => [['upazila_id' => 999999], 'upazila_id'],
    'address line' => [['address_line' => ''], 'address_line'],
    'payment method' => [['payment_method' => 'bkash'], 'payment_method'],
]);

it('does not let a guest send a saved address id', function () {
    addToCart($this->variant->id);

    submitCheckout(newAddressInput(['address_id' => 1]))->assertSessionHasErrors('address_id');
});

it('quotes delivery for an area as JSON', function () {
    addToCart($this->variant->id);

    $this->getJson(route('checkout.delivery-quote', ['upazila_id' => $this->upazila->id]))
        ->assertOk()
        ->assertJson(['deliveryLabel' => 'Delivery (Inside Dhaka)', 'delivery' => '৳ 60', 'total' => '৳ 560', 'expectedTotal' => 56000]);
    $this->getJson(route('checkout.delivery-quote'))->assertJson(['delivery' => 'Choose your area', 'expectedTotal' => 50000]);
});

it('sends visitors without a placed order from the success page to home', function () {
    $this->get(route('checkout.success'))->assertRedirect(route('home'));
});
