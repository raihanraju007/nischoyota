<?php

use App\Modules\Account\Models\User;

it('signs an admin in with phone and redirects to the dashboard', function () {
    $admin = User::factory()->admin()->create(['phone' => '01712345678']);

    $this->post(route('admin.login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
    expect($admin->fresh()->last_login_at)->not->toBeNull();
});

it('signs staff in with email, ignoring case', function () {
    $staff = User::factory()->staff()->create(['email' => 'staff@nischoyota.test']);

    $this->post(route('admin.login.store'), ['login' => 'Staff@Nischoyota.test', 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($staff);
});

it('accepts the phone in international format', function () {
    $admin = User::factory()->admin()->create(['phone' => '01712345678']);

    $this->post(route('admin.login.store'), ['login' => '+880 1712-345678', 'password' => 'password']);

    $this->assertAuthenticatedAs($admin);
});

it('signs in a user who has no email by phone', function () {
    $admin = User::factory()->admin()->withoutEmail()->create(['phone' => '01712345678']);

    $this->post(route('admin.login.store'), ['login' => '01712345678', 'password' => 'password']);

    $this->assertAuthenticatedAs($admin);
});

it('redirects to the admin page the user originally asked for', function () {
    User::factory()->admin()->create(['phone' => '01712345678']);

    $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));

    $this->post(route('admin.login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertRedirect(route('admin.products.index'));
});

it('shows the same message for a wrong password and an unknown user', function (string $login, string $password) {
    User::factory()->admin()->create(['phone' => '01712345678']);

    $this->from(route('admin.login'))
        ->post(route('admin.login.store'), ['login' => $login, 'password' => $password])
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors(['login' => 'These credentials do not match our records.']);

    $this->assertGuest();
})->with([
    'wrong password' => ['01712345678', 'wrong-password'],
    'unknown phone' => ['01812345678', 'password'],
    'unknown email' => ['nobody@nischoyota.test', 'password'],
]);

it('does not let customers into the admin panel', function () {
    User::factory()->customer()->create(['phone' => '01712345678']);

    $this->post(route('admin.login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertSessionHasErrors(['login' => 'This account cannot access the admin panel.']);

    $this->assertGuest();
});

it('does not let blocked admins sign in', function () {
    User::factory()->admin()->blocked()->create(['phone' => '01712345678']);

    $this->post(route('admin.login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertSessionHasErrors(['login' => 'This account is blocked. Please contact the store owner.']);

    $this->assertGuest();
});

it('validates the login form', function () {
    $this->post(route('admin.login.store'), [])
        ->assertSessionHasErrors(['login', 'password']);
});

it('locks the login after five failed attempts', function () {
    User::factory()->admin()->create(['phone' => '01712345678']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.login.store'), ['login' => '01712345678', 'password' => 'wrong-password']);
    }

    $this->post(route('admin.login.store'), ['login' => '01712345678', 'password' => 'password'])
        ->assertSessionHasErrors('login');

    expect(session('errors')->first('login'))->toStartWith('Too many attempts.');
    $this->assertGuest();
});

it('sends a signed-in admin from the login page to the dashboard', function () {
    actingAsAdmin()->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
});

it('signs the admin out', function () {
    actingAsAdmin()->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('success', 'You have been signed out.');

    $this->assertGuest();
});
