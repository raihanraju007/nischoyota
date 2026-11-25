<?php

namespace App\Modules\Promotion\Repositories;

use App\Modules\Promotion\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class CouponRepository
{
    private const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, Coupon>
     */
    public function paginateForAdmin(): LengthAwarePaginator
    {
        return Coupon::query()->latest('id')->paginate(self::PER_PAGE);
    }

    /**
     * One conditional UPDATE: false when the coupon is used up.
     */
    public function incrementUsageWithinLimit(int $couponId): bool
    {
        return Coupon::query()
            ->whereKey($couponId)
            ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
            ->increment('used_count') === 1;
    }

    public function decrementUsage(int $couponId): void
    {
        Coupon::query()->whereKey($couponId)->where('used_count', '>', 0)->decrement('used_count');
    }

    public function findByCode(string $code): ?Coupon
    {
        return Coupon::query()->where('code', mb_strtoupper(trim($code)))->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Coupon
    {
        return Coupon::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Coupon $coupon, array $attributes): Coupon
    {
        $coupon->update($attributes);

        return $coupon;
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }
}
