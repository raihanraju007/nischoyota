<?php

use App\Modules\Account\Models\User;

it('logs in with phone or email', function (string $login) {
    $user = User::factory()->create(['phone' => '01712345678', 'email' => 'rahim@example.com']);

    $this->post(route('login.store'), ['login' => $login, 'password' => 'password'])
        ->assertRedirect(route('account.dashboard'));

    $this->assertAuthenticatedAs($user);
})->with(['01712345678', '+880 1712-345678', 'Rahim@Example.com']);

it('sends the customer back to the page they wanted', function () {
    User::factory()->create(['phone' => '01712345678']);
    $this->withSession(['url.intended' => route('checkout')]);

    $this->post(route('login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertRedirect(route('checkout'));
});

it('shows one message for an unknown user or wrong password', function (string $login, string $password) {
    User::factory()->create(['phone' => '01712345678']);

    $this->post(route('login.store'), ['login' => $login, 'password' => $password])
        ->assertSessionHasErrors(['login' => 'These credentials do not match our records.']);

    $this->assertGuest();
})->with([
    ['01712345678', 'wrong-password'],
    ['01999999999', 'password'],
]);

it('locks after five failed attempts', function () {
    User::factory()->create(['phone' => '01712345678']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('login.store'), ['login' => '01712345678', 'password' => 'wrong']);
    }

    $this->post(route('login.store'), ['login' => '01712345678', 'password' => 'password'])->assertSessionHasErrors('login');
    expect(session('errors')->first('login'))->toStartWith('Too many attempts.');
    $this->assertGuest();
});

it('keeps blocked customers out', function () {
    User::factory()->blocked()->create(['phone' => '01712345678']);

    $this->post(route('login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertSessionHasErrors(['login' => 'This account is blocked. Please contact customer support.']);
});

it('sends an unverified customer to verify the phone first', function () {
    User::factory()->create(['phone' => '01712345678', 'phone_verified_at' => null]);

    $this->post(route('login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertRedirect(route('phone.verify'));

    $this->assertGuest();
    expect(sentSms()->lastCodeFor('01712345678'))->not->toBeNull();
});

it('logs out', function () {
    actingAsCustomer()->post(route('logout'))
        ->assertRedirect(route('home'))
        ->assertSessionHas('success', 'You have been logged out.');

    $this->assertGuest();
});

it('shows the login form with a link to register', function () {
    $this->get(route('login'))->assertOk()->assertSee(route('register'))->assertSee(route('password.request'));
});
