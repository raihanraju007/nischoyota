<?php

use App\Modules\Catalog\Enums\SellUnit;

it('describes what one unit of the price buys', function (SellUnit $unit, ?float $quantity, string $expected) {
    expect($unit->describe($quantity))->toBe($expected);
})->with([
    'piece without amount' => [SellUnit::Piece, null, 'pc'],
    'one piece' => [SellUnit::Piece, 1.0, 'pc'],
    'grams' => [SellUnit::Gram, 500.0, '500 g'],
    'one kilogram' => [SellUnit::Kilogram, 1.0, '1 kg'],
    'decimal litres' => [SellUnit::Litre, 1.5, '1.5 L'],
    'millilitres' => [SellUnit::Millilitre, 100.0, '100 ml'],
    'jar without amount' => [SellUnit::Jar, null, 'jar'],
    'two dozen' => [SellUnit::Dozen, 2.0, '2 dozen'],
]);

it('offers every unit in the admin dropdown', function () {
    expect(SellUnit::options())->toHaveCount(count(SellUnit::cases()))
        ->and(SellUnit::options()['gram'])->toBe('Gram (g)');
});
