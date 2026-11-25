<?php

use App\Modules\Account\Models\User;

it('rejects a text field sent as an array instead of failing', function (string $routeName, array $input, string $field) {
    $this->post(route($routeName), $input)->assertSessionHasErrors($field);
})->with([
    'coupon code' => ['cart.coupon.store', ['code' => ['SAVE']], 'code'],
    'checkout phone' => ['checkout.store', ['phone' => ['01712345678'], 'checkout_token' => 'x'], 'phone'],
    'tracking phone' => ['orders.track.lookup', ['order_number' => 'ORD-10001', 'phone' => ['01712345678']], 'phone'],
    'registration phone' => ['register.store', ['phone' => ['01712345678']], 'phone'],
    'contact phone' => ['contact.store', ['phone' => ['01712345678']], 'phone'],
]);

it('shows the form again after a field came back as an array', function (string $pageRoute, string $submitRoute, array $input) {
    $this->from(route($pageRoute))->followingRedirects()->post(route($submitRoute), $input)->assertOk();
})->with([
    'tracking' => ['orders.track', 'orders.track.lookup', ['order_number' => ['ORD-10001'], 'phone' => ['01712345678']]],
    'registration' => ['register', 'register.store', ['name' => ['Rahim'], 'phone' => ['01712345678'], 'email' => ['a@b.c']]],
    'contact' => ['contact', 'contact.store', ['name' => ['Rahim'], 'phone' => ['01712345678'], 'message' => ['Hello there']]],
]);

it('ignores an order status filter sent as an array', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get(route('account.orders', ['status' => ['pending']]))
        ->assertOk();
});
