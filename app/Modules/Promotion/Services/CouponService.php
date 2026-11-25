<?php

namespace App\Modules\Promotion\Services;

use App\Modules\Promotion\Data\CouponData;
use App\Modules\Promotion\Data\CouponDiscount;
use App\Modules\Promotion\Enums\CouponType;
use App\Modules\Promotion\Exceptions\CouponNotApplicableException;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Promotion\Repositories\CouponRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CouponService
{
    public function __construct(private readonly CouponRepository $couponRepository) {}

    /**
     * @return LengthAwarePaginator<int, Coupon>
     */
    public function paginateForAdmin(): LengthAwarePaginator
    {
        return $this->couponRepository->paginateForAdmin();
    }

    /**
     * Check a code against an order subtotal (paisa). Checkout passes how often this customer already used it.
     *
     * @throws CouponNotApplicableException
     */
    public function apply(string $code, int $subtotal, int $customerUsageCount = 0): CouponDiscount
    {
        $coupon = $this->couponRepository->findByCode($code);
        $now = now();

        match (true) {
            $coupon === null || ! $coupon->is_active => throw CouponNotApplicableException::invalidCode(),
            ! $coupon->hasStartedAt($now) => throw CouponNotApplicableException::notStarted(),
            $coupon->hasExpiredAt($now) => throw CouponNotApplicableException::expired(),
            $coupon->min_order_amount !== null && $subtotal < $coupon->min_order_amount => throw CouponNotApplicableException::belowMinimum($coupon->min_order_amount),
            $coupon->isUsedUp() => throw CouponNotApplicableException::usedUp(),
            $coupon->usage_limit_per_user !== null && $customerUsageCount >= $coupon->usage_limit_per_user => throw CouponNotApplicableException::alreadyUsedByCustomer(),
            default => null,
        };

        return new CouponDiscount($coupon->id, $coupon->code, $this->discountFor($coupon, $subtotal));
    }

    /**
     * Counts one use when an order is placed (F-023). Atomic against the usage limit, so two orders
     * cannot both take the last use. Run it inside the order's transaction.
     *
     * @throws CouponNotApplicableException when the limit was reached meanwhile
     */
    public function recordUse(int $couponId): void
    {
        if (! $this->couponRepository->incrementUsageWithinLimit($couponId)) {
            throw CouponNotApplicableException::usedUp();
        }
    }

    /**
     * Gives a use back when its order is cancelled (F-024). Never below 0.
     */
    public function releaseUse(int $couponId): void
    {
        $this->couponRepository->decrementUsage($couponId);
    }

    /**
     * Fixed: min(value, subtotal). Percent: floor(subtotal × value / 100), capped by max discount and the subtotal.
     */
    public function discountFor(Coupon $coupon, int $subtotal): int
    {
        $discount = match ($coupon->type) {
            CouponType::Fixed => $coupon->value,
            CouponType::Percent => intdiv($subtotal * $coupon->value, 100),
        };

        if ($coupon->max_discount_amount !== null) {
            $discount = min($discount, $coupon->max_discount_amount);
        }

        return max(0, min($discount, $subtotal));
    }

    public function create(CouponData $data): Coupon
    {
        return $this->couponRepository->create($this->attributesFrom($data));
    }

    public function update(Coupon $coupon, CouponData $data): Coupon
    {
        return $this->couponRepository->update($coupon, $this->attributesFrom($data));
    }

    public function delete(Coupon $coupon): void
    {
        $this->couponRepository->delete($coupon);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(CouponData $data): array
    {
        return [
            'code' => $data->code,
            'type' => $data->type,
            'value' => $data->value,
            'min_order_amount' => $data->minOrderAmount,
            'max_discount_amount' => $data->type === CouponType::Percent ? $data->maxDiscountAmount : null,
            'usage_limit' => $data->usageLimit,
            'usage_limit_per_user' => $data->usageLimitPerUser,
            'starts_at' => $data->startsAt,
            'expires_at' => $data->expiresAt,
            'is_active' => $data->isActive,
        ];
    }
}
