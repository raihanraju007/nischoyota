<?php

use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->color = ProductAttribute::factory()->create(['name' => 'Color']);
    $this->white = AttributeValue::factory()->for($this->color, 'attribute')->create(['value' => 'White', 'sort_order' => 1]);
    $this->black = AttributeValue::factory()->for($this->color, 'attribute')->create(['value' => 'Black', 'sort_order' => 2]);
    $this->size = ProductAttribute::factory()->create(['name' => 'Size']);
    $this->small = AttributeValue::factory()->for($this->size, 'attribute')->create(['value' => 'S', 'sort_order' => 1]);
    $this->large = AttributeValue::factory()->for($this->size, 'attribute')->create(['value' => 'L', 'sort_order' => 2]);

    $this->product = Product::factory()->withDefaultVariant([
        'sku' => 'NS-EL-0001', 'price' => 100000, 'compare_price' => 120000, 'cost_price' => 70000, 'stock_quantity' => 7,
    ])->create();
});

function generateVariants(array $values): TestResponse
{
    return actingAsAdmin()->post(route('admin.products.variants.generate', test()->product), ['values' => $values]);
}

/**
 * @return Collection<string, ProductVariant> label => variant
 */
function variantsByLabel(): Collection
{
    return test()->product->variants()->with('attributeValues.attribute')->get()->keyBy(fn (ProductVariant $variant) => $variant->label());
}

describe('generate', function () {
    it('creates every combination and keeps the original variant as the first one', function () {
        generateVariants([
            $this->color->id => [$this->white->id, $this->black->id],
            $this->size->id => [$this->small->id, $this->large->id],
        ])->assertRedirect(route('admin.products.variants.edit', $this->product))->assertSessionHas('success');

        $variants = variantsByLabel();

        expect($variants->keys()->sort()->values()->all())->toBe(['Black / L', 'Black / S', 'White / L', 'White / S'])
            ->and($variants['White / S']->sku)->toBe('NS-EL-0001')
            ->and($variants['White / S']->stock_quantity)->toBe(7)
            ->and($variants['White / S']->is_default)->toBeTrue()
            ->and($variants['Black / L']->price)->toBe(100000)
            ->and($variants['Black / L']->compare_price)->toBe(120000)
            ->and($variants['Black / L']->cost_price)->toBe(70000)
            ->and($variants['Black / L']->stock_quantity)->toBe(0)
            ->and($variants->where('is_default', true))->toHaveCount(1)
            ->and($this->product->hasOptions())->toBeTrue();
    });

    it('keeps existing combinations and deletes unticked ones', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);
        $black = variantsByLabel()['Black'];
        $black->update(['stock_quantity' => 40, 'sku' => 'BLACK-1']);

        generateVariants([
            $this->color->id => [$this->black->id],
            $this->size->id => [$this->large->id],
        ]);
        generateVariants([$this->color->id => [$this->black->id]]);

        $variants = variantsByLabel();
        expect($variants->keys()->all())->toBe(['Black'])
            ->and($variants['Black']->is_default)->toBeTrue();
    });

    it('keeps an existing combination with its SKU and stock', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);
        variantsByLabel()['Black']->update(['stock_quantity' => 40, 'sku' => 'BLACK-1']);

        generateVariants([$this->color->id => [$this->black->id]]);

        expect(variantsByLabel()['Black'])->sku->toBe('BLACK-1')->stock_quantity->toBe(40);
    });

    it('turns the product back into a simple product when nothing is ticked', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);
        $default = $this->product->defaultVariant()->first();

        generateVariants([]);

        $variants = $this->product->variants()->get();
        expect($variants)->toHaveCount(1)
            ->and($variants->first()->id)->toBe($default->id)
            ->and($this->product->hasOptions())->toBeFalse();
    });

    it('rejects a value posted under another attribute', function () {
        generateVariants([$this->color->id => [$this->small->id]])
            ->assertSessionHasErrors(['values' => 'One of the chosen values does not belong to its option.']);

        expect($this->product->hasOptions())->toBeFalse();
    });

    it('limits options to 3 attributes', function () {
        $extra = collect(range(1, 2))->map(fn () => AttributeValue::factory()->create());
        $values = [
            $this->color->id => [$this->white->id],
            $this->size->id => [$this->small->id],
        ];
        foreach ($extra as $value) {
            $values[$value->attribute_id] = [$value->id];
        }

        generateVariants($values)->assertSessionHasErrors(['values' => 'Choose values from at most 3 options.']);
    });

    it('limits the number of combinations', function () {
        $many = ProductAttribute::factory()->create();
        $manyIds = AttributeValue::factory()->count(51)->for($many, 'attribute')->create()->modelKeys();

        generateVariants([
            $many->id => $manyIds,
            $this->color->id => [$this->white->id, $this->black->id],
        ])->assertSessionHasErrors(['values' => 'These options make 102 variants; the maximum is 100.']);
    });

    it('updates the cached lowest price', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);

        expect($this->product->fresh()->min_price)->toBe(100000);
    });
});

