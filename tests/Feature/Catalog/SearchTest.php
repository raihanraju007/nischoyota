<?php

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;

beforeEach(function () {
    $this->electrical = Category::factory()->create(['name' => 'Electrical', 'slug' => 'electrical']);
    $this->fans = Category::factory()->childOf($this->electrical)->create(['name' => 'Fans', 'slug' => 'fans']);
    $this->kitchen = Category::factory()->create(['name' => 'Kitchen', 'slug' => 'kitchen']);
});

/**
 * @param  array<string, mixed>  $attributes
 */
function searchableProduct(string $name, Category $category, array $attributes = [], ?string $sku = null): Product
{
    $product = Product::factory()->active()->create(['name' => $name, 'category_id' => $category->id, ...$attributes]);
    ProductVariant::factory()->for($product)->create(['is_default' => true, 'sku' => $sku ?? fake()->unique()->bothify('SKU-####')]);

    return $product;
}

it('finds products by name, SKU, brand and category', function (string $query, string $expected) {
    $walton = Brand::factory()->create(['name' => 'Walton']);
    searchableProduct('Breeze Fan', $this->fans, ['brand_id' => $walton->id], 'NS-EL-0001');
    searchableProduct('Rice Cooker', $this->kitchen, [], 'NS-HK-0001');

    $this->get(route('search', ['q' => $query]))
        ->assertOk()
        ->assertSee($expected)
        ->assertDontSee($expected === 'Breeze Fan' ? 'Rice Cooker' : 'Breeze Fan');
})->with([
    'name, any case' => ['BREEZE', 'Breeze Fan'],
    'partial word' => ['cook', 'Rice Cooker'],
    'sku' => ['hk-0001', 'Rice Cooker'],
    'brand' => ['walton', 'Breeze Fan'],
    'category' => ['kitchen', 'Rice Cooker'],
]);

it('requires every word to match', function () {
    $walton = Brand::factory()->create(['name' => 'Walton']);
    searchableProduct('Breeze Fan', $this->fans, ['brand_id' => $walton->id]);
    searchableProduct('Table Fan', $this->fans);

    $this->get(route('search', ['q' => 'walton fan']))->assertSee('Breeze Fan')->assertDontSee('Table Fan');
});

it('treats % and _ as plain text', function () {
    searchableProduct('Breeze Fan', $this->fans);

    $this->get(route('search', ['q' => '%%']))->assertSee('0 products found')->assertSee('No products found');
});

it('only finds listed products', function () {
    searchableProduct('Draft Fan', $this->fans, ['status' => 'draft']);
    searchableProduct('Hidden Fan', Category::factory()->inactive()->create());

    $this->get(route('search', ['q' => 'fan']))->assertSee('No products found')->assertDontSee('Draft Fan')->assertDontSee('Hidden Fan');
});

it('orders by best match by default', function () {
    searchableProduct('Fan Regulator', $this->fans, ['sold_count' => 99]);
    searchableProduct('Breeze Fan', $this->fans, ['sold_count' => 50]);
    searchableProduct('Fan', $this->fans, ['sold_count' => 1]);

    $this->get(route('search', ['q' => 'fan']))
        ->assertSeeInOrder(['>Fan</a>', 'Fan Regulator', 'Breeze Fan'], escape: false)
        ->assertSee('Best match');
});

it('offers other sorts and scopes the filters to the results', function () {
    $walton = Brand::factory()->create(['name' => 'Walton', 'slug' => 'walton']);
    $vision = Brand::factory()->create(['name' => 'Vision', 'slug' => 'vision']);
    searchableProduct('Breeze Fan', $this->fans, ['brand_id' => $walton->id, 'published_at' => now()->subDays(5)]);
    searchableProduct('Table Fan', $this->fans, ['published_at' => now()->subDay()]);
    searchableProduct('Kettle', $this->kitchen, ['brand_id' => $vision->id]);

    $this->get(route('search', ['q' => 'fan', 'sort' => 'newest']))
        ->assertSeeInOrder(['Table Fan', 'Breeze Fan'])
        ->assertSee('value="walton"', escape: false)
        ->assertDontSee('value="vision"', escape: false)
        ->assertSee('name="q" value="fan"', escape: false);

    $this->get(route('search', ['q' => 'fan', 'brand' => ['walton']]))->assertSee('Breeze Fan')->assertDontSee('Table Fan');
});

it('keeps the query when a filter chip is removed', function () {
    $walton = Brand::factory()->create(['name' => 'Walton', 'slug' => 'walton']);
    searchableProduct('Breeze Fan', $this->fans, ['brand_id' => $walton->id]);

    $this->get(route('search', ['q' => 'fan', 'brand' => ['walton']]))
        ->assertSee('href="'.e(route('search', ['q' => 'fan'])).'"', escape: false);
});

it('shows a search box without a query and a hint for one character', function () {
    searchableProduct('Breeze Fan', $this->fans);

    $this->get(route('search'))->assertOk()->assertSee('Or browse a category')->assertSee(route('category', 'electrical'));
    $this->get(route('search', ['q' => 'f']))->assertSee('Type at least 2 characters.');
});

it('suggests best sellers when nothing matches', function () {
    searchableProduct('Breeze Fan', $this->fans, ['sold_count' => 80]);

    $this->get(route('search', ['q' => 'xyzabc']))
        ->assertSee('Search results for &ldquo;xyzabc&rdquo;', escape: false)
        ->assertSee('No products found')
        ->assertSee('You may like these')
        ->assertSee('Breeze Fan');
});

it('keeps the query in the header search box', function () {
    $this->get(route('search', ['q' => 'kettle']))->assertSee('value="kettle"', escape: false);
});
