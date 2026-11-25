<?php

use App\Support\Money;

it('formats paisa as taka for display', function (int $paisa, string $expected) {
    expect(Money::format($paisa))->toBe($expected);
})->with([
    'zero' => [0, '৳ 0'],
    'whole taka' => [485000, '৳ 4,850'],
    'with paisa' => [485050, '৳ 4,850.50'],
    'one paisa' => [1, '৳ 0.01'],
    'lakh' => [12345600, '৳ 123,456'],
]);

it('converts taka input to paisa', function (int|float|string $taka, int $expected) {
    expect(Money::fromTaka($taka))->toBe($expected);
})->with([
    'integer' => [4850, 485000],
    'numeric string' => ['4850', 485000],
    'decimal string' => ['4850.5', 485050],
    'rounds to nearest paisa' => ['12.345', 1235],
    'float precision' => [19.99, 1999],
    'zero' => ['0', 0],
]);

it('rejects negative taka amounts', function () {
    Money::fromTaka('-1');
})->throws(InvalidArgumentException::class);

it('rejects non-numeric taka input', function () {
    Money::fromTaka('abc');
})->throws(InvalidArgumentException::class);

it('converts paisa to a plain taka value for form inputs', function (int $paisa, string $expected) {
    expect(Money::toTakaInput($paisa))->toBe($expected);
})->with([
    [485000, '4850'],
    [485050, '4850.50'],
    [0, '0'],
]);
