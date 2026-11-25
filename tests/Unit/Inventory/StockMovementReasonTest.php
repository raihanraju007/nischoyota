<?php

use App\Modules\Inventory\Enums\StockMovementReason;

it('turns the entered quantity into a stock change', function (StockMovementReason $reason, int $quantity, int $currentStock, int $expected) {
    expect($reason->quantityChangeFor($quantity, $currentStock))->toBe($expected);
})->with([
    'restock adds' => [StockMovementReason::Restock, 5, 10, 5],
    'opening stock adds' => [StockMovementReason::OpeningStock, 5, 0, 5],
    'damaged removes' => [StockMovementReason::Damaged, 3, 10, -3],
    'order placed removes' => [StockMovementReason::OrderPlaced, 2, 10, -2],
    'order cancelled adds back' => [StockMovementReason::OrderCancelled, 2, 8, 2],
    'stock count up' => [StockMovementReason::StockCount, 12, 10, 2],
    'stock count down' => [StockMovementReason::StockCount, 7, 10, -3],
    'stock count same' => [StockMovementReason::StockCount, 10, 10, 0],
]);

it('offers only restock, damaged and stock count in the admin', function () {
    expect(StockMovementReason::manualReasons())->toBe([
        StockMovementReason::Restock,
        StockMovementReason::Damaged,
        StockMovementReason::StockCount,
    ]);
});
