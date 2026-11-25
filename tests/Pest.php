<?php

use App\Modules\Account\Models\User;
use App\Modules\Account\Sms\ArraySmsGateway;
use App\Modules\Account\Sms\SmsGateway;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Order\Data\CheckoutData;
use App\Modules\Order\Data\NewAddressData;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\CheckoutService;
use App\Modules\Order\Services\OrderService;
use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function actingAsAdmin(): TestCase
{
    return test()->actingAs(User::factory()->admin()->create());
}

function actingAsStaff(): TestCase
{
    return test()->actingAs(User::factory()->staff()->create());
}

function sentSms(): ArraySmsGateway
{
    return app(SmsGateway::class);
}

function actingAsCustomer(): TestCase
{
    return test()->actingAs(User::factory()->customer()->create());
}

/**
 * A variant that can be put in the cart: active product in an active category (F-021).
 *
 * @param  array<string, mixed>  $variant
 * @param  array<string, mixed>  $product
 */
function sellableVariant(array $variant = [], array $product = []): ProductVariant
{
    $product = Product::factory()->active()->create(['category_id' => Category::factory(), ...$product]);

    return ProductVariant::factory()->for($product)->create(['is_default' => true, 'price' => 50000, 'stock_quantity' => 10, ...$variant]);
}

/**
 * Places a real order through the checkout services (stock movements, coupon use, history).
 * The test must set $this->upazila (the delivery address).
 *
 * @param  array<int, int>  $quantities  variant id => quantity
 */
function placedOrder(User $user, array $quantities, ?string $couponCode = null): Order
{
    $owner = CartOwner::user($user);
    foreach ($quantities as $variantId => $quantity) {
        app(CartService::class)->add($owner, $variantId, $quantity);
    }
    if ($couponCode !== null) {
        app(CartService::class)->applyCoupon($owner, $couponCode);
    }

    $upazila = test()->upazila;
    $contents = app(CartService::class)->contentsFor($owner);
    $total = app(CheckoutService::class)->totalsFor($contents, $upazila->id, $user, $user->phone)->total();

    return app(OrderService::class)->place(new CheckoutData(
        customerName: $user->name,
        customerPhone: $user->phone,
        customerEmail: null,
        savedAddressId: null,
        newAddress: new NewAddressData($upazila->district->division_id, $upazila->district_id, $upazila->id, null, 'House 12', '1205'),
        shouldSaveAddress: false,
        paymentMethod: PaymentMethod::CashOnDelivery,
        expectedTotal: $total,
    ), $owner, $user);
}
