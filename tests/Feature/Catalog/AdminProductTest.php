<?php

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Enums\SellUnit;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Enums\StockMovementReason;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->electrical = Category::factory()->create(['name' => 'Electrical', 'slug' => 'electrical']);
    $this->fans = Category::factory()->childOf($this->electrical)->create(['name' => 'Fans', 'slug' => 'fans']);
});

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function productInput(array $overrides = []): array
{
    return [
        'name' => 'Ceiling Fan 56"',
        'category_id' => test()->fans->id,
        'brand_id' => '',
        'status' => 'draft',
        'price' => '4850',
        'compare_price' => '',
        'cost_price' => '',
        'sku' => '',
        'stock_quantity' => 10,
        'specifications' => [['label' => '', 'value' => '']],
        ...$overrides,
    ];
}

function productWithImages(int $count): Product
{
    $product = Product::factory()->withDefaultVariant()->create();

    foreach (range(0, $count - 1) as $position) {
        Storage::disk('public')->put("products/{$position}.jpg", 'image');
        ProductImage::factory()->for($product)->create(['path' => "products/{$position}.jpg", 'sort_order' => $position]);
    }

    return $product;
}

describe('create', function () {
    it('creates a product with its default variant', function () {
        $brand = Brand::factory()->create();

        actingAsAdmin()->post(route('admin.products.store'), productInput([
            'brand_id' => $brand->id,
            'status' => 'active',
            'price' => '4850.50',
            'compare_price' => '5600',
            'cost_price' => '3400',
            'stock_quantity' => 25,
            'weight_grams' => 4200,
            'is_featured' => '1',
            'short_description' => 'Quiet copper motor.',
        ]))->assertSessionHasNoErrors()->assertSessionHas('success');

        $product = Product::query()->with('defaultVariant')->sole();
        $variant = $product->defaultVariant;

        expect($product->slug)->toBe('ceiling-fan-56')
            ->and($product->status)->toBe(ProductStatus::Active)
            ->and($product->published_at)->not->toBeNull()
            ->and($product->is_featured)->toBeTrue()
            ->and($product->brand_id)->toBe($brand->id)
            ->and($product->min_price)->toBe(485050)
            ->and($product->max_discount_percent)->toBe(13)
            ->and($variant->price)->toBe(485050)
            ->and($variant->compare_price)->toBe(560000)
            ->and($variant->cost_price)->toBe(340000)
            ->and($variant->stock_quantity)->toBe(25)
            ->and(StockMovement::query()->sole())
            ->quantity_change->toBe(25)
            ->reason->toBe(StockMovementReason::OpeningStock)
            ->and($variant->weight_grams)->toBe(4200)
            ->and($variant->is_default)->toBeTrue();
    });

    it('redirects to the edit page after creating', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput())
            ->assertRedirect(route('admin.products.edit', Product::query()->sole()));
    });

    it('does not publish a draft', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput());

        expect(Product::query()->sole()->published_at)->toBeNull();
    });

    it('generates a SKU from the department', function () {
        ProductVariant::factory()->create(['sku' => 'NS-EL-0034']);

        actingAsAdmin()->post(route('admin.products.store'), productInput());

        expect(Product::query()->latest('id')->first()->defaultVariant->sku)->toBe('NS-EL-0035');
    });

    it('uppercases a typed SKU', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput(['sku' => 'fan-56-white']));

        expect(ProductVariant::query()->sole()->sku)->toBe('FAN-56-WHITE');
    });

    it('stores uploaded images in order', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput([
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ]));

        $images = Product::query()->sole()->images;
        expect($images)->toHaveCount(2)
            ->and($images->pluck('sort_order')->all())->toBe([0, 1]);
        $images->each(fn (ProductImage $image) => Storage::disk('public')->assertExists($image->path));
    });

    it('stores specifications and ignores empty rows', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput([
            'specifications' => [
                ['label' => ' Motor ', 'value' => '100% Copper'],
                ['label' => '', 'value' => ''],
                ['label' => 'Power', 'value' => '75W'],
            ],
        ]))->assertSessionHasNoErrors();

        expect(Product::query()->sole()->specifications)->toBe([
            ['label' => 'Motor', 'value' => '100% Copper'],
            ['label' => 'Power', 'value' => '75W'],
        ]);
    });

    it('stores the selling unit and shows it next to the price', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput([
            'name' => 'Sundarban Honey',
            'status' => 'active',
            'sell_unit' => 'gram',
            'unit_quantity' => '500',
        ]))->assertSessionHasNoErrors();

        $product = Product::query()->sole();

        expect($product->sell_unit)->toBe(SellUnit::Gram)
            ->and($product->unit_quantity)->toBe(500.0)
            ->and($product->unitLabel())->toBe('500 g');

        $this->get(route('product', $product->slug))->assertSee('/ 500 g');
    });

    it('sells per piece when no unit is chosen', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput())->assertSessionHasNoErrors();

        expect(Product::query()->sole())
            ->sell_unit->toBe(SellUnit::Piece)
            ->unit_quantity->toBeNull();
    });

    it('validates the input', function (array $input, string $errorField) {
        ProductVariant::factory()->create(['sku' => 'TAKEN-1']);

        actingAsAdmin()->post(route('admin.products.store'), productInput($input))->assertSessionHasErrors($errorField);
    })->with([
        'name missing' => [['name' => ''], 'name'],
        'category missing' => [['category_id' => ''], 'category_id'],
        'unknown category' => [['category_id' => 999], 'category_id'],
        'unknown brand' => [['brand_id' => 999], 'brand_id'],
        'invalid status' => [['status' => 'deleted'], 'status'],
        'unknown selling unit' => [['sell_unit' => 'barrel'], 'sell_unit'],
        'zero unit amount' => [['unit_quantity' => '0'], 'unit_quantity'],
        'price missing' => [['price' => ''], 'price'],
        'price zero' => [['price' => '0'], 'price'],
        'price with 3 decimals' => [['price' => '10.555'], 'price'],
        'old price equal to price' => [['compare_price' => '4850'], 'compare_price'],
        'old price lower than price' => [['compare_price' => '4000'], 'compare_price'],
        'negative cost price' => [['cost_price' => '-1'], 'cost_price'],
        'sku taken' => [['sku' => 'taken-1'], 'sku'],
        'sku with spaces' => [['sku' => 'GB EL 1'], 'sku'],
        'negative stock' => [['stock_quantity' => -1], 'stock_quantity'],
        'fractional stock' => [['stock_quantity' => 1.5], 'stock_quantity'],
        'svg image' => [['images' => [UploadedFile::fake()->create('a.svg', 5, 'image/svg+xml')]], 'images.0'],
        'image too large' => [['images' => [UploadedFile::fake()->image('a.jpg')->size(2049)]], 'images.0'],
        'too many images' => [['images' => array_fill(0, 9, UploadedFile::fake()->image('a.jpg'))], 'images'],
        'spec without value' => [['specifications' => [['label' => 'Motor', 'value' => '']]], 'specifications.0.value'],
        'spec without label' => [['specifications' => [['label' => '', 'value' => '75W']]], 'specifications.0.label'],
    ]);
});

