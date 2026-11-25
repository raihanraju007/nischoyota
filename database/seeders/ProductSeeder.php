<?php

namespace Database\Seeders;

use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Enums\SellUnit;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\ProductSkuGenerator;
use App\Modules\Catalog\Services\ProductVariantService;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Nischoyota starter catalog: honey, ghee & oils, natural foods, clothing and plants.
 * Photos come from public/images/products: the honey bottle photos are from the Nischoyota Facebook page,
 * clothing photos are credited in CREDITS.md, and the rest are illustrated packshots to replace with real photos.
 * Prices are starting points for the owner to adjust in the admin panel.
 */
class ProductSeeder extends Seeder
{
    /**
     * name, category slug, brand, price, old price (taka), stock, photos, featured, short description, specifications
     *
     * @var list<array{0: string, 1: string, 2: ?string, 3: int, 4: ?int, 5: int, 6: list<string>, 7: bool, 8: string, 9?: array<string, string>}>
     */
    private const PRODUCTS = [
        // Honey — base price is the 500g pack
        ['Mustard Flower Honey (সরিষা ফুলের মধু)', 'flower-honey', 'Nischoyota', 450, 520, 60, ['mustard-honey.svg'], true,
            'Light, creamy raw honey collected from mustard fields in bloom. Naturally crystallises — a sign of purity.',
            ['Type' => 'Raw, unprocessed', 'Flower source' => 'Mustard (সরিষা)', 'Pack' => '500g', 'Added sugar' => 'None', 'Shelf life' => '2 years', 'Origin' => 'Bangladesh']],
        ['Litchi Flower Honey (লিচু ফুলের মধু)', 'flower-honey', 'Nischoyota', 550, 650, 40, ['litchi-honey.svg'], true,
            'Fragrant golden honey from litchi orchards, with a gentle fruity sweetness.',
            ['Type' => 'Raw, unprocessed', 'Flower source' => 'Litchi (লিচু)', 'Pack' => '500g', 'Added sugar' => 'None', 'Origin' => 'Bangladesh']],
        ['Sundarban Wild Honey (সুন্দরবনের মধু)', 'sundarban-honey', 'Nischoyota', 850, 990, 25, ['honey-bottle-2.jpg', 'sundarban-honey.svg'], true,
            'Dark, rich wild honey from the Sundarbans mangrove forest. Bold flavour and naturally thin texture.',
            ['Type' => 'Wild forest honey', 'Flower source' => 'Mangrove (Khalisha, Goran)', 'Pack' => '500g', 'Added sugar' => 'None', 'Origin' => 'Sundarbans, Bangladesh']],
        ['Black Seed Honey (কালোজিরা মধু)', 'black-seed-honey', 'Nischoyota', 750, 850, 30, ['honey-bottle-1.jpg', 'black-seed-honey.svg'], true,
            'Deep amber honey from kalojira (black seed) flowers — a traditional favourite for everyday wellness.',
            ['Type' => 'Raw, unprocessed', 'Flower source' => 'Black seed (কালোজিরা)', 'Pack' => '500g', 'Added sugar' => 'None', 'Origin' => 'Bangladesh']],
        ['Natural Comb Honey (চাকের মধু)', 'flower-honey', 'Nischoyota', 950, null, 8, ['comb-honey.svg'], false,
            'Honey still sealed in its natural wax comb, exactly as the bees made it.',
            ['Type' => 'Comb honey', 'Pack' => '500g', 'Origin' => 'Bangladesh']],

        // Ghee & oils
        ['Pure Cow Ghee (গাওয়া ঘি)', 'pure-ghee', 'Nischoyota', 850, 950, 45, ['cow-ghee.svg'], true,
            'Slow-cooked the traditional way from fresh cow milk cream. Rich aroma, grainy texture, nothing added.',
            ['Made from' => 'Cow milk cream', 'Process' => 'Traditional slow-cooked', 'Pack' => '500g', 'Preservatives' => 'None', 'Shelf life' => '12 months']],
        ['Traditional Khati Ghee 1kg (খাঁটি ঘি)', 'pure-ghee', 'Nischoyota', 1650, 1800, 12, ['premium-ghee.svg'], false,
            'Family-size jar of our pure khati ghee for everyday cooking, polao and sweets.',
            ['Made from' => 'Cow milk cream', 'Pack' => '1kg', 'Preservatives' => 'None']],
        ['Cold-Pressed Mustard Oil (ঘানি ভাঙা সরিষার তেল)', 'mustard-oil', 'Nischoyota Naturals', 320, 360, 80, ['mustard-oil.svg'], true,
            'Ghani-pressed mustard oil with its natural pungent aroma — perfect for bhorta, achar and frying.',
            ['Process' => 'Wooden ghani, cold-pressed', 'Pack' => '1L', 'Chemicals' => 'None']],
        ['Black Seed Oil 100ml (কালোজিরা তেল)', 'black-seed-oil', 'Nischoyota Naturals', 280, 320, 35, ['black-seed-oil.svg'], false,
            'Pure cold-pressed kalojira oil for daily use and hair care.',
            ['Process' => 'Cold-pressed', 'Pack' => '100ml']],

        // Natural foods
        ['Medjool Dates 500g', 'dates-dry-fruits', 'Nischoyota Naturals', 1250, 1400, 20, ['dates.svg'], false,
            'Large, soft and naturally sweet premium dates.',
            ['Pack' => '500g', 'Type' => 'Medjool']],
        ['Mariam Dates 500g', 'dates-dry-fruits', 'Nischoyota Naturals', 750, 850, 0, ['dates.svg'], false,
            'Chewy, rich Mariam dates — a healthy everyday snack.',
            ['Pack' => '500g', 'Type' => 'Mariam']],

        // Clothing
        ["Men's Cotton Panjabi", 'panjabi', 'Nischoyota Fashion', 1890, 2290, 36, ['panjabi.jpg'], true,
            'Soft, breathable cotton Panjabi for Jummah, Eid and everyday comfort.',
            ['Fabric' => '100% Cotton', 'Sleeve' => 'Full', 'Fit' => 'Regular']],
        ["Men's Cotton T-Shirt", 't-shirts', 'Nischoyota Fashion', 450, 550, 80, ['tshirt.jpg'], false,
            'Everyday crew-neck T-shirt in soft combed cotton.',
            ['Fabric' => '100% Cotton', 'Fit' => 'Regular']],
        ["Men's Polo Shirt", 't-shirts', 'Nischoyota Fashion', 750, 890, 45, ['polo.jpg'], false,
            'Classic pique polo — smart enough for work, easy enough for weekends.',
            ['Fabric' => 'Cotton pique']],
        ["Men's Formal Shirt", 'shirts', 'Nischoyota Fashion', 1290, 1490, 30, ['shirt.jpg'], false,
            'Crisp long-sleeve formal shirt with a clean, tailored look.',
            ['Fabric' => 'Cotton blend', 'Sleeve' => 'Full']],

        // Plants
        ['Snake Plant (স্নেক প্ল্যান্ট)', 'indoor-plants', 'Nischoyota Garden', 450, 550, 15, ['snake-plant.svg'], false,
            'Hardy indoor plant that purifies the air and thrives on little care. Comes in a pot.',
            ['Light' => 'Low to bright indirect', 'Water' => 'Every 2–3 weeks', 'Pot' => 'Included']],
        ['Money Plant (মানি প্ল্যান্ট)', 'indoor-plants', 'Nischoyota Garden', 250, null, 20, ['money-plant.svg'], false,
            'Easy-growing trailing plant that brings fresh green to any shelf or window.',
            ['Light' => 'Indirect', 'Water' => 'Weekly', 'Pot' => 'Included']],
    ];

