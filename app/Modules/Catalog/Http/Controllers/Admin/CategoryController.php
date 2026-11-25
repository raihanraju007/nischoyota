<?php

namespace App\Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Exceptions\CategoryHasChildrenException;
use App\Modules\Catalog\Exceptions\CategoryHasProductsException;
use App\Modules\Catalog\Exceptions\InvalidCategoryParentException;
use App\Modules\Catalog\Http\Requests\Admin\SaveCategoryRequest;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => $this->categoryService->treeForAdmin(),
            'parentOptions' => $this->categoryService->parentOptions(),
            'editingCategory' => null,
        ]);
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.index', [
            'categories' => $this->categoryService->treeForAdmin(),
            'parentOptions' => $this->categoryService->parentOptions(except: $category),
            'editingCategory' => $category,
        ]);
    }

    public function store(SaveCategoryRequest $request): RedirectResponse
    {
        $category = $this->categoryService->create($request->toData());

        return to_route('admin.categories.index')->with('success', __('Category ":name" created.', ['name' => $category->name]));
    }

    public function update(SaveCategoryRequest $request, Category $category): RedirectResponse
    {
        try {
            $this->categoryService->update($category, $request->toData());
        } catch (InvalidCategoryParentException $exception) {
            return back()->withInput()->withErrors(['parent_id' => $exception->getMessage()]);
        }

        return to_route('admin.categories.index')->with('success', __('Category ":name" updated.', ['name' => $category->name]));
    }

    public function destroy(Category $category): RedirectResponse
    {
        try {
            $this->categoryService->delete($category);
        } catch (CategoryHasChildrenException|CategoryHasProductsException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('admin.categories.index')->with('success', __('Category ":name" deleted.', ['name' => $category->name]));
    }
}
