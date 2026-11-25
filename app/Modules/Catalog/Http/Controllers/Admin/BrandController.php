<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Exceptions\BrandHasProductsException;
use App\Modules\Catalog\Http\Requests\Admin\ListBrandsRequest;
use App\Modules\Catalog\Http\Requests\Admin\SaveBrandRequest;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Services\BrandService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class BrandController extends Controller
{
    public function __construct(private readonly BrandService $brandService) {}

    public function index(ListBrandsRequest $request): View
    {
        return view('admin.brands.index', [
            'brands' => $this->brandService->paginateForAdmin($request->search()),
            'search' => $request->search(),
            'editingBrand' => null,
        ]);
    }

    public function edit(ListBrandsRequest $request, Brand $brand): View
    {
        return view('admin.brands.index', [
            'brands' => $this->brandService->paginateForAdmin($request->search()),
            'search' => $request->search(),
            'editingBrand' => $brand,
        ]);
    }

    public function store(SaveBrandRequest $request): RedirectResponse
    {
        $brand = $this->brandService->create($request->toData());

        return to_route('admin.brands.index')->with('success', __('Brand ":name" created.', ['name' => $brand->name]));
    }

    public function update(SaveBrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brandService->update($brand, $request->toData());

        return to_route('admin.brands.index')->with('success', __('Brand ":name" updated.', ['name' => $brand->name]));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        try {
            $this->brandService->delete($brand);
        } catch (BrandHasProductsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.brands.index')->with('success', __('Brand ":name" deleted.', ['name' => $brand->name]));
    }
}
