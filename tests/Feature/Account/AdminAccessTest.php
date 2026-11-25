<?php

use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Models\User;

it('sends guests to the admin login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});

it('forbids customers', function () {
    actingAsCustomer()->get(route('admin.dashboard'))->assertForbidden();
});

it('lets staff and admins in', function (string $role) {
    $this->actingAs(User::factory()->{$role}()->create())
        ->get(route('admin.dashboard'))
        ->assertOk();
})->with(['staff', 'admin']);

it('signs out a user who was blocked mid-session', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

    $admin->forceFill(['status' => UserStatus::Blocked])->save();

    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('error');

    $this->assertGuest();
});

it('shows the signed-in user in the top bar', function () {
    $this->actingAs(User::factory()->staff()->create(['name' => 'Rahim Uddin']))
        ->get(route('admin.dashboard'))
        ->assertSee('Rahim Uddin')
        ->assertSee('Staff');
});

it('forbids staff from admin-only pages and hides their links', function () {
    actingAsStaff()->get(route('admin.coupons.index'))->assertForbidden();

    actingAsStaff()->get(route('admin.dashboard'))->assertDontSee(route('admin.coupons.index'));
});

it('shows admin-only pages to admins', function () {
    actingAsAdmin()->get(route('admin.coupons.index'))->assertOk();

    actingAsAdmin()->get(route('admin.dashboard'))->assertSee(route('admin.coupons.index'));
});
