<?php

use App\Modules\Account\Enums\UserRole;
use App\Modules\Account\Models\User;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationInput(array $overrides = []): array
{
    return [
        'name' => 'Rahim Uddin',
        'phone' => '+880 1712-345678',
        'email' => 'Rahim@Example.com',
        'password' => 'secret-123',
        'password_confirmation' => 'secret-123',
        'terms' => '1',
        ...$overrides,
    ];
}

it('registers an unverified customer, sends a code and asks for it', function () {
    $this->post(route('register.store'), registrationInput())
        ->assertRedirect(route('phone.verify'))
        ->assertSessionHas('info', 'We sent a 6-digit code to your phone.');

    $user = User::query()->sole();
    expect($user->phone)->toBe('01712345678')
        ->and($user->email)->toBe('rahim@example.com')
        ->and($user->role)->toBe(UserRole::Customer)
        ->and($user->phone_verified_at)->toBeNull()
        ->and(sentSms()->lastCodeFor('01712345678'))->toMatch('/^\d{6}$/');

    $this->assertGuest();
    $this->get(route('phone.verify'))->assertOk()->assertSee('017•••••678');
});

it('verifies the phone and logs the customer in', function () {
    $this->post(route('register.store'), registrationInput());

    $this->post(route('phone.verify.store'), ['code' => sentSms()->lastCodeFor('01712345678')])
        ->assertRedirect(route('account.dashboard'))
        ->assertSessionHas('success');

    $user = User::query()->sole();
    expect($user->phone_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('rejects a wrong code and counts the tries', function () {
    $this->post(route('register.store'), registrationInput());
    $wrongCode = sentSms()->lastCodeFor('01712345678') === '000000' ? '111111' : '000000';

    $this->post(route('phone.verify.store'), ['code' => $wrongCode])
        ->assertSessionHasErrors(['code' => 'Wrong code. 4 tries left.']);

    $this->assertGuest();
});

it('validates registration', function (array $input, string $errorField) {
    User::factory()->create(['phone' => '01812345678', 'email' => 'taken@example.com']);

    $this->post(route('register.store'), registrationInput($input))->assertSessionHasErrors($errorField);
})->with([
    'name missing' => [['name' => ''], 'name'],
    'invalid phone' => [['phone' => '12345'], 'phone'],
    'verified phone taken' => [['phone' => '01812345678'], 'phone'],
    'email taken' => [['email' => 'taken@example.com'], 'email'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'password not confirmed' => [['password_confirmation' => 'different-1'], 'password'],
    'terms not accepted' => [['terms' => ''], 'terms'],
]);

it('lets an unverified phone register again', function () {
    $this->post(route('register.store'), registrationInput(['name' => 'First Try']));
    $this->travel(2)->minutes();

    $this->post(route('register.store'), registrationInput(['name' => 'Second Try', 'email' => '']))
        ->assertRedirect(route('phone.verify'));

    $user = User::query()->sole();
    expect($user->name)->toBe('Second Try')->and($user->email)->toBeNull()
        ->and(sentSms()->sentTo('01712345678'))->toHaveCount(2);
});

it('asks to wait when registering again within a minute', function () {
    $this->post(route('register.store'), registrationInput());

    $this->post(route('register.store'), registrationInput())
        ->assertRedirect(route('phone.verify'))
        ->assertSessionHas('info', fn (string $message) => str_starts_with($message, 'Please wait'));

    expect(sentSms()->sentTo('01712345678'))->toHaveCount(1);
});

it('resends a code after a minute and the old one stops working', function () {
    $this->post(route('register.store'), registrationInput());
    $oldCode = sentSms()->lastCodeFor('01712345678');

    $this->post(route('phone.verify.resend'))->assertSessionHasErrors('code');
    $this->travel(61)->seconds();
    $this->post(route('phone.verify.resend'))->assertSessionHas('success');
    $newCode = sentSms()->lastCodeFor('01712345678');

    if ($oldCode !== $newCode) {
        $this->post(route('phone.verify.store'), ['code' => $oldCode])->assertSessionHasErrors('code');
    }
    $this->post(route('phone.verify.store'), ['code' => $newCode])->assertRedirect(route('account.dashboard'));
});

it('sends visitors without a pending verification to login', function () {
    $this->get(route('phone.verify'))->assertRedirect(route('login'));
    $this->post(route('phone.verify.store'), ['code' => '123456'])->assertRedirect(route('login'));
});

it('sends signed-in customers away from register', function () {
    actingAsCustomer()->get(route('register'))->assertRedirect(route('home'));
});
