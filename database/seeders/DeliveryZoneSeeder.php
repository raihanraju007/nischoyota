<?php

namespace Database\Seeders;

use App\Modules\Location\Models\DeliveryZone;
use Illuminate\Database\Seeder;

/**
 * Zones from the admin Settings prototype. Charges in paisa.
 */
class DeliveryZoneSeeder extends Seeder
{
    public const INSIDE_DHAKA = 'Inside Dhaka';

    public const DHAKA_SUBURBS = 'Dhaka Suburbs';

    public const OUTSIDE_DHAKA = 'Outside Dhaka';

    public function run(): void
    {
        $zones = [
            [self::INSIDE_DHAKA, 6000, 1, 2],
            [self::DHAKA_SUBURBS, 10000, 2, 3],
            [self::OUTSIDE_DHAKA, 12000, 3, 5],
        ];

        foreach ($zones as $sortOrder => [$name, $charge, $minDays, $maxDays]) {
            DeliveryZone::query()->create([
                'name' => $name,
                'charge' => $charge,
                'min_days' => $minDays,
                'max_days' => $maxDays,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ]);
        }
    }
}
