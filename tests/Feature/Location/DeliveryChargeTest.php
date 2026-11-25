<?php

use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;
use App\Modules\Location\Services\DeliveryChargeService;
use Database\Seeders\DeliveryZoneSeeder;
use Database\Seeders\LocationSeeder;

beforeEach(function () {
    config(['shop.free_delivery_threshold' => 200000]);
    $this->service = app(DeliveryChargeService::class);
});

it('uses the district zone when the upazila has no override', function () {
    $zone = DeliveryZone::factory()->create(['name' => 'Dhaka Suburbs', 'charge' => 10000, 'min_days' => 2, 'max_days' => 3]);
    $upazila = Upazila::factory()->for(District::factory()->for($zone, 'deliveryZone'))->create();

    $quote = $this->service->quoteFor($upazila->id, subtotal: 50000);

    expect($quote->zoneName)->toBe('Dhaka Suburbs')
        ->and($quote->charge)->toBe(10000)
        ->and($quote->isFree)->toBeFalse()
        ->and($quote->minDays)->toBe(2)
        ->and($quote->maxDays)->toBe(3);
});

it('uses the upazila override first', function () {
    $insideDhaka = DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 6000]);
    $upazila = Upazila::factory()->create(['delivery_zone_id' => $insideDhaka->id]);

    expect($this->service->quoteFor($upazila->id, 50000)->zoneName)->toBe('Inside Dhaka');
});

it('delivers free from the threshold', function (int $subtotal, bool $isFree, int $charge) {
    $upazila = Upazila::factory()->create(['delivery_zone_id' => DeliveryZone::factory()->create(['charge' => 12000])->id]);

    $quote = $this->service->quoteFor($upazila->id, $subtotal);

    expect($quote->isFree)->toBe($isFree)->and($quote->charge)->toBe($charge);
})->with([
    'just below' => [199999, false, 12000],
    'exactly the threshold' => [200000, true, 0],
    'above' => [500000, true, 0],
]);

it('returns no quote for an inactive zone or unknown area', function () {
    $upazila = Upazila::factory()->create(['delivery_zone_id' => DeliveryZone::factory()->create(['is_active' => false])->id]);

    expect($this->service->quoteFor($upazila->id, 1000))->toBeNull()
        ->and($this->service->quoteFor(999999, 1000))->toBeNull();
});

describe('seeded data', function () {
    beforeEach(function () {
        $this->seed([DeliveryZoneSeeder::class, LocationSeeder::class]);
    });

    it('seeds Bangladesh with Dhaka city thanas and official spellings', function () {
        expect(District::query()->count())->toBe(64)
            ->and(Upazila::query()->count())->toBe(544)
            ->and(District::query()->pluck('name')->all())->toContain('Cumilla', "Cox's Bazar", 'Barishal', 'Chattogram')
            ->and(Upazila::query()->where('name', 'Dhanmondi')->value('name_bn'))->toBe('ধানমন্ডি');
    });

    it('prices the default zones', function (string $upazila, string $expectedZone, int $expectedCharge) {
        $quote = $this->service->quoteFor(Upazila::query()->where('name', $upazila)->value('id'), 100000);

        expect($quote->zoneName)->toBe($expectedZone)->and($quote->charge)->toBe($expectedCharge);
    })->with([
        'Dhaka city thana' => ['Dhanmondi', 'Inside Dhaka', 6000],
        'Dhaka district upazila' => ['Savar', 'Dhaka Suburbs', 10000],
        'Gazipur' => ['Gazipur Sadar', 'Dhaka Suburbs', 10000],
        'outside Dhaka' => ['Debidwar', 'Outside Dhaka', 12000],
    ]);
});
