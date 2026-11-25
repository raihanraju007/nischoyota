<?php

use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Upazila;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function zoneInput(array $overrides = []): array
{
    return ['name' => 'Inside Dhaka', 'charge' => '60', 'min_days' => 1, 'max_days' => 2, 'is_active' => '1', 'sort_order' => 0, ...$overrides];
}

describe('zones', function () {
    it('lists zones with charge, time and areas', function () {
        $zone = DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 6000, 'min_days' => 1, 'max_days' => 2]);
        District::factory()->count(2)->create(['delivery_zone_id' => $zone->id]);

        actingAsAdmin()->get(route('admin.delivery-zones.index'))
            ->assertOk()
            ->assertSee('Inside Dhaka')
            ->assertSee('৳ 60')
            ->assertSee('1–2 days')
            ->assertSee('2 districts');
    });

    it('creates a zone with the charge in paisa', function () {
        actingAsAdmin()->post(route('admin.delivery-zones.store'), zoneInput(['charge' => '99.50']))
            ->assertRedirect(route('admin.delivery-zones.index'))
            ->assertSessionHas('success');

        expect(DeliveryZone::query()->sole()->charge)->toBe(9950);
    });

    it('validates a zone', function (array $input, string $errorField) {
        DeliveryZone::factory()->create(['name' => 'Taken']);

        actingAsAdmin()->post(route('admin.delivery-zones.store'), zoneInput($input))->assertSessionHasErrors($errorField);
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'name taken' => [['name' => 'taken'], 'name'],
        'negative charge' => [['charge' => '-1'], 'charge'],
        'charge too high' => [['charge' => '10001'], 'charge'],
        'zero days' => [['min_days' => 0], 'min_days'],
        'latest before earliest' => [['min_days' => 3, 'max_days' => 2], 'max_days'],
    ]);

    it('updates a zone', function () {
        $zone = DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 6000]);
        DeliveryZone::factory()->create();

        actingAsAdmin()->put(route('admin.delivery-zones.update', $zone), zoneInput(['charge' => '70', 'is_active' => '0']))
            ->assertSessionHasNoErrors();

        expect($zone->fresh())->charge->toBe(7000)->is_active->toBeFalse();
    });

    it('keeps at least one zone active', function () {
        $zone = DeliveryZone::factory()->create(['name' => 'Inside Dhaka']);
        DeliveryZone::factory()->create(['is_active' => false]);

        actingAsAdmin()->put(route('admin.delivery-zones.update', $zone), zoneInput(['is_active' => '0']))
            ->assertSessionHasErrors(['is_active' => 'At least one delivery zone must stay active.']);
    });

    it('does not delete a zone in use', function () {
        $zone = DeliveryZone::factory()->create();
        DeliveryZone::factory()->create();
        Upazila::factory()->create(['delivery_zone_id' => $zone->id]);

        actingAsAdmin()->delete(route('admin.delivery-zones.destroy', $zone))
            ->assertSessionHas('error', 'This zone is used by districts or thanas. Move them to another zone first.');

        $this->assertModelExists($zone);
    });

    it('deletes an unused zone', function () {
        $zone = DeliveryZone::factory()->create();
        DeliveryZone::factory()->create();

        actingAsAdmin()->delete(route('admin.delivery-zones.destroy', $zone))->assertSessionHas('success');

        $this->assertModelMissing($zone);
    });
});

describe('areas', function () {
    it('saves the zone of each district', function () {
        $outside = DeliveryZone::factory()->create(['name' => 'Outside Dhaka']);
        $suburbs = DeliveryZone::factory()->create(['name' => 'Dhaka Suburbs']);
        $district = District::factory()->create(['name' => 'Munshiganj', 'delivery_zone_id' => $outside->id]);

        actingAsAdmin()->get(route('admin.delivery-areas.index'))->assertOk()->assertSee('Munshiganj');

        actingAsAdmin()->put(route('admin.delivery-areas.update'), ['districts' => [$district->id => $suburbs->id]])
            ->assertRedirect(route('admin.delivery-areas.index'));

        expect($district->fresh()->delivery_zone_id)->toBe($suburbs->id);
    });

    it('saves and clears thana overrides', function () {
        $inside = DeliveryZone::factory()->create(['name' => 'Inside Dhaka']);
        $district = District::factory()->create();
        $upazila = Upazila::factory()->for($district)->create(['name' => 'Savar']);

        actingAsAdmin()->get(route('admin.delivery-areas.show', $district))->assertOk()->assertSee('Savar')->assertSee('Same as district');

        actingAsAdmin()->put(route('admin.delivery-areas.update-district', $district), ['upazilas' => [$upazila->id => $inside->id]]);
        expect($upazila->fresh()->delivery_zone_id)->toBe($inside->id);

        actingAsAdmin()->put(route('admin.delivery-areas.update-district', $district), ['upazilas' => [$upazila->id => '']]);
        expect($upazila->fresh()->delivery_zone_id)->toBeNull();
    });

    it('ignores upazilas of another district', function () {
        $zone = DeliveryZone::factory()->create();
        $district = District::factory()->create();
        $foreign = Upazila::factory()->create();

        actingAsAdmin()->put(route('admin.delivery-areas.update-district', $district), ['upazilas' => [$foreign->id => $zone->id]]);

        expect($foreign->fresh()->delivery_zone_id)->toBeNull();
    });

    it('rejects an unknown zone', function () {
        $district = District::factory()->create();

        actingAsAdmin()->put(route('admin.delivery-areas.update'), ['districts' => [$district->id => 999]])
            ->assertSessionHasErrors("districts.{$district->id}");
    });
});

describe('authorization', function () {
    it('is admin only', function (string $method, string $routeName, bool $needsModel) {
        $zone = DeliveryZone::factory()->create();
        $district = District::factory()->create(['delivery_zone_id' => $zone->id]);
        $parameter = str_contains($routeName, 'delivery-zones') ? $zone : $district;
        $url = $needsModel ? route($routeName, $parameter) : route($routeName);

        $this->{$method}($url)->assertRedirect(route('admin.login'));
        actingAsCustomer()->{$method}($url)->assertForbidden();
        actingAsStaff()->{$method}($url)->assertForbidden();
    })->with([
        ['get', 'admin.delivery-zones.index', false],
        ['post', 'admin.delivery-zones.store', false],
        ['put', 'admin.delivery-zones.update', true],
        ['delete', 'admin.delivery-zones.destroy', true],
        ['get', 'admin.delivery-areas.index', false],
        ['put', 'admin.delivery-areas.update', false],
        ['get', 'admin.delivery-areas.show', true],
        ['put', 'admin.delivery-areas.update-district', true],
    ]);

    it('shows the Delivery link and settings panel to admins only', function () {
        actingAsAdmin()->get(route('admin.settings'))->assertSee(route('admin.delivery-zones.index'));
        actingAsStaff()->get(route('admin.settings'))->assertOk()->assertDontSee(route('admin.delivery-zones.index'));
    });
});
