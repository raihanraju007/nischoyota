<?php

use App\Modules\Location\Models\District;
use App\Modules\Location\Models\Division;
use App\Modules\Location\Models\Upazila;

it('lists the districts of a division by name', function () {
    $division = Division::factory()->create();
    District::factory()->for($division)->create(['name' => 'Gazipur', 'name_bn' => 'গাজীপুর']);
    District::factory()->for($division)->create(['name' => 'Dhaka', 'name_bn' => 'ঢাকা']);
    District::factory()->create(['name' => 'Elsewhere']);

    $this->getJson(route('locations.districts', $division))
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.name', 'Dhaka')
        ->assertJsonPath('0.name_bn', 'ঢাকা')
        ->assertJsonStructure([['id', 'name', 'name_bn']]);
});

it('lists the upazilas of a district by name', function () {
    $district = District::factory()->create();
    Upazila::factory()->for($district)->create(['name' => 'Savar']);
    Upazila::factory()->for($district)->create(['name' => 'Dhanmondi']);

    $this->getJson(route('locations.upazilas', $district))
        ->assertOk()
        ->assertJsonPath('0.name', 'Dhanmondi')
        ->assertJsonPath('1.name', 'Savar')
        ->assertJsonMissingPath('0.delivery_zone_id');
});

it('returns 404 for an unknown division', function () {
    $this->getJson(route('locations.districts', 999))->assertNotFound();
});
