<?php

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductAttribute;

describe('attributes', function () {
    it('lists attributes with their values in sort order', function () {
        $color = ProductAttribute::factory()->create(['name' => 'Color']);
        AttributeValue::factory()->for($color, 'attribute')->withColor('#FFFFFF')->create(['value' => 'White', 'sort_order' => 2]);
        AttributeValue::factory()->for($color, 'attribute')->create(['value' => 'Black', 'sort_order' => 1]);
        ProductAttribute::factory()->create(['name' => 'Size']);

        actingAsStaff()->get(route('admin.attributes.index'))
            ->assertOk()
            ->assertSeeInOrder(['Color', '2 values', 'Black', 'White', 'Size', 'No values yet'])
            ->assertSee('background: #FFFFFF', escape: false);
    });

    it('shows an empty state', function () {
        actingAsAdmin()->get(route('admin.attributes.index'))->assertSee('No attributes yet');
    });

    it('creates an attribute and opens it to add values', function () {
        actingAsStaff()->post(route('admin.attributes.store'), ['name' => 'Blade Size', 'slug' => ''])
            ->assertRedirect(route('admin.attributes.edit', ProductAttribute::query()->sole()))
            ->assertSessionHas('success');

        expect(ProductAttribute::query()->sole()->slug)->toBe('blade-size');
    });

    it('validates the attribute', function (array $input, string $errorField) {
        ProductAttribute::factory()->create(['name' => 'Color', 'slug' => 'color']);

        actingAsAdmin()->post(route('admin.attributes.store'), $input)->assertSessionHasErrors($errorField);
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'name taken, other case' => [['name' => 'color'], 'name'],
        'name too long' => [['name' => str_repeat('a', 51)], 'name'],
        'slug taken' => [['name' => 'Colour', 'slug' => 'color'], 'slug'],
        'invalid slug' => [['name' => 'Colour', 'slug' => 'Colour Name'], 'slug'],
    ]);

    it('updates an attribute', function () {
        $attribute = ProductAttribute::factory()->create(['name' => 'Colour', 'slug' => 'colour']);

        actingAsAdmin()->put(route('admin.attributes.update', $attribute), ['name' => 'Color', 'slug' => ''])
            ->assertRedirect(route('admin.attributes.edit', $attribute));

        expect($attribute->fresh())->name->toBe('Color')->slug->toBe('color');
    });

    it('deletes an attribute with its values', function () {
        $attribute = ProductAttribute::factory()->create();
        $value = AttributeValue::factory()->for($attribute, 'attribute')->create();

        actingAsAdmin()->delete(route('admin.attributes.destroy', $attribute))
            ->assertRedirect(route('admin.attributes.index'));

        $this->assertModelMissing($attribute);
        $this->assertModelMissing($value);
    });
});

