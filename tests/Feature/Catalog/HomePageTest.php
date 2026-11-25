<?php

use App\Modules\Catalog\Data\HomePageData;
use App\Modules\Catalog\Data\ProductCardData;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;

beforeEach(function () {
    $this->electrical = Category::factory()->create(['name' => 'Electrical', 'slug' => 'electrical', 'sort_order' => 1]);
    $this->fans = Category::factory()->childOf($this->electrical)->create(['name' => 'Fans', 'slug' => 'fans']);
    $this->fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion', 'sort_order' => 2]);
});

/**
 * A listed product priced at ৳1,000 (optionally discounted from $oldPrice taka).
 *
 * @param  array<string, mixed>  $attributes
 */
function homeProduct(string $name, Category $category, array $attributes = [], ?int $oldPrice = null): Product
{
    $product = Product::factory()->active()->create([
        'name' => $name,
        'category_id' => $category->id,
        'min_price' => 100000,
        'max_discount_percent' => ProductVariant::discountPercentFor(100000, $oldPrice === null ? null : $oldPrice * 100),
        ...$attributes,
    ]);
    ProductVariant::factory()->for($product)->create(['is_default' => true, 'price' => 100000, 'compare_price' => $oldPrice === null ? null : $oldPrice * 100]);

    return $product;
}

/**
 * @param  list<ProductCardData>  $cards
 * @return list<string>
 */
function cardNames(array $cards): array
{
    return array_map(fn (ProductCardData $card): string => $card->name, $cards);
}

function homeSections(): HomePageData
{
    return test()->get(route('home'))->assertOk()->viewData('home');
}

it('shows departments with listed products, counting their subcategories, and a deals tile', function () {
    homeProduct('Ceiling Fan', $this->fans, oldPrice: 1250);
    homeProduct('Panjabi', $this->fashion);
    homeProduct('Draft Shirt', $this->fashion, ['status' => 'draft']);
    Category::factory()->create(['name' => 'Kitchen']);
    homeProduct('Hidden Lamp', Category::factory()->inactive()->create(['name' => 'Hidden Department']));

    expect(homeSections()->departments)->toBe([
        ['name' => 'Electrical', 'slug' => 'electrical', 'imageUrl' => null, 'productCount' => 1],
        ['name' => 'Fashion', 'slug' => 'fashion', 'imageUrl' => null, 'productCount' => 1],
    ]);

    $this->get(route('home'))
        ->assertSee(route('category', 'electrical'))
        ->assertSee('1 Product')
        ->assertSee('Up to 20% OFF')
        ->assertDontSee('Hidden Department');
});

it('shows featured products, newest first, and never unlisted ones', function () {
    homeProduct('Old Featured Fan', $this->fans, ['is_featured' => true, 'published_at' => now()->subDays(3)]);
    homeProduct('New Featured Fan', $this->fans, ['is_featured' => true, 'published_at' => now()->subDay()]);
    homeProduct('Plain Fan', $this->fans);
    homeProduct('Draft Featured Fan', $this->fans, ['is_featured' => true, 'status' => 'draft']);
    homeProduct('Hidden Featured Fan', Category::factory()->inactive()->create(), ['is_featured' => true]);

    expect(cardNames(homeSections()->featured))->toBe(['New Featured Fan', 'Old Featured Fan']);

    $this->get(route('home'))->assertSee('Featured Products')->assertDontSee('Draft Featured Fan')->assertDontSee('Hidden Featured Fan');
});

it('shows deals by biggest discount with the countdown', function () {
    homeProduct('Small Deal', $this->fans, oldPrice: 1100);
    homeProduct('Big Deal', $this->fans, oldPrice: 2000);
    homeProduct('Full Price', $this->fans);

    expect(cardNames(homeSections()->deals))->toBe(['Big Deal', 'Small Deal']);

    $this->get(route('home'))
        ->assertSee('Today&#039;s Deals', escape: false)
        ->assertSee('Up to 50% OFF')
        ->assertSee('data-countdown-hours', escape: false);
});

it('shows new arrivals and best sellers in order', function () {
    homeProduct('Oldest', $this->fans, ['published_at' => now()->subDays(5), 'sold_count' => 80]);
    homeProduct('Middle', $this->fans, ['published_at' => now()->subDays(3), 'sold_count' => 10]);
    homeProduct('Newest', $this->fans, ['published_at' => now()->subDay(), 'sold_count' => 40]);

    $sections = homeSections();

    expect(cardNames($sections->newArrivals))->toBe(['Newest', 'Middle', 'Oldest'])
        ->and(cardNames($sections->bestSellers))->toBe(['Oldest', 'Newest', 'Middle'])
        ->and($sections->bestSellers[0]->badge)->toBe('best');
});

it('limits the product sections', function () {
    foreach (range(1, 9) as $number) {
        homeProduct("Fan {$number}", $this->fans, ['is_featured' => true, 'published_at' => now()->subDays($number)], oldPrice: 1500);
    }

    $sections = homeSections();

    expect($sections->featured)->toHaveCount(8)
        ->and($sections->deals)->toHaveCount(4)
        ->and($sections->newArrivals)->toHaveCount(4)
        ->and($sections->bestSellers)->toHaveCount(4);
});

it('hides empty sections in an empty store', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Straight from Nature')
        ->assertDontSee('What Our Customers Say')
        ->assertDontSee('Shop by Category')
        ->assertDontSee('Featured Products')
        ->assertDontSee('deals-title', escape: false)
        ->assertDontSee('New Arrivals')
        ->assertDontSee('Best Sellers');
});

it('links promo banners to listed departments, otherwise to the shop', function () {
    $clothing = Category::factory()->create(['name' => 'Clothing', 'slug' => 'clothing', 'sort_order' => 3]);
    homeProduct('Panjabi', $clothing);

    expect(homeSections()->promoLinks)->toBe([
        'honey' => route('shop'),
        'plants' => route('shop'),
        'clothing' => route('category', 'clothing'),
    ]);

    $this->get(route('home'))->assertSee('href="'.route('category', 'clothing').'"', escape: false);
});

it('caches the sections for 10 minutes', function () {
    homeProduct('First Fan', $this->fans);
    homeSections();

    homeProduct('Second Fan', $this->fans);
    expect(cardNames(homeSections()->newArrivals))->toBe(['First Fan']);

    $this->travel(11)->minutes();
    expect(cardNames(homeSections()->newArrivals))->toContain('Second Fan');
});
