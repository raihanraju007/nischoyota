<?php

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Shopping\Enums\CartLineState;
use App\Modules\Shopping\Services\CartService;

function variantForState(int $stock, bool $preorder = false, bool $isActive = true, ProductStatus $status = ProductStatus::Active): ProductVariant
{
    $product = (new Product)->forceFill(['status' => $status, 'allow_preorder' => $preorder])
        ->setRelation('category', (new Category)->forceFill(['is_active' => true]));

    return (new ProductVariant)->forceFill(['stock_quantity' => $stock, 'is_active' => $isActive])->setRelation('product', $product);
}

it('works out the line state', function (ProductVariant $variant, int $quantity, CartLineState $expected) {
    $service = (new ReflectionClass(CartService::class))->newInstanceWithoutConstructor();

    expect($service->lineStateFor($variant, $quantity))->toBe($expected);
})->with([
    'enough stock' => fn () => [variantForState(5), 5, CartLineState::Available],
    'too few left' => fn () => [variantForState(2), 3, CartLineState::NotEnoughStock],
    'sold out' => fn () => [variantForState(0), 1, CartLineState::OutOfStock],
    'pre-order' => fn () => [variantForState(0, preorder: true), 4, CartLineState::Preorder],
    'inactive variant' => fn () => [variantForState(5, isActive: false), 1, CartLineState::Unavailable],
    'draft product' => fn () => [variantForState(5, status: ProductStatus::Draft), 1, CartLineState::Unavailable],
]);

it('only lets available and pre-order lines be bought', function () {
    expect(collect(CartLineState::cases())->filter->isBuyable()->values()->all())
        ->toBe([CartLineState::Available, CartLineState::Preorder]);
});
