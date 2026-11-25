<?php

namespace Database\Seeders;

use App\Modules\Promotion\Enums\CouponType;
use App\Modules\Promotion\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * Starter coupons for Nischoyota. Money in paisa.
 */
class CouponSeeder extends Seeder
{
    public function run(): void
    {
        $coupons = [
            ['code' => 'GHEE50', 'type' => CouponType::Fixed, 'value' => 5000, 'min_order_amount' => 80000, 'usage_limit' => 200, 'expires_at' => now()->addMonth()],
            ['code' => 'HONEY10', 'type' => CouponType::Percent, 'value' => 10, 'min_order_amount' => 100000, 'max_discount_amount' => 20000, 'expires_at' => now()->addWeeks(2)],
            ['code' => 'WELCOME100', 'type' => CouponType::Fixed, 'value' => 10000, 'min_order_amount' => 80000, 'usage_limit_per_user' => 1],
            ['code' => 'EID15', 'type' => CouponType::Percent, 'value' => 15, 'min_order_amount' => 150000, 'max_discount_amount' => 80000, 'usage_limit' => 150, 'expires_at' => now()->subWeek()],
        ];

        foreach ($coupons as $coupon) {
            Coupon::query()->create([...$coupon, 'is_active' => true]);
        }
    }
}
