<?php

namespace App\Modules\Promotion\Models;

use App\Modules\Promotion\Enums\CouponStatus;
use App\Modules\Promotion\Enums\CouponType;
use App\Support\Money;
use Carbon\CarbonInterface;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * value: paisa for fixed coupons, whole percent for percent coupons. Other money columns are paisa (D4).
 */
#[Fillable([
    'code', 'type', 'value', 'min_order_amount', 'max_discount_amount',
    'usage_limit', 'usage_limit_per_user', 'starts_at', 'expires_at', 'is_active',
])]
#[UseFactory(CouponFactory::class)]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'max_discount_amount' => 'integer',
            'usage_limit' => 'integer',
            'usage_limit_per_user' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function hasStartedAt(CarbonInterface $moment): bool
    {
        return $this->starts_at === null || $this->starts_at->lessThanOrEqualTo($moment);
    }

    public function hasExpiredAt(CarbonInterface $moment): bool
    {
        return $this->expires_at !== null && $this->expires_at->lessThan($moment);
    }

    public function isUsedUp(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    public function statusAt(CarbonInterface $moment): CouponStatus
    {
        return match (true) {
            ! $this->is_active => CouponStatus::Inactive,
            $this->hasExpiredAt($moment) => CouponStatus::Expired,
            $this->isUsedUp() => CouponStatus::UsedUp,
            ! $this->hasStartedAt($moment) => CouponStatus::Scheduled,
            default => CouponStatus::Active,
        };
    }

    /**
     * "৳ 120 off" / "10% off (max ৳ 500)".
     */
    public function discountLabel(): string
    {
        if ($this->type === CouponType::Fixed) {
            return __(':amount off', ['amount' => Money::format($this->value)]);
        }

        return $this->max_discount_amount === null
            ? __(':percent% off', ['percent' => $this->value])
            : __(':percent% off (max :max)', ['percent' => $this->value, 'max' => Money::format($this->max_discount_amount)]);
    }
}
