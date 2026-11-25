<?php

use App\Modules\Account\Models\User;
use App\Modules\Shopping\Http\CurrentCart;
use App\Modules\Shopping\Models\Cart;

function signIn(User $user): void
{
    test()->post(route('login.store'), ['login' => $user->phone, 'password' => 'password'])
        ->assertRedirect(route('account.dashboard'));
}

it('moves the guest cart into the user cart on login', function () {
    $user = User::factory()->customer()->create(['phone' => '01712345678']);
    $variant = sellableVariant();
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 2]);

    signIn($user);

    expect(Cart::query()->sole()->user_id)->toBe($user->id)
        ->and(Cart::query()->sole()->items()->pluck('quantity', 'product_variant_id')->all())->toBe([$variant->id => 2])
        ->and(session(CurrentCart::SESSION_KEY))->toBeNull();

    $this->get(route('cart'))->assertSee($variant->product->name);
});

it('sums the same variant, caps at 99 and keeps the user coupon', function () {
    $user = User::factory()->customer()->create(['phone' => '01712345678']);
    $shared = sellableVariant(['stock_quantity' => 500]);
    $guestOnly = sellableVariant();
    $userCart = Cart::factory()->forUser($user)->create(['coupon_code' => 'MINE']);
    $userCart->items()->create(['product_variant_id' => $shared->id, 'quantity' => 60, 'added_price' => $shared->price]);

    $this->post(route('cart.items.store'), ['variant_id' => $shared->id, 'quantity' => 50]);
    $this->post(route('cart.items.store'), ['variant_id' => $guestOnly->id]);
    Cart::query()->whereNull('user_id')->update(['coupon_code' => 'GUEST']);

    signIn($user);

    expect(Cart::query()->count())->toBe(1)
        ->and($userCart->fresh()->coupon_code)->toBe('MINE')
        ->and($userCart->items()->pluck('quantity', 'product_variant_id')->all())->toBe([$shared->id => 99, $guestOnly->id => 1]);
});

it('takes the guest coupon when the user cart has none', function () {
    $user = User::factory()->customer()->create(['phone' => '01712345678']);
    $this->post(route('cart.items.store'), ['variant_id' => sellableVariant()->id]);
    Cart::query()->update(['coupon_code' => 'GUEST']);

    signIn($user);

    expect(Cart::query()->sole()->coupon_code)->toBe('GUEST');
});

it('keeps the user cart after logout and starts the guest fresh', function () {
    $user = User::factory()->customer()->create(['phone' => '01712345678']);
    Cart::factory()->forUser($user)->create()->items()->create(['product_variant_id' => sellableVariant()->id, 'quantity' => 1, 'added_price' => 50000]);

    $this->actingAs($user)->post(route('logout'));

    $this->get(route('cart'))->assertSee('Your cart is empty');
    expect(Cart::query()->where('user_id', $user->id)->sole()->items()->count())->toBe(1);
});

it('prunes guest carts untouched for 30 days', function () {
    $old = Cart::factory()->create(['updated_at' => now()->subDays(31)]);
    $recent = Cart::factory()->create();
    $userCart = Cart::factory()->forUser()->create(['updated_at' => now()->subDays(90)]);

    $this->artisan('model:prune', ['--model' => [Cart::class]])->assertSuccessful();

    expect(Cart::query()->pluck('id')->all())->toEqualCanonicalizing([$recent->id, $userCart->id])
        ->and($old->fresh())->toBeNull();
});

it('keeps the guest cart through register and phone verification', function () {
    $variant = sellableVariant();
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id, 'quantity' => 3]);

    $this->post(route('register.store'), [
        'name' => 'Rahim Uddin', 'phone' => '01712345678', 'email' => '',
        'password' => 'secret-123', 'password_confirmation' => 'secret-123', 'terms' => '1',
    ]);
    $this->post(route('phone.verify.store'), ['code' => sentSms()->lastCodeFor('01712345678')]);

    $user = User::query()->where('phone', '01712345678')->sole();
    $this->assertAuthenticatedAs($user);
    expect(Cart::query()->sole()->user_id)->toBe($user->id)
        ->and(Cart::query()->sole()->items()->pluck('quantity', 'product_variant_id')->all())->toBe([$variant->id => 3]);
});

it('keeps the guest cart through a password reset', function () {
    $user = User::factory()->customer()->create(['phone' => '01712345678']);
    $variant = sellableVariant();
    $this->post(route('cart.items.store'), ['variant_id' => $variant->id]);

    $this->post(route('password.email'), ['login' => '01712345678']);
    $this->post(route('password.store'), [
        'code' => sentSms()->lastCodeFor('01712345678'),
        'password' => 'new-secret-1',
        'password_confirmation' => 'new-secret-1',
    ])->assertRedirect(route('account.dashboard'));

    expect(Cart::query()->sole()->user_id)->toBe($user->id);
});
