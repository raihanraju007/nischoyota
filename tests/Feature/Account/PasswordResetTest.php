<?php

use App\Modules\Account\Mail\OtpCodeMail;
use App\Modules\Account\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

it('resets the password with a code sent by SMS', function () {
    $user = User::factory()->create(['phone' => '01712345678', 'phone_verified_at' => null]);

    $this->post(route('password.email'), ['login' => '01712-345678'])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHas('info', "If an account exists, we've sent a code. It is valid for 5 minutes.");
    $this->get(route('password.reset'))->assertSee('017•••••678');

    $this->post(route('password.store'), [
        'code' => sentSms()->lastCodeFor('01712345678'),
        'password' => 'new-secret-1',
        'password_confirmation' => 'new-secret-1',
    ])->assertRedirect(route('account.dashboard'));

    $user->refresh();
    expect(Hash::check('new-secret-1', $user->password))->toBeTrue()
        ->and($user->phone_verified_at)->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('resets the password with a code sent by email', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'rahim@example.com']);

    $this->post(route('password.email'), ['login' => 'Rahim@example.com'])->assertRedirect(route('password.reset'));

    $code = null;
    Mail::assertSent(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code) {
        $code = $mail->code;

        return $mail->hasTo('rahim@example.com');
    });

    $this->post(route('password.store'), ['code' => $code, 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
        ->assertRedirect(route('account.dashboard'));

    expect(Hash::check('new-secret-1', $user->fresh()->password))->toBeTrue();
});

it('gives the same answer for an unknown account and sends nothing', function () {
    Mail::fake();

    $this->post(route('password.email'), ['login' => '01999999999'])
        ->assertRedirect(route('password.reset'))
        ->assertSessionHas('info', "If an account exists, we've sent a code. It is valid for 5 minutes.");

    expect(sentSms()->sentTo('01999999999'))->toBeEmpty();
    Mail::assertNothingSent();

    $this->post(route('password.store'), ['code' => '123456', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
        ->assertSessionHasErrors(['code' => 'This code is wrong or has expired. Ask for a new code.']);
});

it('rejects an expired code', function () {
    User::factory()->create(['phone' => '01712345678']);
    $this->post(route('password.email'), ['login' => '01712345678']);
    $code = sentSms()->lastCodeFor('01712345678');

    $this->travel(6)->minutes();

    $this->post(route('password.store'), ['code' => $code, 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'])
        ->assertSessionHasErrors(['code' => 'This code is wrong or has expired. Ask for a new code.']);
});

it('kills the code after five wrong tries', function () {
    User::factory()->create(['phone' => '01712345678']);
    $this->post(route('password.email'), ['login' => '01712345678']);
    $code = sentSms()->lastCodeFor('01712345678');
    $wrongCode = $code === '000000' ? '111111' : '000000';
    $input = ['password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1'];

    foreach (range(1, 5) as $try) {
        $this->post(route('password.store'), ['code' => $wrongCode, ...$input]);
    }

    $this->post(route('password.store'), ['code' => $code, ...$input])
        ->assertSessionHasErrors(['code' => 'Too many wrong codes. Ask for a new code.']);
});

it('limits sending to five codes an hour', function () {
    User::factory()->create(['phone' => '01712345678']);

    foreach (range(1, 5) as $send) {
        $this->post(route('password.email'), ['login' => '01712345678'])->assertSessionHasNoErrors();
        $this->travel(61)->seconds();
    }

    $this->post(route('password.email'), ['login' => '01712345678'])->assertSessionHasErrors('login');
    expect(sentSms()->sentTo('01712345678'))->toHaveCount(5);
});

it('validates the new password and code format', function (array $input, string $errorField) {
    $this->withSession(['auth.password_reset_recipient' => '01712345678'])
        ->post(route('password.store'), ['code' => '123456', 'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1', ...$input])
        ->assertSessionHasErrors($errorField);
})->with([
    'code not 6 digits' => [['code' => '12345'], 'code'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'not confirmed' => [['password_confirmation' => 'other-secret'], 'password'],
]);

it('sends visitors without a pending reset back to forgot password', function () {
    $this->get(route('password.reset'))->assertRedirect(route('password.request'));
});
