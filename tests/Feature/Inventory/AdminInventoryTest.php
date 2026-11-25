<?php

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\AttributeValue;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Data\StockAdjustmentData;
use App\Modules\Inventory\Enums\StockMovementReason;
use App\Modules\Inventory\Exceptions\InsufficientStockException;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\InventoryService;

function stockedVariant(int $stock, string $productName = 'Ceiling Fan', string $sku = 'NS-EL-0001'): ProductVariant
{
    return Product::factory()->withDefaultVariant(['stock_quantity' => $stock, 'sku' => $sku])
        ->create(['name' => $productName])
        ->defaultVariant()->first();
}

describe('adjust', function () {
    it('restocks and logs who, why and the balance', function () {
        $variant = stockedVariant(5);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->post(route('admin.inventory.adjust', $variant), ['reason' => 'restock', 'quantity' => 20, 'note' => 'Invoice #123'])
            ->assertRedirect(route('admin.inventory.show', $variant))
            ->assertSessionHas('success', 'Stock updated to 25.');

        $movement = StockMovement::query()->sole();
        expect($variant->fresh()->stock_quantity)->toBe(25)
            ->and($movement->quantity_change)->toBe(20)
            ->and($movement->stock_after)->toBe(25)
            ->and($movement->reason)->toBe(StockMovementReason::Restock)
            ->and($movement->user_id)->toBe($staff->id)
            ->and($movement->note)->toBe('Invoice #123');
    });

    it('removes damaged items', function () {
        $variant = stockedVariant(5);

        actingAsAdmin()->post(route('admin.inventory.adjust', $variant), ['reason' => 'damaged', 'quantity' => 2]);

        expect($variant->fresh()->stock_quantity)->toBe(3)
            ->and(StockMovement::query()->sole()->quantity_change)->toBe(-2);
    });

    it('sets the counted stock', function (int $counted, int $expectedChange) {
        $variant = stockedVariant(5);

        actingAsAdmin()->post(route('admin.inventory.adjust', $variant), ['reason' => 'stock_count', 'quantity' => $counted]);

        expect($variant->fresh()->stock_quantity)->toBe($counted)
            ->and(StockMovement::query()->sole()->quantity_change)->toBe($expectedChange);
    })->with([
        'more than recorded' => [8, 3],
        'fewer than recorded' => [1, -4],
        'none left' => [0, -5],
    ]);

    it('records nothing when the count matches', function () {
        $variant = stockedVariant(5);

        actingAsAdmin()->post(route('admin.inventory.adjust', $variant), ['reason' => 'stock_count', 'quantity' => 5])
            ->assertSessionHas('info', 'No change: the stock is already 5.');

        expect(StockMovement::query()->count())->toBe(0);
    });

    it('never lets stock go below zero', function () {
        $variant = stockedVariant(2);

        actingAsAdmin()->post(route('admin.inventory.adjust', $variant), ['reason' => 'damaged', 'quantity' => 3])
            ->assertSessionHasErrors(['quantity' => 'Only 2 in stock.']);

        expect($variant->fresh()->stock_quantity)->toBe(2)
            ->and(StockMovement::query()->count())->toBe(0);
    });

    it('validates the adjustment', function (array $input, string $errorField) {
        actingAsAdmin()->post(route('admin.inventory.adjust', stockedVariant(5)), $input)->assertSessionHasErrors($errorField);
    })->with([
        'no reason' => [['quantity' => 1], 'reason'],
        'order reasons are not manual' => [['reason' => 'order_placed', 'quantity' => 1], 'reason'],
        'opening stock is not manual' => [['reason' => 'opening_stock', 'quantity' => 1], 'reason'],
        'zero restock' => [['reason' => 'restock', 'quantity' => 0], 'quantity'],
        'restock too large' => [['reason' => 'restock', 'quantity' => 100001], 'quantity'],
        'fraction' => [['reason' => 'restock', 'quantity' => 1.5], 'quantity'],
        'negative count' => [['reason' => 'stock_count', 'quantity' => -1], 'quantity'],
        'note too long' => [['reason' => 'restock', 'quantity' => 1, 'note' => str_repeat('a', 256)], 'note'],
    ]);
});

