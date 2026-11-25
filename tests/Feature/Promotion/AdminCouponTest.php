<?php

use App\Modules\Promotion\Enums\CouponType;
use App\Modules\Promotion\Models\Coupon;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function couponInput(array $overrides = []): array
{
    return [
        'code' => 'SAVE120', 'type' => 'fixed', 'value' => '120', 'min_order_amount' => '1500', 'max_discount_amount' => '',
        'usage_limit' => '', 'usage_limit_per_user' => '', 'starts_at' => '', 'expires_at' => '', 'is_active' => '1',
        ...$overrides,
    ];
}

it('lists coupons with discount, usage and status', function () {
    Coupon::factory()->create(['code' => 'SAVE120', 'value' => 12000, 'min_order_amount' => 150000, 'usage_limit' => 200, 'used_count' => 84]);
    Coupon::factory()->percent(10, 50000)->create(['code' => 'EID10']);
    Coupon::factory()->create(['code' => 'OLD', 'expires_at' => now()->subDay()]);
    Coupon::factory()->create(['code' => 'SOON', 'starts_at' => now()->addDay()]);

    actingAsAdmin()->get(route('admin.coupons.index'))
        ->assertOk()
        ->assertSee('৳ 120 off')
        ->assertSee('10% off (max ৳ 500)')
        ->assertSee('84 / 200')
        ->assertSee('Expired')
        ->assertSee('Scheduled');
});

it('creates a fixed coupon with taka converted to paisa and Dhaka dates', function () {
    actingAsAdmin()->post(route('admin.coupons.store'), couponInput([
        'code' => 'save120',
        'starts_at' => '2026-10-01',
        'expires_at' => '2026-10-31',
        'usage_limit' => '200',
        'max_discount_amount' => '999',
    ]))->assertRedirect(route('admin.coupons.index'))->assertSessionHas('success');

    $coupon = Coupon::query()->sole();
    expect($coupon->code)->toBe('SAVE120')
        ->and($coupon->type)->toBe(CouponType::Fixed)
        ->and($coupon->value)->toBe(12000)
        ->and($coupon->min_order_amount)->toBe(150000)
        ->and($coupon->max_discount_amount)->toBeNull()
        ->and($coupon->usage_limit)->toBe(200)
        ->and($coupon->starts_at->toDateTimeString())->toBe('2026-09-30 18:00:00')
        ->and($coupon->expires_at->toDateTimeString())->toBe('2026-10-31 17:59:59');
});

it('creates a percent coupon with a cap', function () {
    actingAsAdmin()->post(route('admin.coupons.store'), couponInput(['code' => 'EID10', 'type' => 'percent', 'value' => '10', 'max_discount_amount' => '500']))
        ->assertSessionHasNoErrors();

    $coupon = Coupon::query()->sole();
    expect($coupon->value)->toBe(10)->and($coupon->max_discount_amount)->toBe(50000);
});

it('validates coupons', function (array $input, string $errorField) {
    Coupon::factory()->create(['code' => 'TAKEN']);

    actingAsAdmin()->post(route('admin.coupons.store'), couponInput($input))->assertSessionHasErrors($errorField);
})->with([
    'code missing' => [['code' => ''], 'code'],
    'code taken, other case' => [['code' => 'taken'], 'code'],
    'code with spaces' => [['code' => 'SAVE 120'], 'code'],
    'code too short' => [['code' => 'AB'], 'code'],
    'unknown type' => [['type' => 'bogo'], 'type'],
    'fixed value zero' => [['value' => '0'], 'value'],
    'percent above 100' => [['type' => 'percent', 'value' => '101'], 'value'],
    'percent with decimals' => [['type' => 'percent', 'value' => '10.5'], 'value'],
    'end before start' => [['starts_at' => '2026-10-10', 'expires_at' => '2026-10-09'], 'expires_at'],
    'bad date' => [['expires_at' => '31/10/2026'], 'expires_at'],
    'zero usage limit' => [['usage_limit' => '0'], 'usage_limit'],
]);

it('edits and deletes a coupon', function () {
    $coupon = Coupon::factory()->create(['code' => 'SAVE120']);

    actingAsAdmin()->get(route('admin.coupons.edit', $coupon))->assertOk()->assertSee('value="SAVE120"', escape: false)->assertSee('value="120"', escape: false);

    actingAsAdmin()->put(route('admin.coupons.update', $coupon), couponInput(['value' => '150', 'is_active' => '0']))
        ->assertSessionHasNoErrors();
    expect($coupon->fresh()->value)->toBe(15000)->and($coupon->fresh()->is_active)->toBeFalse();

    actingAsAdmin()->delete(route('admin.coupons.destroy', $coupon))->assertRedirect(route('admin.coupons.index'));
    $this->assertModelMissing($coupon);
});

it('is admin only', function (string $method, string $routeName, bool $needsCoupon) {
    $coupon = Coupon::factory()->create();
    $url = $needsCoupon ? route($routeName, $coupon) : route($routeName);

    $this->{$method}($url)->assertRedirect(route('admin.login'));
    actingAsCustomer()->{$method}($url)->assertForbidden();
    actingAsStaff()->{$method}($url)->assertForbidden();
})->with([
    ['get', 'admin.coupons.index', false],
    ['get', 'admin.coupons.create', false],
    ['post', 'admin.coupons.store', false],
    ['get', 'admin.coupons.edit', true],
    ['put', 'admin.coupons.update', true],
    ['delete', 'admin.coupons.destroy', true],
]);
