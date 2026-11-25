<?php

use App\Modules\Account\Enums\UserRole;
use App\Modules\Account\Enums\UserStatus;

it('lets only staff and admins into the admin panel', function (UserRole $role, bool $expected) {
    expect($role->canAccessAdminPanel())->toBe($expected);
})->with([
    [UserRole::Customer, false],
    [UserRole::Staff, true],
    [UserRole::Admin, true],
]);

it('lets only admins manage the store', function (UserRole $role, bool $expected) {
    expect($role->canManageStore())->toBe($expected);
})->with([
    [UserRole::Customer, false],
    [UserRole::Staff, false],
    [UserRole::Admin, true],
]);

it('lets only active users sign in', function () {
    expect(UserStatus::Active->canSignIn())->toBeTrue()
        ->and(UserStatus::Blocked->canSignIn())->toBeFalse();
});