    /**
     * Products with options: product slug => [attribute name => values].
     *
     * @var array<string, array<string, list<string>>>
     */
    private const OPTIONS = [
        'mustard-flower-honey' => ['Weight' => ['250g', '500g', '1kg']],
        'litchi-flower-honey' => ['Weight' => ['250g', '500g', '1kg']],
        'sundarban-wild-honey' => ['Weight' => ['250g', '500g', '1kg']],
        'black-seed-honey' => ['Weight' => ['250g', '500g', '1kg']],
        'pure-cow-ghee' => ['Weight' => ['250g', '500g', '1kg']],
        'cold-pressed-mustard-oil' => ['Volume' => ['500ml', '1L', '2L']],
        'mens-cotton-panjabi' => ['Color' => ['White', 'Cream', 'Maroon'], 'Size' => ['M', 'L', 'XL']],
        'mens-cotton-t-shirt' => ['Color' => ['White', 'Green', 'Black'], 'Size' => ['M', 'L', 'XL']],
    ];

    /**
     * How each product is sold: product slug => [unit, amount]. Products not listed are sold per piece.
     * Products with weight/volume options are sold per jar/bottle (the option says the size).
     *
     * @var array<string, array{0: SellUnit, 1: ?float}>
     */
    private const SELL_UNITS = [
        'mustard-flower-honey' => [SellUnit::Jar, null],
        'litchi-flower-honey' => [SellUnit::Jar, null],
        'sundarban-wild-honey' => [SellUnit::Jar, null],
        'black-seed-honey' => [SellUnit::Jar, null],
        'natural-comb-honey' => [SellUnit::Gram, 500.0],
        'pure-cow-ghee' => [SellUnit::Jar, null],
        'traditional-khati-ghee-1kg' => [SellUnit::Kilogram, 1.0],
        'cold-pressed-mustard-oil' => [SellUnit::Bottle, null],
        'black-seed-oil-100ml' => [SellUnit::Millilitre, 100.0],
        'medjool-dates-500g' => [SellUnit::Gram, 500.0],
        'mariam-dates-500g' => [SellUnit::Gram, 500.0],
    ];

