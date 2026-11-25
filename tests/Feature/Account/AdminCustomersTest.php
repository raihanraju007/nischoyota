<?php

use App\Modules\Account\Enums\UserStatus;
use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;
use App\Modules\Order\Data\CheckoutData;
use App\Modules\Order\Data\NewAddressData;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Exceptions\CheckoutException;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderService;
use App\Modules\Shopping\Data\CartOwner;
use App\Modules\Shopping\Services\CartService;

beforeEach(function () {
    $this->staff = User::factory()->staff()->create();
});

describe('list', function () {
    it('lists customers with order count and delivered spend', function () {
        $rahim = User::factory()->customer()->create(['name' => 'Rahim Uddin', 'email' => 'rahim@example.com']);
        Order::factory()->delivered()->create(['user_id' => $rahim->id, 'total' => 485000]);
        Order::factory()->cancelled()->create(['user_id' => $rahim->id, 'total' => 100000]);

        $this->actingAs($this->staff)->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('Rahim Uddin')
            ->assertSee('rahim@example.com')
            ->assertSee('৳ 4,850')
            ->assertViewHas('orderStats', fn (array $stats) => $stats[$rahim->id]->orderCount === 2 && $stats[$rahim->id]->cancelledCount === 1)
            ->assertDontSee($this->staff->phone);
    });

    it('searches by name, phone or email', function (string $search) {
        User::factory()->customer()->create(['name' => 'Nusrat Jahan', 'phone' => '01812556677', 'email' => 'nusrat@example.com']);
        User::factory()->customer()->create(['name' => 'Tanvir Ahmed', 'phone' => '01712998811', 'email' => null]);

        $this->actingAs($this->staff)->get(route('admin.customers.index', ['search' => $search]))
            ->assertSee('Nusrat Jahan')
            ->assertDontSee('Tanvir Ahmed');
    })->with(['name' => ['nusrat'], 'phone' => ['01812-556'], 'email' => ['nusrat@']]);

    it('filters by status with counts', function () {
        User::factory()->customer()->create(['name' => 'Active Buyer']);
        User::factory()->customer()->blocked()->create(['name' => 'Fake Orderer']);

        $this->actingAs($this->staff)->get(route('admin.customers.index', ['status' => 'blocked']))
            ->assertSee('Fake Orderer')
            ->assertDontSee('Active Buyer')
            ->assertViewHas('statusCounts', ['all' => 2, 'active' => 1, 'blocked' => 1]);
    });

    it('keeps customers out', function () {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('admin.customers.index'))->assertForbidden();
    });
});

describe('detail', function () {
    it('shows orders, addresses and stats', function () {
        $customer = User::factory()->customer()->create(['name' => 'Rahim Uddin']);
        $order = Order::factory()->delivered()->create(['user_id' => $customer->id, 'total' => 200000]);
        Address::factory()->for($customer)->create(['address_line' => 'House 12, Road 5']);

        $this->actingAs($this->staff)->get(route('admin.customers.show', $customer->id))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee(route('admin.orders.show', $order))
            ->assertSee('House 12, Road 5')
            ->assertSee('৳ 2,000');
    });

    it('does not open staff or admin accounts', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($this->staff)->get(route('admin.customers.show', $admin->id))->assertNotFound();
        $this->actingAs($this->staff)->post(route('admin.customers.block', $admin->id))->assertNotFound();

        expect($admin->fresh()->status)->toBe(UserStatus::Active);
    });
});

describe('block', function () {
    it('blocks and unblocks a customer', function () {
        $customer = User::factory()->customer()->create(['name' => 'Fake Orderer']);

        $this->actingAs($this->staff)->from(route('admin.customers.index'))->post(route('admin.customers.block', $customer->id))
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', 'Fake Orderer is blocked and can no longer sign in or order.');
        expect($customer->fresh()->status)->toBe(UserStatus::Blocked);

        $this->actingAs($this->staff)->post(route('admin.customers.unblock', $customer->id))
            ->assertSessionHas('success', 'Fake Orderer is active again.');
        expect($customer->fresh()->status)->toBe(UserStatus::Active);
    });

    it('reports when nothing changes', function () {
        $customer = User::factory()->customer()->blocked()->create();

        $this->actingAs($this->staff)->post(route('admin.customers.block', $customer->id))->assertSessionHas('info');
    });

    it('signs a blocked customer out of their account pages', function () {
        $customer = User::factory()->customer()->blocked()->create();

        $this->actingAs($customer)->get(route('account.orders'))->assertRedirect(route('login'));
        $this->assertGuest();
    });
});

describe('blocked customers cannot order', function () {
    beforeEach(function () {
        $zone = DeliveryZone::factory()->create(['charge' => 6000]);
        $this->upazila = Upazila::factory()->for(District::factory()->state(['delivery_zone_id' => $zone->id]))->create();
    });

    it('refuses a signed-in blocked account', function () {
        $customer = User::factory()->customer()->blocked()->create();

        expect(fn () => placedOrder($customer, [sellableVariant()->id => 1]))
            ->toThrow(CheckoutException::class, "We can't take orders from this account.");
        expect(Order::query()->count())->toBe(0);
    });

    it('refuses a guest using a blocked account\'s phone', function () {
        User::factory()->customer()->blocked()->create(['phone' => '01712345678']);
        $owner = CartOwner::guest('guest-token');
        app(CartService::class)->add($owner, sellableVariant()->id, 1);

        expect(fn () => app(OrderService::class)->place(new CheckoutData(
            customerName: 'Guest',
            customerPhone: '01712345678',
            customerEmail: null,
            savedAddressId: null,
            newAddress: new NewAddressData($this->upazila->district->division_id, $this->upazila->district_id, $this->upazila->id, null, 'House 1', null),
            shouldSaveAddress: false,
            paymentMethod: PaymentMethod::CashOnDelivery,
            expectedTotal: 56000,
        ), $owner, null))->toThrow(CheckoutException::class, "We can't take orders from this account.");
    });
});
