<?php

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Models\WishlistItem;

it('renders every storefront page', function (string $routeName) {
    $this->get(route($routeName))->assertOk();
})->with([
    'home', 'shop', 'deals', 'search',
    'cart', 'orders.track',
    'login', 'register', 'password.request',
    'contact',
]);

it('renders every account page for a signed-in customer', function (string $routeName) {
    actingAsCustomer()->get(route($routeName))->assertOk();
})->with([
    'wishlist', 'account.dashboard', 'account.orders',
    'account.addresses', 'account.addresses.create', 'account.profile', 'account.phone',
]);

it('renders the branded 404 page', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertSee('Page Not Found');
});

it('leaves the prototype quick view modal out of pages with real product cards', function () {
    $user = User::factory()->customer()->create();
    WishlistItem::factory()->for($user)->create(['product_id' => sellableVariant()->product_id]);

    $html = $this->actingAs($user)->get(route('wishlist'))->getContent();

    expect(substr_count($html, 'id="modal-quick-view"'))->toBe(0);
});