describe('service', function () {
    it('throws when removing more than available', function () {
        $variant = stockedVariant(1);

        app(InventoryService::class)->adjust($variant->id, new StockAdjustmentData(StockMovementReason::Damaged, 2, null, null));
    })->throws(InsufficientStockException::class, 'Only 1 in stock.');

    it('records opening stock only when above zero', function () {
        $variant = stockedVariant(0);
        $service = app(InventoryService::class);

        expect($service->recordOpeningStock($variant->id, 0, null))->toBeNull();
        expect($service->recordOpeningStock($variant->id, 4, null)->reason)->toBe(StockMovementReason::OpeningStock)
            ->and($variant->fresh()->stock_quantity)->toBe(4);
    });
});

describe('pages', function () {
    it('lists variants with stock status, lowest stock first', function () {
        stockedVariant(40, 'Kettle', 'NS-HK-0001');
        stockedVariant(3, 'Fan', 'NS-EL-0001');
        stockedVariant(0, 'Wallet', 'NS-FA-0001');

        actingAsStaff()->get(route('admin.inventory.index'))
            ->assertOk()
            ->assertSeeInOrder(['Wallet', 'Out of stock', 'Fan', 'Low stock', 'Kettle', 'In stock'])
            ->assertSee('Showing 1–3 of 3 variants');
    });

    it('shows the variant label for products with options', function () {
        $variant = stockedVariant(5);
        $variant->attributeValues()->attach(AttributeValue::factory()->create(['value' => 'White']));

        actingAsAdmin()->get(route('admin.inventory.index'))->assertSee('White');
    });

    it('filters by tab and search', function () {
        stockedVariant(40, 'Kettle', 'NS-HK-0001');
        stockedVariant(3, 'Fan', 'NS-EL-0001');
        stockedVariant(0, 'Wallet', 'NS-FA-0001');

        actingAsAdmin()->get(route('admin.inventory.index', ['tab' => 'low']))
            ->assertSee('Fan')->assertDontSee('Kettle')->assertDontSee('Wallet')
            ->assertSeeInOrder(['All', '3', 'Low stock', '1', 'Out of stock', '1']);

        actingAsAdmin()->get(route('admin.inventory.index', ['tab' => 'out']))->assertSee('Wallet')->assertDontSee('Fan');
        actingAsAdmin()->get(route('admin.inventory.index', ['search' => 'HK-0001']))->assertSee('Kettle')->assertDontSee('Wallet');
        actingAsAdmin()->get(route('admin.inventory.index', ['search' => 'kett']))->assertSee('Kettle')->assertDontSee('Wallet');
    });

    it('shows the history newest first with the user', function () {
        $variant = stockedVariant(10);
        $admin = User::factory()->admin()->create(['name' => 'Rahim']);
        StockMovement::factory()->for($variant, 'variant')->create(['reason' => StockMovementReason::OpeningStock, 'quantity_change' => 10, 'stock_after' => 10]);
        StockMovement::factory()->for($variant, 'variant')->create(['reason' => StockMovementReason::Damaged, 'quantity_change' => -2, 'stock_after' => 8, 'user_id' => $admin->id, 'note' => 'Broken box']);

        actingAsAdmin()->get(route('admin.inventory.show', $variant))
            ->assertOk()
            ->assertSee('SKU: NS-EL-0001')
            ->assertSeeInOrder(['Damaged / lost', 'Broken box', '-2', '8', 'Rahim', 'Opening stock', '+10', '10', 'System'])
            ->assertSee('Add received items to stock.');
    });

    it('links to inventory from the sidebar', function () {
        actingAsStaff()->get(route('admin.dashboard'))->assertSee(route('admin.inventory.index'));
    });
});

it('keeps guests and customers out', function (string $method, string $routeName, bool $needsVariant) {
    $variant = stockedVariant(5);
    $url = $needsVariant ? route($routeName, $variant) : route($routeName);

    $this->{$method}($url)->assertRedirect(route('admin.login'));
    actingAsCustomer()->{$method}($url)->assertForbidden();
})->with([
    ['get', 'admin.inventory.index', false],
    ['get', 'admin.inventory.show', true],
    ['post', 'admin.inventory.adjust', true],
]);