describe('edit', function () {
    it('shows the product in the form', function () {
        $product = Product::factory()->withDefaultVariant(['price' => 485050, 'sku' => 'NS-EL-0012'])->create([
            'name' => 'Ceiling Fan',
            'specifications' => [['label' => 'Motor', 'value' => '100% Copper']],
        ]);

        actingAsStaff()->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Edit Product')
            ->assertSee('value="Ceiling Fan"', escape: false)
            ->assertSee('value="4850.50"', escape: false)
            ->assertSee('value="NS-EL-0012"', escape: false)
            ->assertSee('value="100% Copper"', escape: false);
    });

    it('updates the product, keeps the SKU and sets published_at once', function () {
        $product = Product::factory()->withDefaultVariant(['sku' => 'NS-EL-0001'])->create(['name' => 'Fan']);

        actingAsAdmin()->put(route('admin.products.update', $product), productInput([
            'name' => 'Fan',
            'status' => 'active',
            'price' => '1000',
            'compare_price' => '1250',
        ]))->assertRedirect(route('admin.products.edit', $product));

        $product->refresh()->load('defaultVariant');
        expect($product->defaultVariant->sku)->toBe('NS-EL-0001')
            ->and($product->min_price)->toBe(100000)
            ->and($product->max_discount_percent)->toBe(20)
            ->and($product->published_at)->not->toBeNull()
            ->and($product->variants()->count())->toBe(1);

        $publishedAt = $product->published_at;
        $this->travel(1)->day();
        actingAsAdmin()->put(route('admin.products.update', $product), productInput(['name' => 'Fan', 'status' => 'draft']));
        actingAsAdmin()->put(route('admin.products.update', $product), productInput(['name' => 'Fan', 'status' => 'active']));
        expect($product->fresh()->published_at->toDateTimeString())->toBe($publishedAt->toDateTimeString());
    });

    it('shows stock read-only and ignores posted stock when editing', function () {
        $product = Product::factory()->withDefaultVariant(['stock_quantity' => 12])->create(['name' => 'Fan']);

        actingAsAdmin()->get(route('admin.products.edit', $product))
            ->assertDontSee('name="stock_quantity"', escape: false)
            ->assertSee('Adjust stock')
            ->assertSee(route('admin.inventory.show', $product->defaultVariant));

        actingAsAdmin()->put(route('admin.products.update', $product), productInput(['name' => 'Fan', 'stock_quantity' => 999]))
            ->assertSessionHasNoErrors();

        expect($product->defaultVariant()->first()->stock_quantity)->toBe(12)
            ->and(StockMovement::query()->count())->toBe(0);
    });

    it('labels the stock field "Opening Stock" when creating', function () {
        actingAsAdmin()->get(route('admin.products.create'))->assertSee('Opening Stock');
    });

    it('records no movement for an opening stock of 0', function () {
        actingAsAdmin()->post(route('admin.products.store'), productInput(['stock_quantity' => 0]));

        expect(StockMovement::query()->count())->toBe(0);
    });

    it('removes images, deletes their files and sets the cover', function () {
        $product = productWithImages(3);
        [$first, $second, $third] = $product->images->all();

        actingAsAdmin()->put(route('admin.products.update', $product), productInput([
            'name' => $product->name,
            'remove_images' => [$first->id],
            'cover_image' => $third->id,
        ]))->assertSessionHasNoErrors();

        expect($product->fresh()->images->pluck('id')->all())->toBe([$third->id, $second->id]);
        Storage::disk('public')->assertMissing($first->path);
    });

    it('keeps the image limit when adding images', function () {
        $product = productWithImages(7);

        actingAsAdmin()->put(route('admin.products.update', $product), productInput([
            'name' => $product->name,
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ]))->assertSessionHasErrors(['images' => 'A product can have at most 8 images. Remove some first.']);

        expect($product->images()->count())->toBe(7);
    });

    it('allows adding images after removing some', function () {
        $product = productWithImages(8);

        actingAsAdmin()->put(route('admin.products.update', $product), productInput([
            'name' => $product->name,
            'remove_images' => [$product->images->first()->id],
            'images' => [UploadedFile::fake()->image('a.jpg')],
        ]))->assertSessionHasNoErrors();

        expect($product->images()->count())->toBe(8);
    });

    it('ignores image ids of another product', function () {
        $product = productWithImages(1);
        $otherImage = productWithImages(1)->images->first();

        actingAsAdmin()->put(route('admin.products.update', $product), productInput([
            'name' => $product->name,
            'remove_images' => [$otherImage->id],
        ]));

        $this->assertModelExists($otherImage);
    });
});

