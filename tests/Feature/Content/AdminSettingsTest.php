<?php

use App\Modules\Catalog\Models\Product;
use App\Modules\Content\Models\Setting;
use App\Modules\Content\Services\SettingService;
use App\Modules\Location\Models\DeliveryZone;
use App\Modules\Location\Models\Upazila;
use App\Modules\Location\Services\DeliveryChargeService;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function settingsInput(array $overrides = []): array
{
    return [
        'store_name' => 'Nischoyota',
        'support_phone' => '+880 9612-345678',
        'support_email' => 'support@nischoyota.com',
        'store_address' => 'House 12, Road 5, Dhanmondi',
        'support_hours' => 'Everyday, 9 AM – 10 PM',
        'free_delivery_threshold' => '2000',
        'low_stock_threshold' => 5,
        ...$overrides,
    ];
}

it('shows the defaults before anything is saved', function () {
    actingAsAdmin()->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('value="Nischoyota"', escape: false)
        ->assertSee('value="info@nischoyota.com"', escape: false)
        ->assertSee('value="2000"', escape: false)
        ->assertSee('Coming after launch');
});

it('saves the settings and shows them', function () {
    actingAsAdmin()->put(route('admin.settings.update'), settingsInput([
        'store_name' => 'Nischoyota BD',
        'support_email' => 'Help@Nischoyota.com.bd',
        'free_delivery_threshold' => '1500.50',
        'low_stock_threshold' => 10,
    ]))->assertRedirect(route('admin.settings'))->assertSessionHas('success', 'Store settings saved.');

    $settings = app(SettingService::class)->current();
    expect($settings->storeName)->toBe('Nischoyota BD')
        ->and($settings->supportEmail)->toBe('help@nischoyota.com')
        ->and($settings->freeDeliveryThreshold)->toBe(150050)
        ->and($settings->lowStockThreshold)->toBe(10);

    actingAsAdmin()->get(route('admin.settings'))->assertSee('value="Nischoyota BD"', escape: false);
});

it('turns free delivery off when the threshold is empty', function () {
    actingAsAdmin()->put(route('admin.settings.update'), settingsInput(['free_delivery_threshold' => '']));

    expect(app(SettingService::class)->current()->freeDeliveryThreshold)->toBeNull();
});

it('clears the cache when saving', function () {
    expect(app(SettingService::class)->current()->storeName)->toBe('Nischoyota');

    actingAsAdmin()->put(route('admin.settings.update'), settingsInput(['store_name' => 'New Name']));

    expect(app(SettingService::class)->current()->storeName)->toBe('New Name')
        ->and(Setting::query()->count())->toBe(7);
});

it('validates the settings', function (array $input, string $errorField) {
    actingAsAdmin()->put(route('admin.settings.update'), settingsInput($input))->assertSessionHasErrors($errorField);
})->with([
    'name missing' => [['store_name' => ''], 'store_name'],
    'phone with letters' => [['support_phone' => 'call us'], 'support_phone'],
    'invalid email' => [['support_email' => 'not-an-email'], 'support_email'],
    'negative threshold' => [['free_delivery_threshold' => '-1'], 'free_delivery_threshold'],
    'low stock missing' => [['low_stock_threshold' => ''], 'low_stock_threshold'],
    'low stock too high' => [['low_stock_threshold' => 1001], 'low_stock_threshold'],
]);

it('shows staff only the password form and forbids saving', function () {
    actingAsStaff()->get(route('admin.settings'))
        ->assertOk()
        ->assertSee('Change Password')
        ->assertDontSee('name="store_name"', escape: false);

    actingAsStaff()->put(route('admin.settings.update'), settingsInput())->assertForbidden();
});

it('keeps guests and customers out', function () {
    $this->get(route('admin.settings'))->assertRedirect(route('admin.login'));
    actingAsCustomer()->get(route('admin.settings'))->assertForbidden();
});

describe('used by other modules', function () {
    it('uses the saved free-delivery threshold, or none', function () {
        $upazila = Upazila::factory()->create(['delivery_zone_id' => DeliveryZone::factory()->create(['charge' => 12000])->id]);
        $service = app(DeliveryChargeService::class);

        actingAsAdmin()->put(route('admin.settings.update'), settingsInput(['free_delivery_threshold' => '500']));
        expect($service->quoteFor($upazila->id, 50000)->isFree)->toBeTrue();

        actingAsAdmin()->put(route('admin.settings.update'), settingsInput(['free_delivery_threshold' => '']));
        expect($service->quoteFor($upazila->id, 99_999_999)->isFree)->toBeFalse();
    });

    it('uses the saved low-stock threshold in Inventory', function () {
        Product::factory()->withDefaultVariant(['stock_quantity' => 8])->create(['name' => 'Kettle']);

        actingAsAdmin()->get(route('admin.inventory.index', ['tab' => 'low']))->assertDontSee('Kettle');

        actingAsAdmin()->put(route('admin.settings.update'), settingsInput(['low_stock_threshold' => 10]));

        actingAsAdmin()->get(route('admin.inventory.index', ['tab' => 'low']))->assertSee('Kettle');
    });
});