describe('update table', function () {
    beforeEach(function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);
        $variants = variantsByLabel();
        $this->whiteVariant = $variants['White'];
        $this->blackVariant = $variants['Black'];
    });

    function variantRows(array $overrides = []): array
    {
        $rows = [];
        foreach ([test()->whiteVariant, test()->blackVariant] as $variant) {
            $rows[$variant->id] = [
                'sku' => $variant->sku, 'price' => '1000', 'compare_price' => '', 'cost_price' => '',
                'stock_quantity' => 5, 'is_active' => '1', 'product_image_id' => '',
                ...($overrides[$variant->id] ?? []),
            ];
        }

        return $rows;
    }

    it('saves prices, stock, photo, active and default', function () {
        $image = ProductImage::factory()->for($this->product)->create();

        actingAsAdmin()->put(route('admin.products.variants.update', $this->product), [
            'variants' => variantRows([
                $this->whiteVariant->id => ['price' => '950.50', 'compare_price' => '1100', 'stock_quantity' => 3, 'is_active' => '0'],
                $this->blackVariant->id => ['sku' => 'blk-01', 'price' => '1200', 'cost_price' => '800', 'product_image_id' => $image->id],
            ]),
            'default_variant' => $this->blackVariant->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.products.variants.edit', $this->product));

        $white = $this->whiteVariant->fresh();
        $black = $this->blackVariant->fresh();
        expect($white->price)->toBe(95050)
            ->and($white->compare_price)->toBe(110000)
            ->and($white->is_active)->toBeFalse()
            ->and($white->is_default)->toBeFalse()
            ->and($black->sku)->toBe('BLK-01')
            ->and($black->cost_price)->toBe(80000)
            ->and($black->product_image_id)->toBe($image->id)
            ->and($black->is_default)->toBeTrue()
            ->and($this->product->fresh()->min_price)->toBe(120000);
    });

    it('validates rows', function (string $row, array $values, string $errorField) {
        ProductVariant::factory()->create(['sku' => 'TAKEN']);
        $variant = $row === 'white' ? $this->whiteVariant : $this->blackVariant;
        $field = str_replace('{id}', (string) $variant->id, $errorField);

        actingAsAdmin()->put(route('admin.products.variants.update', $this->product), [
            'variants' => variantRows([$variant->id => $values]),
            'default_variant' => $this->whiteVariant->id,
        ])->assertSessionHasErrors($field);
    })->with([
        'price zero' => ['white', ['price' => '0'], 'variants.{id}.price'],
        'old price not higher' => ['white', ['compare_price' => '900'], 'variants.{id}.compare_price'],
        'sku taken' => ['black', ['sku' => 'taken'], 'variants.{id}.sku'],
        'photo of another product' => ['black', ['product_image_id' => 999], 'variants.{id}.product_image_id'],
    ]);

    it('ignores stock posted in the variants table (stock changes only in Inventory)', function () {
        actingAsAdmin()->put(route('admin.products.variants.update', $this->product), [
            'variants' => variantRows([$this->whiteVariant->id => ['stock_quantity' => 999]]),
            'default_variant' => $this->whiteVariant->id,
        ])->assertSessionHasNoErrors();

        expect($this->whiteVariant->fresh()->stock_quantity)->toBe(7);
    });

    it('links each variant\'s stock to Inventory', function () {
        actingAsAdmin()->get(route('admin.products.variants.edit', $this->product))
            ->assertSee(route('admin.inventory.show', $this->whiteVariant));
    });

    it('rejects the same SKU twice', function () {
        actingAsAdmin()->put(route('admin.products.variants.update', $this->product), [
            'variants' => variantRows([
                $this->whiteVariant->id => ['sku' => 'SAME-1'],
                $this->blackVariant->id => ['sku' => 'SAME-1'],
            ]),
            'default_variant' => $this->whiteVariant->id,
        ])->assertSessionHasErrors(["variants.{$this->blackVariant->id}.sku" => 'Two variants have the same SKU.']);
    });

    it('requires the default variant to be active', function () {
        actingAsAdmin()->put(route('admin.products.variants.update', $this->product), [
            'variants' => variantRows([$this->whiteVariant->id => ['is_active' => '0']]),
            'default_variant' => $this->whiteVariant->id,
        ])->assertSessionHasErrors(['default_variant' => 'The default variant must be active.']);
    });

    it('rejects a default variant of another product', function () {
        $foreign = ProductVariant::factory()->create();

        actingAsAdmin()->put(route('admin.products.variants.update', $this->product), [
            'variants' => variantRows(),
            'default_variant' => $foreign->id,
        ])->assertSessionHasErrors('default_variant');
    });

    it('ignores rows of another product and cost price from staff', function () {
        $foreign = ProductVariant::factory()->create(['price' => 5000]);
        $rows = variantRows([$this->whiteVariant->id => ['cost_price' => '1']]);
        $rows[$foreign->id] = ['sku' => $foreign->sku, 'price' => '1', 'stock_quantity' => 1, 'is_active' => '1'];

        actingAsStaff()->put(route('admin.products.variants.update', $this->product), [
            'variants' => $rows,
            'default_variant' => $this->whiteVariant->id,
        ])->assertSessionHasNoErrors();

        expect($foreign->fresh()->price)->toBe(5000)
            ->and($this->whiteVariant->fresh()->cost_price)->toBe(70000);
    });
});

