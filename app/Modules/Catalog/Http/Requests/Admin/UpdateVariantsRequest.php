<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Data\VariantRowData;
use App\Modules\Catalog\Models\Product;
use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The variants table: variants[{id}][sku|price|compare_price|cost_price|is_active|product_image_id]. Stock is read-only (ADR-013).
 */
final class UpdateVariantsRequest extends FormRequest
{
    private const SKU_PATTERN = '/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/';

    private const MAX_PRICE_TAKA = 10_000_000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        $rules = [
            'variants' => ['required', 'array'],
            'variants.*.price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:'.self::MAX_PRICE_TAKA],
            'variants.*.compare_price' => ['nullable', 'numeric', 'decimal:0,2', 'gt:variants.*.price', 'max:'.self::MAX_PRICE_TAKA],
            'variants.*.cost_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRICE_TAKA],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.product_image_id' => ['nullable', 'integer', Rule::exists('product_images', 'id')->where('product_id', $product->id)],
            'default_variant' => ['required', 'integer'],
        ];

        foreach (array_keys((array) $this->input('variants', [])) as $variantId) {
            $rules["variants.{$variantId}.sku"] = [
                'nullable', 'string', 'max:40', 'regex:'.self::SKU_PATTERN,
                Rule::unique('product_variants', 'sku')->ignore((int) $variantId),
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'variants.*.sku.regex' => __('Use only capital letters, numbers and dashes.'),
            'variants.*.compare_price.gt' => __('The old price must be higher than the price.'),
            'variants.*.product_image_id.exists' => __('Choose one of this product\'s photos.'),
        ];
    }

    /**
     * Two rows with the same SKU (the unique rule only sees the database, not the other rows).
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $seenSkus = [];

                foreach ((array) $this->input('variants', []) as $variantId => $row) {
                    $sku = $row['sku'] ?? null;

                    if (! filled($sku)) {
                        continue;
                    }

                    if (isset($seenSkus[$sku])) {
                        $validator->errors()->add("variants.{$variantId}.sku", __('Two variants have the same SKU.'));
                    }

                    $seenSkus[$sku] = true;
                }
            },
        ];
    }

    /**
     * @return list<VariantRowData>
     */
    public function rows(): array
    {
        $canEditCostPrice = $this->user()->can('manage-store');
        $rows = [];

        foreach ($this->input('variants') as $variantId => $row) {
            $rows[] = new VariantRowData(
                variantId: (int) $variantId,
                sku: filled($row['sku'] ?? null) ? (string) $row['sku'] : null,
                price: Money::fromTaka($row['price']),
                comparePrice: filled($row['compare_price'] ?? null) ? Money::fromTaka($row['compare_price']) : null,
                costPrice: filled($row['cost_price'] ?? null) ? Money::fromTaka($row['cost_price']) : null,
                shouldUpdateCostPrice: $canEditCostPrice,
                isActive: (bool) ($row['is_active'] ?? false),
                productImageId: filled($row['product_image_id'] ?? null) ? (int) $row['product_image_id'] : null,
            );
        }

        return $rows;
    }

    public function defaultVariantId(): int
    {
        return $this->integer('default_variant');
    }

    protected function prepareForValidation(): void
    {
        $variants = (array) $this->input('variants', []);

        foreach ($variants as $variantId => $row) {
            if (is_array($row) && filled($row['sku'] ?? null)) {
                $variants[$variantId]['sku'] = mb_strtoupper(trim((string) $row['sku']));
            }
        }

        $this->merge(['variants' => $variants]);
    }
}
