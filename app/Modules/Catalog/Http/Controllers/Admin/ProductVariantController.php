<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Exceptions\InvalidVariantSetupException;
use App\Modules\Catalog\Http\Requests\Admin\GenerateVariantsRequest;
use App\Modules\Catalog\Http\Requests\Admin\UpdateVariantsRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\AttributeService;
use App\Modules\Catalog\Services\ProductVariantService;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductVariantService $variantService,
        private readonly AttributeService $attributeService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function edit(Product $product): View
    {
        return view('admin.products.variants', [
            'product' => $this->variantService->loadForVariantsPage($product),
            'attributes' => $this->attributeService->listWithValues(),
            'maxAttributes' => ProductVariantService::MAX_ATTRIBUTES,
            'maxCombinations' => ProductVariantService::MAX_COMBINATIONS,
            'lowStockThreshold' => $this->inventoryService->lowStockThreshold(),
        ]);
    }

    public function generate(GenerateVariantsRequest $request, Product $product): RedirectResponse
    {
        try {
            $variantCount = $this->variantService->generate($product, $request->selection());
        } catch (InvalidVariantSetupException $exception) {
            return back()->withInput()->withErrors(['values' => $exception->getMessage()]);
        }

        return to_route('admin.products.variants.edit', $product)
            ->with('success', trans_choice('Saved: :count variant. Now check prices and stock.|Saved: :count variants. Now check prices and stock.', $variantCount));
    }

    public function update(UpdateVariantsRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->variantService->updateVariants($product, $request->rows(), $request->defaultVariantId());
        } catch (InvalidVariantSetupException $exception) {
            return back()->withInput()->withErrors(['default_variant' => $exception->getMessage()]);
        }

        return to_route('admin.products.variants.edit', $product)->with('success', __('Variants updated.'));
    }
}
