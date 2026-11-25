<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Enums\BulkProductAction;
use App\Modules\Catalog\Enums\ProductListTab;
use App\Modules\Catalog\Enums\ProductStatus;
use App\Modules\Catalog\Exceptions\TooManyProductImagesException;
use App\Modules\Catalog\Http\Requests\Admin\BulkProductActionRequest;
use App\Modules\Catalog\Http\Requests\Admin\ListProductsRequest;
use App\Modules\Catalog\Http\Requests\Admin\SaveProductRequest;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\BrandService;
use App\Modules\Catalog\Services\CategoryService;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
        private readonly BrandService $brandService,
        private readonly InventoryService $inventoryService,
    ) {}

    public function index(ListProductsRequest $request): View
    {
        $filter = $request->toFilter();

        return view('admin.products.index', [
            'products' => $this->productService->paginateForAdmin($filter),
            'tabCounts' => $this->productService->countPerTab($filter),
            'filter' => $filter,
            'tabs' => ProductListTab::cases(),
            'bulkActions' => BulkProductAction::cases(),
            'categoryOptions' => $this->categoryService->optionsForSelect(),
            'lowStockThreshold' => $this->inventoryService->lowStockThreshold(),
        ]);
    }

    public function create(): View
    {
        return $this->form(product: null);
    }

    public function edit(Product $product): View
    {
        return $this->form($this->productService->loadForEditing($product));
    }

    public function store(SaveProductRequest $request): RedirectResponse
    {
        try {
            $product = $this->productService->create($request->toData());
        } catch (TooManyProductImagesException $exception) {
            return back()->withInput()->withErrors(['images' => $exception->getMessage()]);
        }

        return to_route('admin.products.edit', $product)->with('success', __('Product ":name" created.', ['name' => $product->name]));
    }

    public function update(SaveProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->productService->update($product, $request->toData());
        } catch (TooManyProductImagesException $exception) {
            return back()->withInput()->withErrors(['images' => $exception->getMessage()]);
        }

        return to_route('admin.products.edit', $product)->with('success', __('Product updated.'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return to_route('admin.products.index')->with('success', __('Product ":name" deleted.', ['name' => $product->name]));
    }

    public function bulk(BulkProductActionRequest $request): RedirectResponse
    {
        $updatedCount = $this->productService->applyBulkAction($request->action(), $request->productIds());

        return back()->with('success', trans_choice(':count product updated.|:count products updated.', $updatedCount));
    }

    private function form(?Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product,
            'categoryOptions' => $this->categoryService->optionsForSelect(),
            'brandOptions' => $this->brandService->optionsForSelect(),
            'statusOptions' => collect(ProductStatus::cases())->mapWithKeys(fn (ProductStatus $status): array => [$status->value => $status->label()])->all(),
            'maxImages' => ProductService::MAX_IMAGES,
            'hasOptions' => $product?->hasOptions() ?? false,
        ]);
    }
}