describe('values', function () {
    it('shows the values page with an add form', function () {
        $attribute = ProductAttribute::factory()->create(['name' => 'Color']);
        AttributeValue::factory()->for($attribute, 'attribute')->withColor('#DC2626')->create(['value' => 'Red']);

        actingAsAdmin()->get(route('admin.attributes.edit', $attribute))
            ->assertOk()
            ->assertSee('Add Value')
            ->assertSee('Red')
            ->assertSee('#DC2626');
    });

    it('loads a value into the form for editing', function () {
        $attribute = ProductAttribute::factory()->create();
        $value = AttributeValue::factory()->for($attribute, 'attribute')->create(['value' => 'Maroon']);

        actingAsAdmin()->get(route('admin.attributes.edit', [$attribute, 'value' => $value->id]))
            ->assertSee('Edit Value')
            ->assertSee('value="Maroon"', escape: false);
    });

    it('ignores a value from another attribute in the edit link', function () {
        $attribute = ProductAttribute::factory()->create();
        $foreignValue = AttributeValue::factory()->create(['value' => 'Foreign']);

        actingAsAdmin()->get(route('admin.attributes.edit', [$attribute, 'value' => $foreignValue->id]))
            ->assertOk()
            ->assertSee('Add Value')
            ->assertDontSee('value="Foreign"', escape: false);
    });

    it('adds a value with an uppercase swatch colour', function () {
        $attribute = ProductAttribute::factory()->create();

        actingAsStaff()->post(route('admin.attributes.values.store', $attribute), [
            'value' => ' Sky Blue ',
            'color_hex' => '#87ceeb',
            'sort_order' => 3,
        ])->assertRedirect(route('admin.attributes.edit', $attribute))->assertSessionHas('success');

        $value = $attribute->values()->sole();
        expect($value->value)->toBe('Sky Blue')
            ->and($value->color_hex)->toBe('#87CEEB')
            ->and($value->sort_order)->toBe(3);
    });

    it('validates a value', function (array $input, string $errorField) {
        $attribute = ProductAttribute::factory()->create();
        AttributeValue::factory()->for($attribute, 'attribute')->create(['value' => 'White']);

        actingAsAdmin()->post(route('admin.attributes.values.store', $attribute), $input)->assertSessionHasErrors($errorField);
    })->with([
        'value missing' => [['value' => ''], 'value'],
        'duplicate in attribute, other case' => [['value' => 'white'], 'value'],
        'too long' => [['value' => str_repeat('a', 51)], 'value'],
        'colour without #' => [['value' => 'Red', 'color_hex' => 'DC2626'], 'color_hex'],
        'short colour' => [['value' => 'Red', 'color_hex' => '#FFF'], 'color_hex'],
        'css injection' => [['value' => 'Red', 'color_hex' => 'red;}'], 'color_hex'],
        'negative sort order' => [['value' => 'Red', 'sort_order' => -1], 'sort_order'],
    ]);

    it('allows the same value in different attributes', function () {
        AttributeValue::factory()->create(['value' => 'White']);
        $frameColor = ProductAttribute::factory()->create();

        actingAsAdmin()->post(route('admin.attributes.values.store', $frameColor), ['value' => 'White'])
            ->assertSessionHasNoErrors();
    });

    it('updates a value and lets it keep its own name', function () {
        $attribute = ProductAttribute::factory()->create();
        $value = AttributeValue::factory()->for($attribute, 'attribute')->create(['value' => 'White']);

        actingAsAdmin()->put(route('admin.attributes.values.update', [$attribute, $value]), [
            'value' => 'White',
            'color_hex' => '#FFFFFF',
            'sort_order' => 1,
        ])->assertSessionHasNoErrors();

        expect($value->fresh()->color_hex)->toBe('#FFFFFF');
    });

    it('deletes a value', function () {
        $attribute = ProductAttribute::factory()->create();
        $value = AttributeValue::factory()->for($attribute, 'attribute')->create();

        actingAsAdmin()->delete(route('admin.attributes.values.destroy', [$attribute, $value]))
            ->assertRedirect(route('admin.attributes.edit', $attribute));

        $this->assertModelMissing($value);
    });

    it('returns 404 for a value of another attribute', function (string $method, string $routeName) {
        $attribute = ProductAttribute::factory()->create();
        $foreignValue = AttributeValue::factory()->create();

        actingAsAdmin()->{$method}(route($routeName, [$attribute, $foreignValue]), ['value' => 'X'])
            ->assertNotFound();

        $this->assertModelExists($foreignValue);
    })->with([
        ['put', 'admin.attributes.values.update'],
        ['delete', 'admin.attributes.values.destroy'],
    ]);
});

describe('authorization', function () {
    it('keeps guests out and forbids customers', function (string $method, string $routeName) {
        $attribute = ProductAttribute::factory()->create();
        $value = AttributeValue::factory()->for($attribute, 'attribute')->create();
        $parameters = str_contains($routeName, 'values.') && ! str_ends_with($routeName, 'store') ? [$attribute, $value] : [$attribute];

        $this->{$method}(route($routeName, $parameters))->assertRedirect(route('admin.login'));
        actingAsCustomer()->{$method}(route($routeName, $parameters))->assertForbidden();
    })->with([
        ['get', 'admin.attributes.index'],
        ['get', 'admin.attributes.edit'],
        ['post', 'admin.attributes.store'],
        ['put', 'admin.attributes.update'],
        ['delete', 'admin.attributes.destroy'],
        ['post', 'admin.attributes.values.store'],
        ['put', 'admin.attributes.values.update'],
        ['delete', 'admin.attributes.values.destroy'],
    ]);
});
