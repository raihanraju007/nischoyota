<?php

use App\Modules\Account\Models\User;
use Illuminate\Support\Facades\Hash;

it('changes the signed-in user\'s password', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)
        ->from(route('admin.settings'))
        ->put(route('admin.password.update'), [
            'current_password' => 'password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])
        ->assertRedirect(route('admin.settings'))
        ->assertSessionHas('success', 'Password updated.');

    expect(Hash::check('new-secret-123', $user->fresh()->password))->toBeTrue();
})->with(['staff', 'admin']);

it('rejects a wrong current password', function () {
    $user = User::factory()->admin()->create();

    $this->actingAs($user)
        ->put(route('admin.password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-secret-123',
            'password_confirmation' => 'new-secret-123',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

it('validates the new password', function (array $input, string $errorField) {
    actingAsAdmin()
        ->put(route('admin.password.update'), ['current_password' => 'password', ...$input])
        ->assertSessionHasErrors($errorField);
})->with([
    'too short' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'not confirmed' => [['password' => 'new-secret-123', 'password_confirmation' => 'different-123'], 'password'],
    'same as current' => [['password' => 'password', 'password_confirmation' => 'password'], 'password'],
]);

it('does not let guests change a password', function () {
    $this->put(route('admin.password.update'), [])->assertRedirect(route('admin.login'));
});
