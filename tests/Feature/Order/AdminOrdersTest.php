<?php

use App\Modules\Account\Models\User;
use App\Modules\Inventory\Enums\StockMovementReason;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentStatus;
use App\Modules\Order\Events\OrderCancelled;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\Payment;
use App\Modules\Promotion\Models\Coupon;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $zone = DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 6000]);
    $this->upazila = Upazila::factory()->for(District::factory()->state(['delivery_zone_id' => $zone->id]))->create(['name' => 'Dhanmondi']);
    $this->customer = User::factory()->customer()->create(['phone' => '01712345678', 'name' => 'Rahim Uddin']);
    $this->staff = User::factory()->staff()->create(['name' => 'Karim Staff']);
});

function changeStatus(Order $order, string $status, array $fields = []): TestResponse
{
    return test()->actingAs(test()->staff)->from(route('admin.orders.show', $order))
        ->post(route('admin.orders.status', $order), ['status' => $status, ...$fields]);
}

describe('list', function () {
    it('shows orders newest first with tab counts, for staff', function () {
        $older = Order::factory()->create(['placed_at' => now()->subHour(), 'customer_name' => 'Older Buyer']);
        $newer = Order::factory()->confirmed()->create(['customer_name' => 'Newer Buyer']);

        $this->actingAs($this->staff)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSeeInOrder([$newer->order_number, $older->order_number])
            ->assertViewHas('tabCounts', fn (array $counts) => $counts['all'] === 2 && $counts['pending'] === 1 && $counts['confirmed'] === 1 && $counts['returned'] === 0);
    });

    it('filters by status tab', function () {
        $pending = Order::factory()->create();
        $shipped = Order::factory()->shipped()->create();

        actingAsAdmin()->get(route('admin.orders.index', ['status' => 'shipped']))
            ->assertSee($shipped->order_number)
            ->assertDontSee($pending->order_number.'<', escape: false);
    });

    it('searches by order number, phone or name', function (string $search) {
        $match = Order::factory()->create(['customer_name' => 'Nusrat Jahan', 'customer_phone' => '01812556677']);
        $other = Order::factory()->create(['customer_name' => 'Tanvir Ahmed', 'customer_phone' => '01712998811']);
        $search = str_replace('{number}', $match->order_number, $search);

        actingAsAdmin()->get(route('admin.orders.index', ['search' => $search]))
            ->assertSee($match->order_number)
            ->assertDontSee($other->order_number);
    })->with([
        'number with #' => ['#{number}'],
        'phone with dash' => ['01812-556'],
        'name' => ['nusrat'],
    ]);

    it('filters by date range in Bangladesh days', function () {
        $this->travelTo(now()->setTimezone('Asia/Dhaka')->setTime(12, 0)->utc());
        $today = Order::factory()->create();
        $lastWeek = Order::factory()->create(['placed_at' => now()->subDays(10)]);

        actingAsAdmin()->get(route('admin.orders.index', ['date' => 'today']))
            ->assertSee($today->order_number)
            ->assertDontSee($lastWeek->order_number);

        actingAsAdmin()->get(route('admin.orders.index', ['date' => '30d']))
            ->assertSee($lastWeek->order_number);
    });

    it('shows an empty state', function () {
        actingAsAdmin()->get(route('admin.orders.index'))->assertSee('No orders here');
    });

    it('keeps customers out', function () {
        $order = Order::factory()->create();

        $this->actingAs($this->customer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($this->customer)->post(route('admin.orders.status', $order), ['status' => 'confirmed'])->assertForbidden();
    });

    it('shows the pending order count in the sidebar', function () {
        Order::factory()->count(3)->create();
        Order::factory()->confirmed()->create();

        actingAsAdmin()->get(route('admin.dashboard'))
            ->assertSee('<span class="nav-count">3</span>', escape: false)
            ->assertSee('3 new orders');
    });
});

describe('detail', function () {
    it('shows items, totals, customer and history with who changed it', function () {
        $order = placedOrder($this->customer, [sellableVariant(['sku' => 'NS-FAN-1'], ['name' => 'Ceiling Fan'])->id => 2]);
        changeStatus($order, 'confirmed', ['note' => 'Phone call']);

        $this->actingAs($this->staff)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Ceiling Fan')
            ->assertSee('NS-FAN-1')
            ->assertSee('1 order · since', escape: false)
            ->assertSee('by customer')
            ->assertSee('by Karim Staff · Phone call')
            ->assertSee('Mark as Paid');
    });

    it('returns 404 for an unknown order number', function () {
        actingAsAdmin()->get('/admin/orders/ORD-1')->assertNotFound();
    });

    it('saves the internal note', function () {
        $order = Order::factory()->create();

        $this->actingAs($this->staff)->put(route('admin.orders.note', $order), ['admin_note' => 'Call after 5 PM'])
            ->assertRedirect(route('admin.orders.show', $order));

        expect($order->fresh()->admin_note)->toBe('Call after 5 PM');
    });
});

describe('status changes', function () {
    it('moves along the allowed path and records each change', function () {
        $placedAt = now()->subDays(2)->startOfSecond();
        $order = Order::factory()->create(['placed_at' => $placedAt]);

        changeStatus($order, 'confirmed', ['note' => 'Phone call'])->assertSessionHas('success', 'Order marked as Confirmed.');
        changeStatus($order, 'processing');
        changeStatus($order, 'shipped', ['courier_name' => 'Pathao', 'tracking_code' => 'PTH-88213']);
        changeStatus($order, 'delivered');

        $order->refresh();
        $history = $order->statusHistory()->orderBy('id')->get();
        expect($order->status)->toBe(OrderStatus::Delivered)
            ->and($order->courier_name)->toBe('Pathao')
            ->and($order->tracking_code)->toBe('PTH-88213')
            ->and($order->delivered_at)->not->toBeNull()
            ->and($order->placed_at->equalTo($placedAt))->toBeTrue()
            ->and($history->pluck('to_status')->all())->toBe([OrderStatus::Confirmed, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered])
            ->and($history->first()->from_status)->toBe(OrderStatus::Pending)
            ->and($history->first()->user_id)->toBe($this->staff->id)
            ->and($history->first()->note)->toBe('Phone call');
    });

    it('refuses a move that skips or reverses steps', function (string $from, string $to) {
        $order = Order::factory()->status(OrderStatus::from($from))->create();

        changeStatus($order, $to)->assertSessionHas('error');

        expect($order->fresh()->status->value)->toBe($from)
            ->and($order->statusHistory()->count())->toBe(0);
    })->with([
        'pending to shipped' => ['pending', 'shipped'],
        'shipped to cancelled' => ['shipped', 'cancelled'],
        'delivered to pending' => ['delivered', 'pending'],
        'cancelled to confirmed' => ['cancelled', 'confirmed'],
        'returned to delivered' => ['returned', 'delivered'],
    ]);

    it('needs a courier before shipping', function () {
        $order = Order::factory()->status(OrderStatus::Processing)->create();

        changeStatus($order, 'shipped')->assertSessionHas('error', 'Enter the courier name before marking the order as shipped.');

        expect($order->fresh()->status)->toBe(OrderStatus::Processing);
    });

    it('validates the status', function () {
        $order = Order::factory()->create();

        changeStatus($order, 'lost')->assertSessionHasErrors('status');
    });

    it('shows the courier to the customer', function () {
        $order = Order::factory()->shipped()->create(['user_id' => $this->customer->id, 'tracking_code' => 'PTH-1']);

        $this->actingAs($this->customer)->get(route('account.orders.show', $order->order_number))
            ->assertSee('Pathao · PTH-1');
    });
});

describe('cancel and return', function () {
    it('cancels: stock back, coupon released, payment cancelled, event', function () {
        Event::fake([OrderCancelled::class]);
        $variant = sellableVariant(['stock_quantity' => 10]);
        $coupon = Coupon::factory()->create(['code' => 'SAVE50', 'value' => 5000]);
        $order = placedOrder($this->customer, [$variant->id => 3], 'SAVE50');
        $order->update(['status' => OrderStatus::Processing]);

        changeStatus($order, 'cancelled');

        $order->refresh();
        expect($order->status)->toBe(OrderStatus::Cancelled)
            ->and($order->cancelled_at)->not->toBeNull()
            ->and($order->payment_status)->toBe(PaymentStatus::Cancelled)
            ->and($order->payments()->sole()->status)->toBe(PaymentStatus::Cancelled)
            ->and($variant->fresh()->stock_quantity)->toBe(10)
            ->and($coupon->fresh()->used_count)->toBe(0);
        Event::assertDispatched(OrderCancelled::class);
        $this->get(route('admin.orders.show', $order))->assertSee('Not collected')->assertDontSee('Amount due');
    });

    it('refunds a paid order that is cancelled', function () {
        $order = placedOrder($this->customer, [sellableVariant()->id => 1]);
        $this->actingAs($this->staff)->post(route('admin.orders.mark-paid', $order));

        changeStatus($order, 'cancelled');

        $order->refresh();
        expect($order->payment_status)->toBe(PaymentStatus::Refunded)
            ->and($order->payments()->where('status', PaymentStatus::Refunded)->sole()->amount)->toBe($order->total);
        $this->get(route('admin.orders.show', $order))->assertSee('Amount refunded');
    });

    it('returns a delivered order: stock back as returned, coupon use kept', function () {
        $variant = sellableVariant(['stock_quantity' => 10]);
        $coupon = Coupon::factory()->create(['code' => 'SAVE50', 'value' => 5000]);
        $order = placedOrder($this->customer, [$variant->id => 2], 'SAVE50');
        $order->update(['status' => OrderStatus::Delivered]);

        changeStatus($order, 'returned', ['note' => 'Wrong size']);

        expect($order->fresh()->status)->toBe(OrderStatus::Returned)
            ->and($variant->fresh()->stock_quantity)->toBe(10)
            ->and(StockMovement::query()->where('reason', StockMovementReason::Returned)->sole()->quantity_change)->toBe(2)
            ->and($coupon->fresh()->used_count)->toBe(1);
    });
});

describe('mark paid', function () {
    it('marks the COD payment as received', function () {
        $order = placedOrder($this->customer, [sellableVariant()->id => 1]);

        $this->actingAs($this->staff)->post(route('admin.orders.mark-paid', $order))
            ->assertSessionHas('success', 'Payment marked as received.');

        $payment = Payment::query()->where('order_id', $order->id)->sole();
        expect($order->fresh()->payment_status)->toBe(PaymentStatus::Paid)
            ->and($payment->status)->toBe(PaymentStatus::Paid)
            ->and($payment->paid_at)->not->toBeNull();
    });

    it('refuses twice or on a cancelled order', function () {
        $paid = Order::factory()->paid()->create();
        $cancelled = Order::factory()->cancelled()->create();

        $this->actingAs($this->staff)->post(route('admin.orders.mark-paid', $paid))->assertSessionHas('error');
        $this->actingAs($this->staff)->post(route('admin.orders.mark-paid', $cancelled))->assertSessionHas('error');

        expect($cancelled->fresh()->payment_status)->toBe(PaymentStatus::Cancelled);
    });
});
