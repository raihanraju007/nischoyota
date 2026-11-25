<?php

it('renders every admin page for an admin', function (string $routeName) {
    actingAsAdmin()->get(route($routeName))->assertOk();
})->with([
    'admin.dashboard',
    'admin.orders.index',
    'admin.products.index', 'admin.products.create',
    'admin.categories.index', 'admin.customers.index', 'admin.reviews.index', 'admin.coupons.index',
    'admin.settings',
]);

it('renders the admin login page for guests', function () {
    $this->get(route('admin.login'))->assertOk();
});

it('highlights the current section in the admin sidebar', function () {
    actingAsAdmin()->get(route('admin.products.index'))
        ->assertSeeInOrder(['aria-current="page"', 'Products'], escape: false);
});

it('keeps admin pages out of search engines', function () {
    actingAsAdmin()->get(route('admin.dashboard'))
        ->assertSee('<meta name="robots" content="noindex, nofollow">', escape: false);
});