describe('pages', function () {
    it('shows the variants page with cost price for admins only', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);
        $white = variantsByLabel()['White'];

        actingAsAdmin()->get(route('admin.products.variants.edit', $this->product))
            ->assertOk()
            ->assertSee('2 variants')
            ->assertSee('Generate variants')
            ->assertSee('name="variants['.$white->id.'][cost_price]"', escape: false);

        actingAsStaff()->get(route('admin.products.variants.edit', $this->product))
            ->assertOk()
            ->assertDontSee('[cost_price]', escape: false);
    });

    it('replaces the price fields in the product form once the product has options', function () {
        actingAsAdmin()->get(route('admin.products.edit', $this->product))
            ->assertSee('name="price"', escape: false)
            ->assertSee('Add options and variants');

        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);

        actingAsAdmin()->get(route('admin.products.edit', $this->product))
            ->assertDontSee('name="price"', escape: false)
            ->assertSee('Manage variants');
    });

    it('saves the product form without price fields when it has options', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);

        actingAsAdmin()->put(route('admin.products.update', $this->product), [
            'name' => 'Renamed Fan',
            'category_id' => $this->product->category_id,
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        expect($this->product->fresh()->name)->toBe('Renamed Fan')
            ->and($this->product->variants()->count())->toBe(2)
            ->and($this->product->fresh()->min_price)->toBe(100000);
    });

    it('shows "from" price, total stock and variant count in the list', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);
        variantsByLabel()['Black']->update(['stock_quantity' => 5]);

        actingAsAdmin()->get(route('admin.products.index'))
            ->assertSee('from')
            ->assertSee('2 variants')
            ->assertSeeInOrder(['৳ 1,000', '12']);
    });

    it('counts a product as out of stock only when no active variant has stock', function () {
        generateVariants([$this->color->id => [$this->white->id, $this->black->id]]);
        $variants = variantsByLabel();
        $variants['White']->update(['stock_quantity' => 0]);
        $variants['Black']->update(['stock_quantity' => 3]);

        actingAsAdmin()->get(route('admin.products.index', ['tab' => 'out-of-stock']))->assertSee('No products match');

        $variants['Black']->update(['is_active' => false]);
        actingAsAdmin()->get(route('admin.products.index', ['tab' => 'out-of-stock']))->assertSee($this->product->name);
    });
});

describe('attribute guards', function () {
    it('does not delete a value or attribute used by a variant', function () {
        generateVariants([$this->color->id => [$this->white->id]]);

        actingAsAdmin()->delete(route('admin.attributes.values.destroy', [$this->color, $this->white]))
            ->assertSessionHas('error', 'This value is used by product variants. Remove it from those products first.');
        actingAsAdmin()->delete(route('admin.attributes.destroy', $this->color))
            ->assertSessionHas('error', 'Values of this option are used by product variants. Remove them from those products first.');

        $this->assertModelExists($this->white);
        actingAsAdmin()->delete(route('admin.attributes.values.destroy', [$this->color, $this->black]))->assertSessionHas('success');
    });
});

it('keeps guests and customers out', function (string $method, string $routeName) {
    $this->{$method}(route($routeName, $this->product))->assertRedirect(route('admin.login'));
    actingAsCustomer()->{$method}(route($routeName, $this->product))->assertForbidden();
})->with([
    ['get', 'admin.products.variants.edit'],
    ['post', 'admin.products.variants.generate'],
    ['put', 'admin.products.variants.update'],
]);
