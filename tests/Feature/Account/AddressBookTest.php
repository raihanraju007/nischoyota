<?php

use App\Modules\Account\Models\Address;
use App\Modules\Account\Models\User;
use App\Modules\Account\Services\AddressService;
use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use App\Modules\Location\Models\Upazila;

beforeEach(function () {
    $this->customer = User::factory()->create(['name' => 'Rahim Uddin', 'phone' => '01712345678']);
    $this->division = Division::factory()->create(['name' => 'Dhaka']);
    $this->district = District::factory()->for($this->division)->create(['name' => 'Dhaka']);
    $this->upazila = Upazila::factory()->for($this->district)->create(['name' => 'Dhanmondi']);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function addressInput(array $overrides = []): array
{
    return [
        'label' => 'home',
        'recipient_name' => 'Rahim Uddin',
        'phone' => '01712-345678',
        'division_id' => test()->division->id,
        'district_id' => test()->district->id,
        'upazila_id' => test()->upazila->id,
        'area' => 'Dhanmondi 27',
        'address_line' => 'House 12, Road 5',
        'postal_code' => '1205',
        ...$overrides,
    ];
}

it('adds the first address as the default', function () {
    $this->actingAs($this->customer)->post(route('account.addresses.store'), addressInput())
        ->assertRedirect(route('account.addresses'))
        ->assertSessionHas('success');

    $address = $this->customer->addresses()->sole();
    expect($address->is_default)->toBeTrue()
        ->and($address->phone)->toBe('01712345678')
        ->and($address->displayLines())->toBe(['House 12, Road 5, Dhanmondi 27', 'Dhanmondi, Dhaka 1205']);

    $this->actingAs($this->customer)->get(route('account.addresses'))->assertSee('House 12, Road 5, Dhanmondi 27')->assertSee('Default');
});

it('adds later addresses as non-default', function () {
    Address::factory()->for($this->customer)->default()->create();

    $this->actingAs($this->customer)->post(route('account.addresses.store'), addressInput(['label' => 'office']));

    expect($this->customer->addresses()->where('is_default', true)->count())->toBe(1);
});

it('validates the address and the location chain', function (array $input, string $errorField) {
    $otherDistrict = District::factory()->create();
    $otherUpazila = Upazila::factory()->create();
    $input = array_map(fn ($value) => match ($value) {
        'OTHER_DISTRICT' => $otherDistrict->id,
        'OTHER_UPAZILA' => $otherUpazila->id,
        default => $value,
    }, $input);

    $this->actingAs($this->customer)->post(route('account.addresses.store'), addressInput($input))->assertSessionHasErrors($errorField);
})->with([
    'label unknown' => [['label' => 'farm'], 'label'],
    'name missing' => [['recipient_name' => ''], 'recipient_name'],
    'phone invalid' => [['phone' => '12345'], 'phone'],
    'district of another division' => [['district_id' => 'OTHER_DISTRICT'], 'district_id'],
    'upazila of another district' => [['upazila_id' => 'OTHER_UPAZILA'], 'upazila_id'],
    'address missing' => [['address_line' => ''], 'address_line'],
    'postal code letters' => [['postal_code' => '12AB'], 'postal_code'],
]);

it('stops at ten addresses', function () {
    Address::factory()->count(AddressService::MAX_ADDRESSES)->for($this->customer)->create();

    $this->actingAs($this->customer)->post(route('account.addresses.store'), addressInput())
        ->assertSessionHas('error', 'You can save up to 10 addresses. Delete one to add another.');

    $this->actingAs($this->customer)->get(route('account.addresses'))->assertDontSee('Add New Address');
});

it('edits an address and fills the dropdowns for its location', function () {
    $address = Address::factory()->for($this->customer)->create([
        'division_id' => $this->division->id, 'district_id' => $this->district->id, 'upazila_id' => $this->upazila->id,
    ]);

    $this->actingAs($this->customer)->get(route('account.addresses.edit', $address))
        ->assertOk()
        ->assertSee('>Dhanmondi</option>', escape: false)
        ->assertSee('value="'.$this->upazila->id.'"', escape: false);

    $this->actingAs($this->customer)->put(route('account.addresses.update', $address), addressInput(['address_line' => 'House 99']))
        ->assertRedirect(route('account.addresses'));

    expect($address->fresh()->address_line)->toBe('House 99');
});

it('moves the default and picks a new one when the default is deleted', function () {
    $first = Address::factory()->for($this->customer)->default()->create();
    $second = Address::factory()->for($this->customer)->create();
    $third = Address::factory()->for($this->customer)->create();

    $this->actingAs($this->customer)->post(route('account.addresses.default', $third))->assertSessionHas('success');
    expect($third->fresh()->is_default)->toBeTrue()->and($first->fresh()->is_default)->toBeFalse();

    $this->actingAs($this->customer)->delete(route('account.addresses.destroy', $third));
    $this->assertModelMissing($third);
    expect($first->fresh()->is_default)->toBeTrue()->and($second->fresh()->is_default)->toBeFalse();
});

it('hides other customers\' addresses (404)', function (string $method, string $routeName) {
    $foreign = Address::factory()->create();

    $this->actingAs($this->customer)->{$method}(route($routeName, $foreign), addressInput())->assertNotFound();

    $this->assertModelExists($foreign);
})->with([
    ['get', 'account.addresses.edit'],
    ['put', 'account.addresses.update'],
    ['delete', 'account.addresses.destroy'],
    ['post', 'account.addresses.default'],
]);

it('shows the create form with divisions and the customer\'s details', function () {
    $this->actingAs($this->customer)->get(route('account.addresses.create'))
        ->assertOk()
        ->assertSee('value="Rahim Uddin"', escape: false)
        ->assertSee('data-location-division', escape: false)
        ->assertSee(route('locations.districts', '__ID__'));
});

it('shows the default address on the dashboard', function () {
    Address::factory()->for($this->customer)->default()->create([
        'division_id' => $this->division->id, 'district_id' => $this->district->id, 'upazila_id' => $this->upazila->id, 'address_line' => 'House 12, Road 5',
    ]);

    $this->actingAs($this->customer)->get(route('account.dashboard'))->assertSee('House 12, Road 5');
});
