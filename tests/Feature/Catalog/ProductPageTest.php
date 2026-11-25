<?php

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Location\Models\DeliveryZone;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->electrical = Category::factory()->create(['name' => 'Electrical', 'slug' => 'electrical']);
    $this->fans = Category::factory()->childOf($this->electrical)->create(['name' => 'Fans', 'slug' => 'fans']);
});

/**
 * @param  array<string, mixed>  $attributes
 * @param  array<string, mixed>  $variant
 */
function pageProduct(array $attributes = [], array $variant = []): Product
{
    $product = Product::factory()->active()->create([
        'name' => 'Ceiling Fan', 'slug' => 'ceiling-fan', 'category_id' => test()->fans->id, ...$attributes,
    ]);
    $default = ProductVariant::factory()->for($product)->create([
        'is_default' => true, 'sku' => 'NS-EL-0001', 'price' => 485000, 'compare_price' => 560000, 'stock_quantity' => 20, ...$variant,
    ]);
    $product->forceFill(['min_price' => $default->price])->save();

    return $product;
}

it('shows the product with breadcrumbs, brand, price and SKU', function () {
    $brand = Brand::factory()->create(['name' => 'Walton', 'slug' => 'walton']);
    pageProduct(['brand_id' => $brand->id, 'short_description' => 'Quiet copper motor.', 'meta_title' => 'Best Ceiling Fan']);

    $this->get(route('product', 'ceiling-fan'))
        ->assertOk()
        ->assertSee('<title>Best Ceiling Fan — Nischoyota</title>', escape: false)
        ->assertSeeInOrder(['Electrical', 'Fans', 'Ceiling Fan'])
        ->assertSee('Walton')
        ->assertSee(e(route('shop', ['brand' => ['walton']])), escape: false)
        ->assertSee('৳ 4,850')
        ->assertSee('৳ 5,600')
        ->assertSee('-13%')
        ->assertSee('NS-EL-0001')
        ->assertSee('Quiet copper motor.');
});

it('returns 404 for products that are not listed', function (string $state) {
    $product = pageProduct();
    match ($state) {
        'draft' => $product->update(['status' => 'draft']),
        'archived' => $product->update(['status' => 'archived']),
        'hidden category' => $this->fans->update(['is_active' => false]),
    };

    $this->get(route('product', 'ceiling-fan'))->assertNotFound();
})->with(['draft', 'archived', 'hidden category']);

it('returns 404 for an unknown slug', function () {
    $this->get(route('product', 'nope'))->assertNotFound();
});

it('shows the stock note', function (int $stock, bool $preorder, string $note, bool $canBuy) {
    pageProduct(['allow_preorder' => $preorder], ['stock_quantity' => $stock]);

    $response = $this->get(route('product', 'ceiling-fan'))->assertSee($note);

    $canBuy
        ? $response->assertDontSee('data-add-to-cart data-variant-buy disabled', escape: false)
        : $response->assertSee('data-add-to-cart data-variant-buy disabled', escape: false);
})->with([
    'plenty' => [20, false, 'In Stock — ships within 24 hours', true],
    'low' => [3, false, 'Only 3 left — order soon', true],
    'pre-order' => [0, true, 'Available for pre-order', true],
    'sold out' => [0, false, 'Out of stock', false],
]);

