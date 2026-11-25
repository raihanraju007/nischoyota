<?php

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductVariant;

beforeEach(function () {
    $this->electrical = Category::factory()->create(['name' => 'Electrical', 'slug' => 'electrical', 'sort_order' => 1]);
    $this->fans = Category::factory()->childOf($this->electrical)->create(['name' => 'Fans', 'slug' => 'fans']);
    $this->fashion = Category::factory()->create(['name' => 'Fashion', 'slug' => 'fashion', 'sort_order' => 2]);
});

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $variant
 */
function listedProduct(string $name, Category $category, array $attributes = [], array $variant = []): Product
{
    $product = Product::factory()->active()->create(['name' => $name, 'category_id' => $category->id, ...$attributes]);
    $defaultVariant = ProductVariant::factory()->for($product)->create(['is_default' => true, 'price' => 100000, 'stock_quantity' => 10, ...$variant]);
    $product->forceFill([
        'min_price' => $defaultVariant->price,
        'max_discount_percent' => ProductVariant::discountPercentFor($defaultVariant->price, $defaultVariant->compare_price),
    ])->save();

    return $product;
}

describe('scope', function () {
    it('lists only active products in active categories', function () {
        listedProduct('Ceiling Fan', $this->fans);
        Product::factory()->withDefaultVariant()->create(['name' => 'Draft Fan', 'category_id' => $this->fans->id]);
        Product::factory()->archived()->withDefaultVariant()->create(['name' => 'Old Fan', 'category_id' => $this->fans->id]);
        listedProduct('Hidden Thing', Category::factory()->inactive()->create());

        $this->get(route('shop'))
            ->assertOk()
            ->assertSee('Ceiling Fan')
            ->assertDontSee('Draft Fan')
            ->assertDontSee('Old Fan')
            ->assertDontSee('Hidden Thing')
            ->assertSee('Showing 1–1 of 1 product');
    });

    it('shows a department with its subcategories', function () {
        listedProduct('Ceiling Fan', $this->fans);
        listedProduct('Panjabi', $this->fashion);

        $this->get(route('category', 'electrical'))
            ->assertOk()
            ->assertSee('Ceiling Fan')
            ->assertDontSee('Panjabi')
            ->assertSee('All Electrical')
            ->assertSee(route('category', 'fans'));

        $this->get(route('category', 'fans'))->assertSee('Ceiling Fan')->assertSee('All Electrical');
    });

    it('returns 404 for an unknown or hidden category and redirects the bare URL', function () {
        Category::factory()->inactive()->create(['slug' => 'hidden']);

        $this->get(route('category', 'nope'))->assertNotFound();
        $this->get(route('category', 'hidden'))->assertNotFound();
        $this->get('/category')->assertRedirect(route('shop'));
    });

    it('shows only discounted products on Deals with the highest discount', function () {
        listedProduct('Full Price', $this->fans);
        listedProduct('Cheap Fan', $this->fans, variant: ['price' => 75000, 'compare_price' => 100000]);

        $this->get(route('deals'))
            ->assertOk()
            ->assertSee('Cheap Fan')
            ->assertDontSee('Full Price')
            ->assertSee('Up to 25% OFF')
            ->assertSee('1 deal available');
    });
});

