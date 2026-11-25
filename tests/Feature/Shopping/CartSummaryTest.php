<?php

use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Content\Services\SettingService;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Shopping\Models\Cart;
use App\Support\Money;

beforeEach(function () {
    $this->user = User::factory()->customer()->create();
    $this->cart = Cart::factory()->forUser($this->user)->create();
});

function cartLine(array $variant = [], array $product = [], int $quantity = 1, ?int $addedPrice = null): void
{
    $sellable = sellableVariant($variant, $product);

    test()->cart->items()->create([
        'product_variant_id' => $sellable->id,
        'quantity' => $quantity,
        'added_price' => $addedPrice ?? $sellable->price,
    ]);
}

it('totals the live variant price, not the price when added', function () {
    cartLine(['price' => 60000], ['name' => 'Desk Lamp'], quantity: 2, addedPrice: 50000);

    $this->actingAs($this->user)->get(route('cart'))
        ->assertSee('Price changed from ৳ 500')
        ->assertSeeInOrder(['Subtotal', '৳ 1,200', 'Total', '৳ 1,200']);
});

it('marks lines that cannot be bought and leaves them out of the subtotal', function (array $variant, array $product, int $quantity, string $note) {
    cartLine(['price' => 10000]);
    cartLine(['price' => 99900, ...$variant], $product, $quantity);

    $response = $this->actingAs($this->user)->get(route('cart'))
        ->assertSee($note)
        ->assertSeeInOrder(['Subtotal', '৳ 100']);

    str_contains($note, 'Pre-order')
        ? $response->assertDontSee('Please fix or remove the items marked above')
        : $response->assertSee('Please fix or remove the items marked above')->assertSee('is-disabled', escape: false);
})->with([
    'not enough stock' => [['stock_quantity' => 2], [], 3, 'Only 2 left — please lower the quantity'],
    'out of stock' => [['stock_quantity' => 0], [], 1, 'Out of stock — please remove it'],
    'archived' => [[], ['status' => 'archived'], 1, 'No longer available — please remove it'],
]);

it('counts pre-order lines and keeps checkout open', function () {
    cartLine(['price' => 10000, 'stock_quantity' => 0], ['allow_preorder' => true]);

    $this->actingAs($this->user)->get(route('cart'))
        ->assertSee('Pre-order — ships when back in stock')
        ->assertSeeInOrder(['Subtotal', '৳ 100'])
        ->assertDontSee('Please fix or remove the items marked above');
});

it('applies, re-checks and removes a coupon', function () {
    Coupon::factory()->create(['code' => 'SAVE120', 'value' => 12000, 'min_order_amount' => 100000]);
    cartLine(['price' => 150000, 'stock_quantity' => 10]);
    $item = $this->cart->items()->sole();

    $this->actingAs($this->user)->post(route('cart.coupon.store'), ['code' => ' save120 '])
        ->assertRedirect(route('cart'))
        ->assertSessionHas('success', 'Coupon SAVE120 applied.');
    expect($this->cart->fresh()->coupon_code)->toBe('SAVE120');

    $this->get(route('cart'))->assertSeeInOrder(['Discount (SAVE120)', '− ৳ 120', 'Total', '৳ 1,380']);

    $item->update(['quantity' => 1]);
    $item->variant->update(['price' => 50000]);
    $this->get(route('cart'))
        ->assertSee('Spend at least ৳ 1,000 to use this coupon.')
        ->assertDontSee('− ৳ 120');
    expect($this->cart->fresh()->coupon_code)->toBe('SAVE120');

    $this->delete(route('cart.coupon.destroy'))->assertSessionHas('info', 'Coupon removed.');
    expect($this->cart->fresh()->coupon_code)->toBeNull();
});

it('does not save a coupon that does not apply', function () {
    cartLine();

    $this->actingAs($this->user)->from(route('cart'))->post(route('cart.coupon.store'), ['code' => 'NOPE'])
        ->assertRedirect(route('cart'))
        ->assertSessionHasErrors(['code' => 'This coupon code is not valid.']);

    expect($this->cart->fresh()->coupon_code)->toBeNull();
});

it('estimates delivery from the default address', function () {
    $zone = DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 7000]);
    $upazila = Upazila::factory()->for(District::factory()->state(['delivery_zone_id' => $zone->id]))->create();
    Address::factory()->default()->for($this->user)->create(['upazila_id' => $upazila->id, 'district_id' => $upazila->district_id, 'division_id' => $upazila->district->division_id]);
    cartLine(['price' => 50000]);

    $this->actingAs($this->user)->get(route('cart'))
        ->assertSeeInOrder(['Delivery (Inside Dhaka)', '৳ 70', 'Total', '৳ 570']);
});

it('says delivery is calculated at checkout without a default address and shows the free-delivery hint', function () {
    cartLine(['price' => 50000]);
    $threshold = app(SettingService::class)->current()->freeDeliveryThreshold;

    $this->actingAs($this->user)->get(route('cart'))
        ->assertSee('Calculated at checkout')
        ->assertSee('Add '.Money::format($threshold - 50000).' more for free delivery');
});

it('shows the cart count in the header and the lines in the drawer', function () {
    cartLine([], ['name' => 'Wall Clock'], quantity: 3);
    cartLine([], ['name' => 'Table Fan']);

    $this->actingAs($this->user)->get(route('home'))
        ->assertSee('<span class="count-badge" data-cart-count>2</span>', escape: false)
        ->assertSee('Your Cart (2)')
        ->assertSee('Wall Clock')
        ->assertSee('Table Fan');
});
