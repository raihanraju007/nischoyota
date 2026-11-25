<?php

namespace App\Modules\Catalog\Enums;

/**
 * How a product is sold: by the piece, by weight, by volume or by pack. Shown next to the price ("৳450 / 500 g").
 */
enum SellUnit: string
{
    case Piece = 'piece';
    case Kilogram = 'kg';
    case Gram = 'gram';
    case Litre = 'litre';
    case Millilitre = 'ml';
    case Pack = 'pack';
    case Jar = 'jar';
    case Bottle = 'bottle';
    case Dozen = 'dozen';
    case Set = 'set';
    case Pair = 'pair';

    /**
     * Dropdown label in the admin form.
     */
    public function label(): string
    {
        return match ($this) {
            self::Piece => __('Piece (pc)'),
            self::Kilogram => __('Kilogram (kg)'),
            self::Gram => __('Gram (g)'),
            self::Litre => __('Litre (L)'),
            self::Millilitre => __('Millilitre (ml)'),
            self::Pack => __('Pack'),
            self::Jar => __('Jar'),
            self::Bottle => __('Bottle'),
            self::Dozen => __('Dozen'),
            self::Set => __('Set'),
            self::Pair => __('Pair'),
        };
    }

    /**
     * Short symbol shown after the price.
     */
    public function symbol(): string
    {
        return match ($this) {
            self::Piece => 'pc',
            self::Kilogram => 'kg',
            self::Gram => 'g',
            self::Litre => 'L',
            self::Millilitre => 'ml',
            self::Pack => 'pack',
            self::Jar => 'jar',
            self::Bottle => 'bottle',
            self::Dozen => 'dozen',
            self::Set => 'set',
            self::Pair => 'pair',
        };
    }

    /**
     * "500 g", "1.5 kg", "pc" (no amount, or an amount of 1 for a count unit).
     */
    public function describe(?float $quantity): string
    {
        if ($quantity === null || ($quantity == 1 && ! $this->isMeasured())) {
            return $this->symbol();
        }

        $amount = rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');

        return "{$amount} {$this->symbol()}";
    }

    /**
     * Weight and volume units, where the amount matters (500 g, 1 L).
     */
    public function isMeasured(): bool
    {
        return in_array($this, [self::Kilogram, self::Gram, self::Litre, self::Millilitre], true);
    }

    /**
     * @return array<string, string> value => label, for the admin dropdown
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $unit) {
            $options[$unit->value] = $unit->label();
        }

        return $options;
    }
}