describe('cost price (admin only)', function () {
    it('hides the cost price field from staff', function () {
        $product = Product::factory()->withDefaultVariant(['cost_price' => 340000])->create();

        actingAsStaff()->get(route('admin.products.edit', $product))
            ->assertDontSee('name="cost_price"', escape: false)
            ->assertDontSee('3400');

        actingAsAdmin()->get(route('admin.products.edit', $product))
            ->assertSee('name="cost_price"', escape: false)
            ->assertSee('value="3400"', escape: false);
    });

    it('ignores a cost price posted by staff', function () {
        $product = Product::factory()->withDefaultVariant(['cost_price' => 340000])->create();

        actingAsStaff()->put(route('admin.products.update', $product), productInput([
            'name' => $product->name,
            'cost_price' => '1',
        ]))->assertSessionHasNoErrors();

        expect($product->defaultVariant()->first()->cost_price)->toBe(340000);
    });
});

describe('list', function () {
    it('lists products with price, stock and status', function () {
        Product::factory()->active()->withDefaultVariant(['sku' => 'NS-EL-0012', 'price' => 485000, 'stock_quantity' => 3])
            ->create(['name' => 'Ceiling Fan', 'category_id' => $this->fans->id]);

        actingAsStaff()->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Ceiling Fan')
            ->assertSee('SKU: NS-EL-0012')
            ->assertSee('৳ 4,850')
            ->assertSee('stock-low')
            ->assertSee('1 product in your catalog.');
    });

    it('filters by tab and shows counts', function () {
        Product::factory()->active()->withDefaultVariant()->create(['name' => 'Active Fan']);
        Product::factory()->withDefaultVariant()->create(['name' => 'Draft Fan']);
        Product::factory()->archived()->withDefaultVariant(['stock_quantity' => 0])->create(['name' => 'Old Fan']);

        actingAsAdmin()->get(route('admin.products.index', ['tab' => 'draft']))
            ->assertSee('Draft Fan')
            ->assertDontSee('Active Fan')
            ->assertSeeInOrder(['All', '3', 'Active', '1', 'Draft', '1', 'Archived', '1', 'Out of stock', '1']);

        actingAsAdmin()->get(route('admin.products.index', ['tab' => 'out-of-stock']))
            ->assertSee('Old Fan')
            ->assertDontSee('Draft Fan');
    });

    it('searches by name or SKU', function () {
        Product::factory()->withDefaultVariant(['sku' => 'NS-EL-0099'])->create(['name' => 'Ceiling Fan']);
        Product::factory()->withDefaultVariant(['sku' => 'NS-HK-0001'])->create(['name' => 'Kettle']);

        actingAsAdmin()->get(route('admin.products.index', ['search' => 'ceiling']))->assertSee('Ceiling Fan')->assertDontSee('Kettle');
        actingAsAdmin()->get(route('admin.products.index', ['search' => 'HK-0001']))->assertSee('Kettle')->assertDontSee('Ceiling Fan');
    });

    it('includes subcategories when filtering by a department', function () {
        Product::factory()->withDefaultVariant()->create(['name' => 'Ceiling Fan', 'category_id' => $this->fans->id]);
        Product::factory()->withDefaultVariant()->create(['name' => 'Kettle']);

        actingAsAdmin()->get(route('admin.products.index', ['category' => $this->electrical->id]))
            ->assertSee('Ceiling Fan')
            ->assertDontSee('Kettle');
    });

    it('paginates 20 per page', function () {
        Product::factory()->count(21)->withDefaultVariant()->create();

        actingAsAdmin()->get(route('admin.products.index'))->assertSee('Showing 1–20 of 21 products');
    });

    it('shows empty states', function () {
        actingAsAdmin()->get(route('admin.products.index'))->assertSee('No products yet');
        actingAsAdmin()->get(route('admin.products.index', ['search' => 'nothing']))->assertSee('No products match these filters');
    });
});

