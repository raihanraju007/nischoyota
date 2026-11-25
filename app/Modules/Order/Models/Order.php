<?php

namespace App\Modules\Order\Models;

use App\Modules\Account\Models\User;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Enums\PaymentMethod;
use App\Modules\Order\Enums\PaymentStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A placed order with snapshots of customer, address and amounts (ADR-007, D6). Money is paisa.
 * user_id is null for guest orders (D3).
 */
#[Fillable([
    'order_number', 'user_id', 'status', 'customer_name', 'customer_phone', 'customer_email',
    'division_id', 'division_name', 'district_id', 'district_name', 'upazila_id', 'upazila_name',
    'area', 'address_line', 'postal_code', 'delivery_zone_id', 'delivery_zone_name', 'delivery_min_days', 'delivery_max_days',
    'subtotal', 'discount_total', 'delivery_charge', 'total', 'coupon_id', 'coupon_code',
    'payment_method', 'payment_status', 'placed_at',
    'courier_name', 'tracking_code', 'admin_note', 'delivered_at', 'cancelled_at',
])]
#[UseFactory(OrderFactory::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const NUMBER_PREFIX = 'ORD-';

    public const NUMBER_OFFSET = 10000;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'integer',
            'discount_total' => 'integer',
            'delivery_charge' => 'integer',
            'total' => 'integer',
            'delivery_min_days' => 'integer',
            'delivery_max_days' => 'integer',
            'placed_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public static function numberFor(int $orderId): string
    {
        return self::NUMBER_PREFIX.(self::NUMBER_OFFSET + $orderId);
    }

    /**
     * "18–20 Aug 2026": placed date + the zone's min / max days, in Bangladesh time.
     */
    public function estimatedDeliveryLabel(): string
    {
        $placedAt = $this->placed_at->timezone(config('shop.timezone'));
        $earliest = $placedAt->copy()->addDays($this->delivery_min_days);
        $latest = $placedAt->copy()->addDays($this->delivery_max_days);

        return match (true) {
            $earliest->isSameDay($latest) => $latest->format('d M Y'),
            $earliest->isSameMonth($latest) => $earliest->format('d').'–'.$latest->format('d M Y'),
            default => $earliest->format('d M').' – '.$latest->format('d M Y'),
        };
    }

    /**
     * "2–5 business days" from the delivery zone snapshot.
     */
    public function deliveryTimeLabel(): string
    {
        return $this->delivery_min_days === $this->delivery_max_days
            ? trans_choice(':count business day|:count business days', $this->delivery_max_days)
            : __(':min–:max business days', ['min' => $this->delivery_min_days, 'max' => $this->delivery_max_days]);
    }
}
