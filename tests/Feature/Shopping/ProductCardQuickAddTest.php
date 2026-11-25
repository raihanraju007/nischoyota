<?php

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\ProductVariant;

it('adds a single-variant product from its card', function () {
    $variant = sellableVariant([], ['name' => 'LED Bulb', 'min_price' => 50000]);

    $this->get(route('shop'))
        ->assertSee('<input type="hidden" name="variant_id" value="'.$variant->id.'">', escape: false)
        ->assertSee(route('cart.items.store'));
});

it('links a product with options to its page', function () {
    $variant = sellableVariant([], ['name' => 'Polo Shirt', 'slug' => 'polo-shirt', 'min_price' => 50000]);
    $variant->attributeValues()->attach(AttributeValue::factory()->create());
    $second = ProductVariant::factory()->for($variant->product)->create(['price' => 60000]);
    $second->attributeValues()->attach(AttributeValue::factory()->create());

    $this->get(route('shop'))
        ->assertSee('Choose Options')
        ->assertDontSee('name="variant_id"', escape: false);
});

it('posts the product page buy form to the cart', function () {
    sellableVariant([], ['name' => 'Ceiling Fan', 'slug' => 'ceiling-fan']);

    $this->get(route('product', 'ceiling-fan'))
        ->assertSee('action="'.route('cart.items.store').'"', escape: false)
        ->assertSee('name="buy_now"', escape: false)
        ->assertSee('name="quantity"', escape: false);
});
