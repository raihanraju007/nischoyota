<?php

use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Models\User;
use Illuminate\Support\Facades\Hash;

it('sends guests to login for every account page', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['account.dashboard', 'account.profile', 'account.phone', 'account.addresses', 'account.addresses.create', 'account.orders']);

it('logs out a customer who was blocked', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('account.dashboard'))->assertOk();

    $user->forceFill(['status' => UserStatus::Blocked])->save();

    $this->get(route('account.dashboard'))->assertRedirect(route('login'))->assertSessionHas('error');
    $this->assertGuest();
});

it('shows the customer in the sidebar and on the dashboard', function () {
    $this->actingAs(User::factory()->create(['name' => 'Rahim Uddin', 'email' => 'rahim@example.com']))
        ->get(route('account.dashboard'))
        ->assertOk()
        ->assertSee('Hello, Rahim Uddin!')
        ->assertSee('rahim@example.com')
        ->assertSee('No address saved yet.');
});

describe('profile', function () {
    it('updates name and email', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile.update'), ['name' => 'New Name', 'email' => 'New@Example.com'])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHas('success');

        expect($user->fresh())->name->toBe('New Name')->email->toBe('new@example.com');
    });

    it('allows removing the email', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.profile.update'), ['name' => $user->name, 'email' => '']);

        expect($user->fresh()->email)->toBeNull();
    });

    it('validates the profile', function (array $input, string $errorField) {
        User::factory()->create(['email' => 'taken@example.com']);

        actingAsCustomer()->put(route('account.profile.update'), ['name' => 'Name', ...$input])->assertSessionHasErrors($errorField);
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'email taken' => [['email' => 'taken@example.com'], 'email'],
        'invalid email' => [['email' => 'nope'], 'email'],
    ]);

    it('changes the password', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertSessionHasNoErrors();

        expect(Hash::check('new-secret-1', $user->fresh()->password))->toBeTrue();

        $this->actingAs($user)->put(route('account.password.update'), [
            'current_password' => 'wrong',
            'password' => 'another-secret',
            'password_confirmation' => 'another-secret',
        ])->assertSessionHasErrors('current_password');
    });
});

describe('change phone', function () {
    it('changes the phone after a code sent to the new number', function () {
        $user = User::factory()->create(['phone' => '01712345678']);

        $this->actingAs($user)->post(route('account.phone.send-code'), ['phone' => '+880 1812-345678'])
            ->assertRedirect(route('account.phone'));
        $this->actingAs($user)->get(route('account.phone'))->assertSee('018•••••678');

        $this->actingAs($user)->post(route('account.phone.verify'), ['code' => sentSms()->lastCodeFor('01812345678')])
            ->assertRedirect(route('account.profile'))
            ->assertSessionHas('success', 'Your phone number has been changed.');

        expect($user->fresh()->phone)->toBe('01812345678')
            ->and(sentSms()->sentTo('01712345678'))->toBeEmpty();
    });

    it('rejects a wrong code and keeps the old phone', function () {
        $user = User::factory()->create(['phone' => '01712345678']);
        $this->actingAs($user)->post(route('account.phone.send-code'), ['phone' => '01812345678']);
        $wrongCode = sentSms()->lastCodeFor('01812345678') === '000000' ? '111111' : '000000';

        $this->actingAs($user)->post(route('account.phone.verify'), ['code' => $wrongCode])->assertSessionHasErrors('code');

        expect($user->fresh()->phone)->toBe('01712345678');
    });

    it('validates the new phone', function (string $phone) {
        User::factory()->create(['phone' => '01999999999']);
        $user = User::factory()->create(['phone' => '01712345678']);

        $this->actingAs($user)->post(route('account.phone.send-code'), ['phone' => $phone])->assertSessionHasErrors('phone');
    })->with(['12345', '01712345678', '01999999999']);
});