describe('filters', function () {
    it('filters by department (including subcategories) and brand', function () {
        $walton = Brand::factory()->create(['name' => 'Walton', 'slug' => 'walton']);
        listedProduct('Walton Fan', $this->fans, ['brand_id' => $walton->id]);
        listedProduct('Other Fan', $this->fans);
        listedProduct('Panjabi', $this->fashion);

        $this->get(route('shop', ['category' => ['electrical']]))->assertSee('Walton Fan')->assertSee('Other Fan')->assertDontSee('Panjabi');
        $this->get(route('shop', ['brand' => ['walton']]))->assertSee('Walton Fan')->assertDontSee('Other Fan');
    });

    it('filters by price in taka', function () {
        listedProduct('Cheap', $this->fans, variant: ['price' => 50000]);
        listedProduct('Mid', $this->fans, variant: ['price' => 150000]);
        listedProduct('Dear', $this->fans, variant: ['price' => 500000]);

        $this->get(route('shop', ['price_min' => '1000', 'price_max' => '2000']))
            ->assertSee('Mid')->assertDontSee('Cheap')->assertDontSee('Dear')
            ->assertSee('৳ 1,000 – ৳ 2,000');
    });

    it('filters by rating, discount, stock and pre-order', function () {
        listedProduct('Top Rated', $this->fans, ['rating_average' => 4.5]);
        listedProduct('Big Sale', $this->fans, variant: ['price' => 50000, 'compare_price' => 100000]);
        listedProduct('Sold Out', $this->fans, variant: ['stock_quantity' => 0]);
        listedProduct('Preorder Fan', $this->fans, ['allow_preorder' => true], ['stock_quantity' => 0]);

        $this->get(route('shop', ['rating' => 4]))->assertSee('Top Rated')->assertDontSee('Big Sale');
        $this->get(route('shop', ['discount' => 40]))->assertSee('Big Sale')->assertDontSee('Top Rated');
        $this->get(route('shop', ['availability' => ['in-stock']]))->assertDontSee('Sold Out')->assertDontSee('Preorder Fan')->assertSee('Top Rated');
        $this->get(route('shop', ['availability' => ['pre-order']]))->assertSee('Preorder Fan')->assertDontSee('Top Rated');
    });

    it('filters by colour and size of active variants', function () {
        $color = ProductAttribute::factory()->create(['name' => 'Color', 'slug' => 'color']);
        $white = AttributeValue::factory()->for($color, 'attribute')->withColor('#FFFFFF')->create(['value' => 'White']);
        $black = AttributeValue::factory()->for($color, 'attribute')->create(['value' => 'Black']);
        $whiteFan = listedProduct('White Fan', $this->fans);
        $whiteFan->defaultVariant->attributeValues()->attach($white);
        $retiredBlack = listedProduct('Retired Black', $this->fans, variant: ['is_active' => false]);
        $retiredBlack->variants()->first()->attributeValues()->attach($black);

        $this->get(route('shop', ['color' => [$white->id]]))->assertSee('White Fan')->assertDontSee('Retired Black');
        $this->get(route('shop', ['color' => [$black->id]]))->assertDontSee('Retired Black')->assertSee('No products match these filters');
        $this->get(route('shop'))->assertSee('Filter by color: White')->assertDontSee('Filter by color: Black');
    });

    it('ignores unknown filter values instead of failing', function () {
        listedProduct('Ceiling Fan', $this->fans);

        $this->get(route('shop', ['rating' => 9, 'discount' => 'x', 'sort' => 'bogus', 'price_min' => '-5', 'color' => ['abc']]))
            ->assertOk()
            ->assertSee('Ceiling Fan');
    });

    it('shows chips that remove one filter and a clear-all link', function () {
        listedProduct('Ceiling Fan', $this->fans);

        $this->get(route('shop', ['category' => ['electrical'], 'rating' => 4, 'sort' => 'newest']))
            ->assertSee('href="'.e(route('shop', ['rating' => 4, 'sort' => 'newest'])).'"', escape: false)
            ->assertSee('4★ &amp; up', escape: false)
            ->assertSee('Clear all');
    });

    it('lists departments with product counts', function () {
        listedProduct('Fan A', $this->fans);
        listedProduct('Fan B', $this->electrical);

        $this->get(route('shop'))->assertSee('Electrical <span class="filter-count">(2)</span>', escape: false);
    });
});

describe('sort and pages', function () {
    it('sorts', function (string $sort, array $expectedOrder) {
        listedProduct('Alpha', $this->fans, ['sold_count' => 5, 'rating_average' => 3.0, 'published_at' => now()->subDays(3)], ['price' => 300000]);
        listedProduct('Beta', $this->fans, ['sold_count' => 50, 'rating_average' => 4.9, 'published_at' => now()->subDays(9)], ['price' => 100000]);
        listedProduct('Gamma', $this->fans, ['sold_count' => 20, 'rating_average' => 4.0, 'published_at' => now()->subDay()], ['price' => 200000]);

        $this->get(route('shop', ['sort' => $sort]))->assertSeeInOrder($expectedOrder);
    })->with([
        'popular' => ['popular', ['Beta', 'Gamma', 'Alpha']],
        'newest' => ['newest', ['Gamma', 'Alpha', 'Beta']],
        'price low' => ['price-asc', ['Beta', 'Gamma', 'Alpha']],
        'price high' => ['price-desc', ['Alpha', 'Gamma', 'Beta']],
        'rating' => ['rating', ['Beta', 'Gamma', 'Alpha']],
    ]);

    it('paginates 24 per page and keeps the filters', function () {
        foreach (range(1, 25) as $number) {
            listedProduct("Fan {$number}", $this->fans);
        }

        $this->get(route('shop', ['category' => ['electrical']]))
            ->assertSee('Showing 1–24 of 25 products')
            ->assertSee('category%5B0%5D=electrical&amp;page=2', escape: false);
    });

    it('shows card details: price, old price, discount, badges, stock', function () {
        $saleFan = listedProduct('Sale Fan', $this->fans, ['sold_count' => 60], ['price' => 485000, 'compare_price' => 560000]);
        listedProduct('New Kettle', $this->fans, ['published_at' => now()->subDays(2)]);
        listedProduct('Empty Shelf', $this->fans, ['published_at' => now()->subMonth()], ['stock_quantity' => 0]);

        $this->get(route('shop'))
            ->assertSee('৳ 4,850')
            ->assertSee('৳ 5,600')
            ->assertSee('-13%')
            ->assertSee('BEST SELLER')
            ->assertSee('NEW')
            ->assertSee('Out of Stock')
            ->assertSee(route('product', $saleFan->slug));
    });

    it('uses the cheapest active variant for the card price', function () {
        $product = listedProduct('Multi Fan', $this->fans, variant: ['price' => 500000]);
        ProductVariant::factory()->for($product)->create(['price' => 400000, 'compare_price' => 450000]);
        ProductVariant::factory()->for($product)->create(['price' => 100000, 'is_active' => false]);

        $this->get(route('shop'))->assertSee('৳ 4,000')->assertSee('৳ 4,500')->assertDontSee('৳ 1,000');
    });
});