    /**
     * Price of each pack size relative to the listed (500g / 1L) price.
     *
     * @var array<string, float>
     */
    private const PACK_PRICE_FACTORS = [
        '250g' => 0.55, '500g' => 1.0, '1kg' => 1.9, '2kg' => 3.7,
        '500ml' => 0.55, '1L' => 1.0, '2L' => 1.95,
    ];

    public function run(ProductSkuGenerator $skuGenerator, ProductVariantService $variantService, InventoryService $inventoryService): void
    {
        $categories = Category::query()->pluck('id', 'slug');
        $brands = Brand::query()->pluck('id', 'name');

        foreach (self::PRODUCTS as $index => $row) {
            [$name, $categorySlug, $brand, $price, $oldPrice, $stock, $photos, $isFeatured, $shortDescription] = $row;
            $categoryId = $categories[$categorySlug];
            $slug = $this->slugFor($name);
            $pricePaisa = $price * 100;
            $oldPricePaisa = $oldPrice === null ? null : $oldPrice * 100;

            $product = Product::query()->create([
                'category_id' => $categoryId,
                'brand_id' => $brand === null ? null : $brands[$brand],
                'name' => $name,
                'slug' => $slug,
                'short_description' => $shortDescription,
                'description' => "{$shortDescription}\n\nNischoyota (নিশ্চয়তা) — প্রাকৃতিক ও খাঁটি পণ্যের নিশ্চয়তা. Every order is checked before dispatch, with Cash on Delivery across Bangladesh.",
                'specifications' => $this->specificationRows($row[9] ?? []),
                'sell_unit' => self::SELL_UNITS[$slug][0] ?? SellUnit::Piece,
                'unit_quantity' => self::SELL_UNITS[$slug][1] ?? null,
                'status' => ProductStatus::Active,
                'is_featured' => $isFeatured,
                'min_price' => $pricePaisa,
                'max_discount_percent' => ProductVariant::discountPercentFor($pricePaisa, $oldPricePaisa),
                'published_at' => now()->subDays(count(self::PRODUCTS) - $index),
            ]);

            $variant = $product->variants()->create([
                'sku' => $skuGenerator->generateFor($categoryId),
                'price' => $pricePaisa,
                'compare_price' => $oldPricePaisa,
                'cost_price' => (int) round($pricePaisa * 0.7),
                'is_default' => true,
            ]);
            $inventoryService->recordOpeningStock($variant->id, $stock, userId: null);

            foreach ($photos as $position => $photo) {
                $product->images()->create(['path' => $this->copyPhoto($photo, $slug, $position), 'sort_order' => $position]);
            }
        }

        $this->addOptions($variantService, $inventoryService);
    }

