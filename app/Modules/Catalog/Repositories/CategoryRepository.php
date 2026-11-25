<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class CategoryRepository
{
    /**
     * Top-level categories with their subcategories, both in display order.
     *
     * @return Collection<int, Category>
     */
    public function treeForAdmin(): Collection
    {
        return Category::query()
            ->topLevel()
            ->ordered()
            ->withCount('products')
            ->with(['children' => fn ($query) => $query->ordered()->withCount('products')])
            ->get();
    }

    /**
     * Active departments with their active subcategories, in display order (storefront menu).
     *
     * @return Collection<int, Category>
     */
    public function activeMenuTree(): Collection
    {
        return Category::query()
            ->topLevel()
            ->where('is_active', true)
            ->ordered()
            ->with(['children' => fn ($query) => $query->where('is_active', true)->ordered()])
            ->get(['id', 'parent_id', 'name', 'slug']);
    }

    /**
     * Categories that can be chosen as a parent: top level, except the one being edited.
     *
     * @return array<int, string> id => name
     */
    public function parentOptions(?Category $except = null): array
    {
        return Category::query()
            ->topLevel()
            ->ordered()
            ->when($except, fn ($query) => $query->whereKeyNot($except->getKey()))
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * The top-level category's name: the category itself, or its parent for a subcategory.
     */
    public function departmentNameOf(int $categoryId): string
    {
        $category = Category::query()->with('parent')->findOrFail($categoryId);

        return $category->parent->name ?? $category->name;
    }

    /**
     * id => "Electrical" / "— Fans", departments followed by their subcategories.
     *
     * @return array<int, string>
     */
    public function optionsForSelect(): array
    {
        $options = [];

        foreach ($this->treeForAdmin() as $department) {
            $options[$department->id] = $department->name;

            foreach ($department->children as $child) {
                $options[$child->id] = '— '.$child->name;
            }
        }

        return $options;
    }

    public function slugExists(string $slug): bool
    {
        return Category::query()->where('slug', $slug)->exists();
    }

    public function hasChildren(Category $category): bool
    {
        return $category->children()->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Category
    {
        return Category::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Category $category, array $attributes): Category
    {
        $category->update($attributes);

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
