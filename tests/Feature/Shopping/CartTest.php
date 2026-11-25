<?php

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Http\CurrentCart;
use App\Modules\Shopping\Models\Cart;
use App\Modules\Shopping\Models\CartItem;

function guestCartItems(): array
{
    return Cart::query()->whereNull('user_id')->sole()->items()->pluck('quantity', 'product_variant_id')->all();
}

it('shows the empty cart without creating a cart row', function () {
    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('Your cart is empty');

    expect(Cart::query()->count())->toBe(0);
});

it('lets a guest add a product and see it in the cart', function () {
    $variant = sellableVariant(['price' => 48500], ['name' => 'Ceiling Fan']);

    $this->from(route('home'))
        ->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 2])
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'Added to cart.');

    expect(guestCartItems())->toBe([$variant->id => 2]);

    $this->get(route('cart'))
        ->assertOk()
        ->assertSee('Ceiling Fan')
        ->assertSee('1 item')
        ->assertSee('৳ 970')
        ->assertSee('data-cart-count', escape: false);
});

it('adds to the existing line when the same variant is added again', function () {
    $variant = sellableVariant();

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id]);
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 3]);

    expect(guestCartItems())->toBe([$variant->id => 4]);
});

it('keeps a signed-in customer cart on their account', function () {
    $user = User::factory()->customer()->create();
    $variant = sellableVariant();

    $this->actingAs($user)->post(route('cart.items.store'), ['variant_id' => $variant->id]);

    expect(Cart::query()->where('user_id', $user->id)->sole()->items()->count())->toBe(1)
        ->and(session(CurrentCart::SESSION_KEY))->toBeNull();
});

it('refuses products that cannot be bought', function (string $case, string $message) {
    $variant = sellableVariant(['stock_quantity' => 3]);
    $quantity = 1;

    match ($case) {
        'inactive variant' => $variant->update(['is_active' => false]),
        'draft product' => $variant->product->update(['status' => 'draft']),
        'hidden category' => $variant->product->category->update(['is_active' => false]),
        'sold out' => $variant->update(['stock_quantity' => 0]),
        'too many' => $quantity = 4,
    };

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => $quantity])
        ->assertSessionHas('error', $message);

    expect(CartItem::query()->count())->toBe(0);
})->with([
    ['inactive variant', 'This product is not available.'],
    ['draft product', 'This product is not available.'],
    ['hidden category', 'This product is not available.'],
    ['sold out', 'This item is out of stock.'],
    ['too many', 'Only 3 left in stock.'],
]);

it('allows more than the stock for a pre-order product', function () {
    $variant = sellableVariant(['stock_quantity' => 0], ['allow_preorder' => true]);

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 5])
        ->assertSessionHas('success');

    expect(guestCartItems())->toBe([$variant->id => 5]);
});

it('limits a line to 99', function () {
    $variant = sellableVariant(['stock_quantity' => 500]);

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 60]);
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 40])
        ->assertSessionHas('error', 'You can buy at most 99 of one item.');

    expect(guestCartItems())->toBe([$variant->id => 60]);
});

it('validates the add request', function (array $input, string $field) {
    $this->post(route('cart.items.store'), $input)->assertSessionHasErrors($field);
})->with([
    'missing variant' => [[], 'variant_id'],
    'zero quantity' => [['variant_id' => 1, 'quantity' => 0], 'quantity'],
    'too large' => [['variant_id' => 1, 'quantity' => 100], 'quantity'],
]);

it('answers JSON with the count and the new drawer', function () {
    $variant = sellableVariant([], ['name' => 'Electric Kettle']);

    $this->postJson(route('cart.items.store'), ['variant_id' => $variant->id])
        ->assertOk()
        ->assertJson(['message' => 'Added to cart.', 'count' => 1])
        ->assertJsonPath('drawer', fn (string $html): bool => str_contains($html, 'Electric Kettle') && str_contains($html, 'Your Cart (1)'));
});

it('answers JSON 422 when the product cannot be added', function () {
    $variant = sellableVariant(['stock_quantity' => 0]);

    $this->postJson(route('cart.items.store'), ['variant_id' => $variant->id])
        ->assertUnprocessable()
        ->assertJson(['message' => 'This item is out of stock.']);
});

it('sends Buy Now to checkout', function () {
    $variant = sellableVariant();

    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'buy_now' => 1])
        ->assertRedirect(route('checkout'));
});

it('updates a line quantity with a stock check', function () {
    $variant = sellableVariant(['stock_quantity' => 5]);
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id]);
    $item = CartItem::query()->sole();

    $this->patch(route('cart.items.update', $item->id), ['quantity' => 4])->assertSessionHas('success', 'Quantity updated.');
    expect($item->fresh()->quantity)->toBe(4);

    $this->patch(route('cart.items.update', $item->id), ['quantity' => 6])->assertSessionHas('error', 'Only 5 left in stock.');
    expect($item->fresh()->quantity)->toBe(4);

    $this->patch(route('cart.items.update', $item->id), ['quantity' => 0])->assertSessionHasErrors('quantity');
});

it('removes a line and clears the cart', function () {
    $first = sellableVariant();
    $second = sellableVariant();
    $this->post(route('cart.items.store'), ['variant_id' => $first->id]);
    $this->post(route('cart.items.store'), ['variant_id' => $second->id]);

    $this->delete(route('cart.items.destroy', CartItem::query()->where('product_variant_id', $first->id)->sole()->id))
        ->assertSessionHas('info', 'Removed from cart.');
    expect(guestCartItems())->toBe([$second->id => 1]);

    $this->delete(route('cart.clear'))->assertRedirect(route('cart'));
    expect(CartItem::query()->count())->toBe(0);
});

it('returns 404 for a line in someone else\'s cart', function (string $method, string $routeName) {
    $otherItem = CartItem::factory()->for(Cart::factory()->forUser())->create(['product_variant_id' => sellableVariant()->id]);
    $mine = sellableVariant();
    $this->post(route('cart.items.store'), ['variant_id' => $mine->id]);

    $this->{$method}(route($routeName, $otherItem->id), ['quantity' => 2])->assertNotFound();

    expect($otherItem->fresh())->not->toBeNull()->quantity->toBe(1);
})->with([
    'update' => ['patch', 'cart.items.update'],
    'remove' => ['delete', 'cart.items.destroy'],
]);

it('returns 404 when a visitor without a cart changes a line', function () {
    $this->delete(route('cart.items.destroy', 1))->assertNotFound();
});