    /**
     * "Mustard Flower Honey (সরিষা ফুলের মধু)" → "mustard-flower-honey": the Bangla name in brackets stays out of the URL.
     */
    private function slugFor(string $name): string
    {
        return Str::slug(trim((string) preg_replace('/\s*\(.*\)\s*/u', ' ', $name)));
    }

    private function addOptions(ProductVariantService $variantService, InventoryService $inventoryService): void
    {
        foreach (self::OPTIONS as $productSlug => $options) {
            $product = Product::query()->where('slug', $productSlug)->firstOrFail();
            $selection = [];

            foreach ($options as $attributeName => $values) {
                $attribute = ProductAttribute::query()->where('name', $attributeName)->firstOrFail();
                $selection[$attribute->id] = $attribute->values()->whereIn('value', $values)->pluck('id')->all();
            }

            $variantService->generate($product, $selection);
            $this->priceByPackSize($product);

            $product->variants()->where('stock_quantity', 0)->each(
                fn (ProductVariant $variant) => $inventoryService->recordOpeningStock($variant->id, random_int(5, 30), userId: null),
            );
        }
    }

    /**
     * Scales each weight/volume variant from the listed price, then refreshes the product's cached prices.
     */
    private function priceByPackSize(Product $product): void
    {
        $variants = $product->variants()->with('attributeValues')->get();
        $basePrice = (int) $variants->firstWhere('is_default', true)?->price;
        $baseComparePrice = $variants->firstWhere('is_default', true)?->compare_price;

        foreach ($variants as $variant) {
            $factor = $variant->attributeValues
                ->map(fn ($value): ?float => self::PACK_PRICE_FACTORS[$value->value] ?? null)
                ->filter()
                ->first();

            if ($factor === null) {
                continue;
            }

            $variant->update([
                'price' => (int) (round($basePrice * $factor / 1000) * 1000),
                'compare_price' => $baseComparePrice === null ? null : (int) (round($baseComparePrice * $factor / 1000) * 1000),
                'cost_price' => (int) round($basePrice * $factor * 0.7),
            ]);
        }

        $variants = $product->variants()->get();
        $product->update([
            'min_price' => (int) $variants->min('price'),
            'max_discount_percent' => (int) $variants->max(fn (ProductVariant $variant): int => ProductVariant::discountPercentFor($variant->price, $variant->compare_price)),
        ]);
    }

    /**
     * @param  array<string, string>  $specifications
     * @return list<array{label: string, value: string}>|null
     */
    private function specificationRows(array $specifications): ?array
    {
        if ($specifications === []) {
            return null;
        }

        return array_map(
            fn (string $label, string $value): array => ['label' => $label, 'value' => $value],
            array_keys($specifications),
            $specifications,
        );
    }

    private function copyPhoto(string $photo, string $slug, int $position): string
    {
        $extension = pathinfo($photo, PATHINFO_EXTENSION);
        $suffix = $position === 0 ? '' : '-'.($position + 1);
        $path = "products/{$slug}{$suffix}.{$extension}";

        Storage::disk('public')->put($path, file_get_contents(public_path("images/products/{$photo}")));

        return $path;
    }
}
