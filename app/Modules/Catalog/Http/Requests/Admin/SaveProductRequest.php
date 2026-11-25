<?php

namespace App\Modules\Catalog\Http\Requests\Admin;

use App\Modules\Catalog\Data\DefaultVariantData;
use App\Modules\Catalog\Data\ProductData;
use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Enums\SellUnit;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ProductService;
use App\Support\Money;
use App\Support\NormalizesStringInput;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

/**
 * Create and update. Prices arrive in taka and leave as paisa; cost price is applied for admins only (ADR-012).
 */
final class SaveProductRequest extends FormRequest
{
    use NormalizesStringInput;

    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private const SKU_PATTERN = '/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/';

    private const MAX_PRICE_TAKA = 10_000_000;

    private const MAX_SPECIFICATIONS = 30;

    private ?bool $hasOptions = null;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $product = $this->product();

        return [
            ...($this->hasOptions() ? [] : $this->defaultVariantRules($product)),
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:'.self::SLUG_PATTERN, Rule::unique('products', 'slug')->ignore($product)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:20000'],
            'sell_unit' => ['nullable', Rule::enum(SellUnit::class)],
            'unit_quantity' => ['nullable', 'numeric', 'decimal:0,2', 'gt:0', 'max:100000'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'is_featured' => ['nullable', 'boolean'],
            'allow_preorder' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],

            'images' => ['nullable', 'array', 'max:'.ProductService::MAX_IMAGES],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'cover_image' => ['nullable', 'integer'],

            'specifications' => ['nullable', 'array', 'max:'.self::MAX_SPECIFICATIONS],
            'specifications.*.label' => ['required_with:specifications.*.value', 'nullable', 'string', 'max:60'],
            'specifications.*.value' => ['required_with:specifications.*.label', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Price, SKU and stock of a simple product's default variant.
     *
     * @return array<string, array<int, mixed>>
     */
    private function defaultVariantRules(?Product $product): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:40', 'regex:'.self::SKU_PATTERN, Rule::unique('product_variants', 'sku')->ignore($product?->defaultVariant)],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:'.self::MAX_PRICE_TAKA],
            'compare_price' => ['nullable', 'numeric', 'decimal:0,2', 'gt:price', 'max:'.self::MAX_PRICE_TAKA],
            'cost_price' => ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:'.self::MAX_PRICE_TAKA],
            ...($product === null ? ['stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000']] : []),
            'weight_grams' => ['nullable', 'integer', 'min:1', 'max:1000000'],

        ];
    }

    private function hasOptions(): bool
    {
        return $this->hasOptions ??= $this->product()?->hasOptions() ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.regex' => __('Use only lowercase letters, numbers and dashes.'),
            'sku.regex' => __('Use only capital letters, numbers and dashes, e.g. NS-EL-0034.'),
            'compare_price.gt' => __('The old price must be higher than the selling price.'),
            'specifications.*.label.required_with' => __('Each specification needs a label.'),
            'specifications.*.value.required_with' => __('Each specification needs a value.'),
            'images.max' => __('Upload at most :max images.'),
        ];
    }

    public function toData(): ProductData
    {
        return new ProductData(
            name: $this->string('name')->trim()->toString(),
            slug: $this->nullableString('slug'),
            categoryId: $this->integer('category_id'),
            brandId: $this->filled('brand_id') ? $this->integer('brand_id') : null,
            shortDescription: $this->nullableString('short_description'),
            description: $this->nullableString('description'),
            specifications: $this->specifications(),
            status: $this->enum('status', ProductStatus::class),
            isFeatured: $this->boolean('is_featured'),
            allowsPreorder: $this->boolean('allow_preorder'),
            metaTitle: $this->nullableString('meta_title'),
            metaDescription: $this->nullableString('meta_description'),
            variant: $this->hasOptions() ? null : new DefaultVariantData(
                sku: $this->nullableString('sku'),
                price: Money::fromTaka($this->input('price')),
                comparePrice: $this->filled('compare_price') ? Money::fromTaka($this->input('compare_price')) : null,
                costPrice: $this->filled('cost_price') ? Money::fromTaka($this->input('cost_price')) : null,
                shouldUpdateCostPrice: $this->user()->can('manage-store'),
                openingStock: $this->product() === null ? $this->integer('stock_quantity') : null,
                weightGrams: $this->filled('weight_grams') ? $this->integer('weight_grams') : null,
            ),
            newImages: $this->newImages(),
            removedImageIds: array_map(intval(...), $this->input('remove_images', [])),
            coverImageId: $this->filled('cover_image') ? $this->integer('cover_image') : null,
            actingUserId: $this->user()->id,
            sellUnit: $this->enum('sell_unit', SellUnit::class) ?? SellUnit::Piece,
            unitQuantity: $this->filled('unit_quantity') ? (float) $this->input('unit_quantity') : null,
        );
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeStrings([
            'sku' => fn (string $sku): string => mb_strtoupper(trim($sku)),
            'slug' => fn (string $slug): string => mb_strtolower(trim($slug)),
        ]);
        $this->merge(['specifications' => $this->specificationRowsWithContent()]);
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function specifications(): array
    {
        return array_map(fn (array $row): array => [
            'label' => trim((string) $row['label']),
            'value' => trim((string) $row['value']),
        ], $this->input('specifications', []));
    }

    /**
     * Drops rows where both label and value are empty, so blank repeater rows are not errors.
     *
     * @return list<array<string, mixed>>
     */
    private function specificationRowsWithContent(): array
    {
        $rows = $this->input('specifications', []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, fn (mixed $row): bool => is_array($row)
            && (trim((string) ($row['label'] ?? '')) !== '' || trim((string) ($row['value'] ?? '')) !== '')));
    }

    /**
     * @return list<UploadedFile>
     */
    private function newImages(): array
    {
        return array_values(array_filter((array) $this->file('images', []), fn (mixed $file): bool => $file instanceof UploadedFile));
    }

    private function nullableString(string $key): ?string
    {
        return $this->filled($key) ? $this->string($key)->trim()->toString() : null;
    }

    private function product(): ?Product
    {
        $product = $this->route('product');

        return $product instanceof Product ? $product->loadMissing('defaultVariant') : null;
    }
}