describe('delete and bulk', function () {
    it('deletes a product with its variants and image files', function () {
        $product = productWithImages(2);
        $paths = $product->images->pluck('path');

        actingAsAdmin()->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertModelMissing($product);
        expect(ProductVariant::query()->count())->toBe(0);
        $paths->each(fn (string $path) => Storage::disk('public')->assertMissing($path));
    });

    it('publishes, drafts and archives selected products', function (string $action, ProductStatus $expected) {
        $products = Product::factory()->count(2)->withDefaultVariant()->create();
        $untouched = Product::factory()->withDefaultVariant()->create();

        actingAsStaff()->from(route('admin.products.index'))
            ->post(route('admin.products.bulk'), ['action' => $action, 'ids' => $products->modelKeys()])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success', '2 products updated.');

        $products->each(fn (Product $product) => expect($product->fresh()->status)->toBe($expected));
        expect($untouched->fresh()->status)->toBe(ProductStatus::Draft);
    })->with([
        ['publish', ProductStatus::Active],
        ['draft', ProductStatus::Draft],
        ['archive', ProductStatus::Archived],
    ]);

    it('sets published_at when publishing in bulk', function () {
        $product = Product::factory()->withDefaultVariant()->create();

        actingAsAdmin()->post(route('admin.products.bulk'), ['action' => 'publish', 'ids' => [$product->id, 9999]]);

        expect($product->fresh()->published_at)->not->toBeNull();
    });

    it('validates bulk actions', function (array $input, string $errorField) {
        actingAsAdmin()->post(route('admin.products.bulk'), $input)->assertSessionHasErrors($errorField);
    })->with([
        'no products' => [['action' => 'publish', 'ids' => []], 'ids'],
        'no action' => [['action' => '', 'ids' => [1]], 'action'],
        'unknown action' => [['action' => 'delete', 'ids' => [1]], 'action'],
    ]);
});

