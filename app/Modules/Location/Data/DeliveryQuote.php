<?php

namespace App\Modules\Location\Data;

/**
 * Delivery charge and time for one address. charge is paisa; 0 when $isFree.
 */
final readonly class DeliveryQuote
{
    public function __construct(
        public int $deliveryZoneId,
        public string $zoneName,
        public int $charge,
        public bool $isFree,
        public int $minDays,
        public int $maxDays,
    ) {}
}
