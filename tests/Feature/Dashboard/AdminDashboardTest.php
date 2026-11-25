<?php

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Dashboard\Data\DashboardStats;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use Carbon\CarbonImmutable;

beforeEach(function () {
    // 07 Oct 2026, 12:00 in Dhaka (06:00 UTC)
    $this->travelTo(CarbonImmutable::parse('2026-10-07 12:00', 'Asia/Dhaka'));
});

function dashboardStats(array $query = []): DashboardStats
{
    return actingAsStaff()->get(route('admin.dashboard', $query))->assertOk()->viewData('stats');
}

it('adds up booked sales for today and this month, without cancelled or returned orders', function () {
    Order::factory()->create(['total' => 100000]);
    Order::factory()->confirmed()->create(['total' => 50000, 'placed_at' => now()->subHours(3)]);
    Order::factory()->cancelled()->create(['total' => 999900]);
    Order::factory()->status(OrderStatus::Returned)->create(['total' => 888800]);
    Order::factory()->delivered()->create(['total' => 20000, 'placed_at' => now()->subDays(5)]);
    Order::factory()->delivered()->create(['total' => 70000, 'placed_at' => now()->subMonth()]);

    $stats = dashboardStats();

    expect($stats->salesToday)->toBe(150000)
        ->and($stats->ordersToday)->toBe(2)
        ->and($stats->salesThisMonth)->toBe(170000)
        ->and($stats->ordersThisMonth)->toBe(3);
});

it('uses Bangladesh days: an order at 23:30 UTC yesterday is today in Dhaka', function () {
    Order::factory()->create(['total' => 40000, 'placed_at' => CarbonImmutable::parse('2026-10-06 23:30', 'UTC')]);
    Order::factory()->create(['total' => 10000, 'placed_at' => CarbonImmutable::parse('2026-10-06 17:30', 'UTC')]);

    $stats = dashboardStats();

    expect($stats->salesToday)->toBe(40000)
        ->and($stats->salesChart[6])->toMatchArray(['date' => '07 Oct', 'sales' => 40000, 'orders' => 1])
        ->and($stats->salesChart[5])->toMatchArray(['date' => '06 Oct', 'sales' => 10000, 'orders' => 1]);
});

it('charts 7 or 30 days, including days without sales', function () {
    Order::factory()->create(['total' => 25000, 'placed_at' => now()->subDays(2)]);

    $week = dashboardStats();
    $month = dashboardStats(['period' => 30]);

    expect($week->salesChart)->toHaveCount(7)
        ->and($week->salesChart[0])->toMatchArray(['label' => 'Thu', 'date' => '01 Oct', 'sales' => 0, 'orders' => 0])
        ->and($week->salesChart[4]['sales'])->toBe(25000)
        ->and($week->chartTotal())->toBe(25000)
        ->and($month->salesChart)->toHaveCount(30)
        ->and($month->salesChart[0]['date'])->toBe('08 Sep');
});

it('counts orders per status and new customers this month', function () {
    Order::factory()->count(2)->create();
    Order::factory()->shipped()->create();
    User::factory()->customer()->count(3)->create();
    User::factory()->customer()->create(['created_at' => now()->subMonth()]);
    User::factory()->staff()->create();

    $stats = dashboardStats();

    expect($stats->ordersPerStatus)->toMatchArray(['pending' => 2, 'shipped' => 1, 'delivered' => 0])
        ->and($stats->newCustomersThisMonth)->toBe(3);
});

it('shows recent orders and low stock, out of stock first', function () {
    $order = Order::factory()->create(['customer_name' => 'Nusrat Jahan']);
    Product::factory()->withDefaultVariant(['stock_quantity' => 3, 'sku' => 'NS-LOW-1'])->create(['name' => 'Rice Cooker']);
    Product::factory()->withDefaultVariant(['stock_quantity' => 0, 'sku' => 'NS-OUT-1'])->create(['name' => 'Leather Wallet']);
    Product::factory()->withDefaultVariant(['stock_quantity' => 50, 'sku' => 'NS-OK-1'])->create(['name' => 'Plenty Item']);

    actingAsStaff()->get(route('admin.dashboard'))
        ->assertSee('Nusrat Jahan')
        ->assertSee(route('admin.orders.show', $order))
        ->assertSee('Low Stock (2)')
        ->assertSeeInOrder(['Leather Wallet', 'Rice Cooker'])
        ->assertDontSee('Plenty Item');
});

it('caches the sales numbers for a few minutes', function () {
    dashboardStats();
    Order::factory()->create(['total' => 100000]);

    expect(dashboardStats()->salesToday)->toBe(0);

    $this->travel(6)->minutes();

    expect(dashboardStats()->salesToday)->toBe(100000);
});

it('renders for an empty store and keeps customers out', function () {
    actingAsAdmin()->get(route('admin.dashboard'))->assertOk()->assertSee('No orders yet')->assertSee('Everything is well stocked.');

    $this->actingAs(User::factory()->customer()->create())->get(route('admin.dashboard'))->assertForbidden();
});