describe('categories and brands', function () {
    it('counts products and blocks deleting a category or brand in use', function () {
        $brand = Brand::factory()->create();
        Product::factory()->withDefaultVariant()->create(['category_id' => $this->fans->id, 'brand_id' => $brand->id]);

        actingAsAdmin()->delete(route('admin.categories.destroy', $this->fans))
            ->assertSessionHas('error', 'Move or delete its products first.');
        actingAsAdmin()->delete(route('admin.brands.destroy', $brand))
            ->assertSessionHas('error', 'This brand is used by products. Change their brand first.');

        $this->assertModelExists($this->fans);
        $this->assertModelExists($brand);
    });
});

describe('authorization', function () {
    it('keeps guests out and forbids customers', function (string $method, string $routeName, bool $needsProduct) {
        $product = Product::factory()->withDefaultVariant()->create();
        $url = $needsProduct ? route($routeName, $product) : route($routeName);

        $this->{$method}($url)->assertRedirect(route('admin.login'));
        actingAsCustomer()->{$method}($url)->assertForbidden();
    })->with([
        ['get', 'admin.products.index', false],
        ['get', 'admin.products.create', false],
        ['post', 'admin.products.store', false],
        ['get', 'admin.products.edit', true],
        ['put', 'admin.products.update', true],
        ['delete', 'admin.products.destroy', true],
        ['post', 'admin.products.bulk', false],
    ]);
});

it('calculates the discount percent', function (int $price, ?int $comparePrice, int $expected) {
    expect(ProductVariant::discountPercentFor($price, $comparePrice))->toBe($expected);
})->with([
    'no old price' => [100000, null, 0],
    '13.39% rounds down' => [485050, 560000, 13],
    'exactly 20%' => [100000, 125000, 20],
    'old price not higher' => [100000, 100000, 0],
]);
