<?php

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopping\Models\Cart;
use App\Modules\Shopping\Models\WishlistItem;

beforeEach(function () {
    $this->user = User::factory()->customer()->create();
});

function savedProductIds(User $user): array
{
    return WishlistItem::query()->where('user_id', $user->id)->pluck('product_id')->all();
}

it('saves and removes a product with the heart', function () {
    $product = sellableVariant()->product;

    $this->actingAs($this->user)->from(route('shop'))
        ->post(route('wishlist.toggle', $product->id))
        ->assertRedirect(route('shop'))
        ->assertSessionHas('success', 'Added to wishlist.');
    expect(savedProductIds($this->user))->toBe([$product->id]);

    $this->post(route('wishlist.toggle', $product->id))->assertSessionHas('info', 'Removed from wishlist.');
    expect(savedProductIds($this->user))->toBe([]);
});

it('answers JSON with the saved state and the count', function () {
    $product = sellableVariant()->product;

    $this->actingAs($this->user)->postJson(route('wishlist.toggle', $product->id))
        ->assertOk()
        ->assertJson(['message' => 'Added to wishlist.', 'isSaved' => true, 'count' => 1]);

    $this->postJson(route('wishlist.toggle', $product->id))
        ->assertJson(['isSaved' => false, 'count' => 0]);
});

it('sends guests to login', function () {
    $product = sellableVariant()->product;

    $this->postJson(route('wishlist.toggle', $product->id))->assertUnauthorized();
    $this->post(route('wishlist.toggle', $product->id))->assertRedirect(route('login'));
    $this->get(route('wishlist'))->assertRedirect(route('login'));

    expect(WishlistItem::query()->count())->toBe(0);
});

it('only saves listed products but always lets them be removed', function () {
    $variant = sellableVariant();
    $variant->product->update(['status' => 'draft']);

    $this->actingAs($this->user)->postJson(route('wishlist.toggle', $variant->product_id))
        ->assertUnprocessable()
        ->assertJson(['message' => 'This product is not available.']);
    $this->postJson(route('wishlist.toggle', 999999))->assertUnprocessable();

    WishlistItem::factory()->for($this->user)->create(['product_id' => $variant->product_id]);
    $this->postJson(route('wishlist.toggle', $variant->product_id))->assertJson(['isSaved' => false]);
});

it('lists listed saved products, newest first, and hides unlisted ones', function () {
    $older = sellableVariant([], ['name' => 'Older Lamp']);
    $newer = sellableVariant([], ['name' => 'Newer Kettle']);
    $archived = sellableVariant([], ['name' => 'Gone Heater']);
    WishlistItem::factory()->for($this->user)->create(['product_id' => $older->product_id, 'created_at' => now()->subDay()]);
    WishlistItem::factory()->for($this->user)->create(['product_id' => $newer->product_id]);
    WishlistItem::factory()->for($this->user)->create(['product_id' => $archived->product_id]);
    $archived->product->update(['status' => 'archived']);

    $this->actingAs($this->user)->get(route('wishlist'))
        ->assertOk()
        ->assertSee('2 items')
        ->assertSeeInOrder(['Newer Kettle', 'Older Lamp'])
        ->assertDontSee('Gone Heater')
        ->assertSee('<span class="count-badge" data-wishlist-count>2</span>', escape: false);
});

it('shows the empty page and does not show other customers\' items', function () {
    WishlistItem::factory()->create(['product_id' => sellableVariant([], ['name' => 'Someone Else Fan'])->product_id]);

    $this->actingAs($this->user)->get(route('wishlist'))
        ->assertOk()
        ->assertSee('Your wishlist is empty')
        ->assertDontSee('Someone Else Fan');
});

it('moves a single-variant product to the cart', function () {
    $variant = sellableVariant();
    WishlistItem::factory()->for($this->user)->create(['product_id' => $variant->product_id]);

    $this->actingAs($this->user)->get(route('wishlist'))->assertSee('Move to Cart');
    $this->post(route('wishlist.move', $variant->product_id))
        ->assertRedirect(route('wishlist'))
        ->assertSessionHas('success', 'Moved to cart.');

    expect(savedProductIds($this->user))->toBe([])
        ->and(Cart::query()->where('user_id', $this->user->id)->sole()->items()->pluck('quantity', 'product_variant_id')->all())
        ->toBe([$variant->id => 1]);
});

it('keeps the product saved when the cart refuses it', function () {
    $variant = sellableVariant(['stock_quantity' => 0]);
    WishlistItem::factory()->for($this->user)->create(['product_id' => $variant->product_id]);

    $this->actingAs($this->user)->post(route('wishlist.move', $variant->product_id))
        ->assertSessionHas('error', 'This item is out of stock.');

    expect(savedProductIds($this->user))->toBe([$variant->product_id]);
});

it('does not move a product with options or one that is not saved', function () {
    $variant = sellableVariant(['stock_quantity' => 5]);
    $variant->attributeValues()->attach(AttributeValue::factory()->create());
    ProductVariant::factory()->for($variant->product)->create()->attributeValues()->attach(AttributeValue::factory()->create());
    WishlistItem::factory()->for($this->user)->create(['product_id' => $variant->product_id]);

    $this->actingAs($this->user)->post(route('wishlist.move', $variant->product_id))
        ->assertSessionHas('error', 'Please choose an option on the product page.');
    $this->post(route('wishlist.move', sellableVariant()->product_id))->assertNotFound();

    expect(savedProductIds($this->user))->toBe([$variant->product_id]);
});

it('shows saved hearts as active on cards and the product page', function () {
    $saved = sellableVariant([], ['slug' => 'saved-fan', 'min_price' => 50000]);
    sellableVariant([], ['min_price' => 50000]);
    WishlistItem::factory()->for($this->user)->create(['product_id' => $saved->product_id]);

    $shop = $this->actingAs($this->user)->get(route('shop'))->getContent();
    expect(substr_count($shop, 'class="wishlist-btn active"'))->toBe(1);

    $this->get(route('product', 'saved-fan'))
        ->assertSee('class="icon-btn-outline active"', escape: false)
        ->assertSee('action="'.route('wishlist.toggle', $saved->product_id).'"', escape: false);
});

it('signs out a blocked customer instead of showing the wishlist', function () {
    $blocked = User::factory()->customer()->blocked()->create();

    $this->actingAs($blocked)->get(route('wishlist'))->assertRedirect(route('login'));
    $this->actingAs($blocked)->post(route('wishlist.toggle', sellableVariant()->product_id))->assertRedirect(route('login'));

    expect(WishlistItem::query()->count())->toBe(0);
});

it('keeps one row when the same product is saved twice at once', function () {
    $product = sellableVariant()->product;
    $repository = app(App\Modules\Shopping\Repositories\WishlistRepository::class);

    $first = $repository->create($this->user, $product->id);
    $second = $repository->create($this->user, $product->id);

    expect($second->id)->toBe($first->id)
        ->and(savedProductIds($this->user))->toBe([$product->id]);
});
