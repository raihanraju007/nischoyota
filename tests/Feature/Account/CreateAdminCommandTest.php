<?php

use App\Modules\Account\Enums\UserRole;
use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates an active admin', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Store Owner',
        '--phone' => '+880 1712-345678',
        '--email' => 'Owner@Nischoyota.test',
        '--password' => 'secret-123',
    ])->assertSuccessful();

    $admin = User::query()->sole();

    expect($admin->name)->toBe('Store Owner')
        ->and($admin->phone)->toBe('01712345678')
        ->and($admin->email)->toBe('owner@nischoyota.test')
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($admin->status)->toBe(UserStatus::Active)
        ->and(Hash::check('secret-123', $admin->password))->toBeTrue();
});

it('creates an admin without an email', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Store Owner',
        '--phone' => '01712345678',
        '--email' => '',
        '--password' => 'secret-123',
    ])->assertSuccessful();

    expect(User::query()->sole()->email)->toBeNull();
});

it('rejects invalid input', function (array $options) {
    User::factory()->create(['phone' => '01812345678', 'email' => 'taken@nischoyota.test']);

    $this->artisan('app:create-admin', [
        '--name' => 'Store Owner',
        '--phone' => '01712345678',
        '--email' => '',
        '--password' => 'secret-123',
        ...$options,
    ])->assertFailed();

    expect(User::query()->count())->toBe(1);
})->with([
    'invalid phone' => [['--phone' => '12345']],
    'phone taken' => [['--phone' => '01812345678']],
    'email taken' => [['--email' => 'taken@nischoyota.test']],
    'short password' => [['--password' => 'short']],
]);