it('builds option groups and picker data from active variants', function () {
    $product = pageProduct();
    $color = ProductAttribute::factory()->create(['name' => 'Color']);
    $white = AttributeValue::factory()->for($color, 'attribute')->withColor('#FFFFFF')->create(['value' => 'White', 'sort_order' => 1]);
    $black = AttributeValue::factory()->for($color, 'attribute')->create(['value' => 'Black', 'sort_order' => 2]);
    $maroon = AttributeValue::factory()->for($color, 'attribute')->create(['value' => 'Maroon', 'sort_order' => 3]);
    $photo = ProductImage::factory()->for($product)->create(['path' => 'products/black.jpg', 'sort_order' => 1]);

    $product->defaultVariant->attributeValues()->attach($white);
    $blackVariant = ProductVariant::factory()->for($product)->create(['sku' => 'BLACK-1', 'price' => 500000, 'stock_quantity' => 0, 'product_image_id' => $photo->id]);
    $blackVariant->attributeValues()->attach($black);
    $inactive = ProductVariant::factory()->for($product)->create(['is_active' => false]);
    $inactive->attributeValues()->attach($maroon);

    $response = $this->get(route('product', 'ceiling-fan'))->assertOk();

    $response->assertSeeInOrder(['Color:', 'White', 'Black'])
        ->assertDontSee('data-value="Maroon"', escape: false)
        ->assertSee('variant-btn active', escape: false);

    preg_match('#<script type="application/json" id="variant-data">(.*?)</script>#s', $response->getContent(), $match);
    $variants = collect(json_decode($match[1], true));

    expect($variants)->toHaveCount(2)
        ->and($variants->firstWhere('sku', 'BLACK-1'))
        ->toMatchArray(['price' => '৳ 5,000', 'canBuy' => false, 'stockText' => 'Out of stock', 'valueIds' => [$black->id]])
        ->and($variants->firstWhere('sku', 'BLACK-1')['imageUrl'])->toEndWith('products/black.jpg')
        ->and($variants->firstWhere('isDefault', true)['sku'])->toBe('NS-EL-0001');
});

it('renders the Markdown description safely and shows specifications', function () {
    pageProduct([
        'description' => "## Why buy\n\n- copper motor\n\n<script>alert(1)</script>",
        'specifications' => [['label' => 'Motor', 'value' => '100% Copper']],
    ]);

    $this->get(route('product', 'ceiling-fan'))
        ->assertSee('<h2>Why buy</h2>', escape: false)
        ->assertSee('<li>copper motor</li>', escape: false)
        ->assertDontSee('<script>alert(1)</script>', escape: false)
        ->assertSee('Specifications')
        ->assertSee('100% Copper');
});

it('hides the specifications tab without specifications and shows no-review text', function () {
    pageProduct(['specifications' => null]);

    $this->get(route('product', 'ceiling-fan'))
        ->assertDontSee('id="tab-specs"', escape: false)
        ->assertSee('Reviews (0)')
        ->assertSee('No reviews yet.');
});

it('shows delivery zones and the free-delivery threshold', function () {
    DeliveryZone::factory()->create(['name' => 'Inside Dhaka', 'charge' => 6000, 'min_days' => 1, 'max_days' => 2]);
    DeliveryZone::factory()->create(['name' => 'Outside Dhaka', 'charge' => 12000, 'min_days' => 3, 'max_days' => 5]);
    pageProduct();

    $this->get(route('product', 'ceiling-fan'))
        ->assertSee('Estimated delivery: 1–5 days')
        ->assertSee('Inside Dhaka ৳ 60')
        ->assertSee('Free delivery');
});

it('shows related products from the same subcategory first', function () {
    pageProduct();
    $sibling = Product::factory()->active()->withDefaultVariant()->create(['name' => 'Table Fan', 'category_id' => $this->fans->id]);
    $bulbs = Category::factory()->childOf($this->electrical)->create(['slug' => 'led-bulbs']);
    Product::factory()->active()->withDefaultVariant()->create(['name' => 'Bulb', 'category_id' => $bulbs->id, 'sold_count' => 999]);
    Product::factory()->active()->withDefaultVariant()->create(['name' => 'Panjabi', 'category_id' => Category::factory()->create()->id]);
    Product::factory()->withDefaultVariant()->create(['name' => 'Draft Fan', 'category_id' => $this->fans->id]);

    $this->get(route('product', 'ceiling-fan'))
        ->assertSee('You May Also Like')
        ->assertSeeInOrder(['Table Fan', 'Bulb'])
        ->assertDontSee('Draft Fan')
        ->assertDontSee('Panjabi');
});

it('loads the page with a small, fixed number of queries', function () {
    pageProduct();
    foreach (range(1, 4) as $number) {
        Product::factory()->active()->withDefaultVariant()->create(['category_id' => $this->fans->id]);
    }

    DB::enableQueryLog();
    $this->get(route('product', 'ceiling-fan'))->assertOk();

    expect(count(DB::getQueryLog()))->toBeLessThan(25);
});
