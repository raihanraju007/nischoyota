<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\CategoryData;
use App\Modules\Catalog\Exceptions\CategoryHasChildrenException;
use App\Modules\Catalog\Exceptions\CategoryHasProductsException;
use App\Modules\Catalog\Exceptions\InvalidCategoryParentException;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Support\PublicImageStorage;
use App\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final class CategoryService
{
    private const IMAGE_DIRECTORY = 'categories';

    private const FALLBACK_SLUG = 'category';

    private const MENU_CACHE_KEY = 'storefront.category_menu';

    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly ProductRepository $productRepository,
        private readonly PublicImageStorage $imageStorage,
    ) {}

    /**
     * @return Collection<int, Category>
     */
    public function treeForAdmin(): Collection
    {
        return $this->categoryRepository->treeForAdmin();
    }

    /**
     * @return array<int, string>
     */
    public function parentOptions(?Category $except = null): array
    {
        return $this->categoryRepository->parentOptions($except);
    }

    /**
     * Storefront menu, cached until a category changes.
     *
     * @return list<array{name: string, slug: string, children: list<array{name: string, slug: string}>}>
     */
    public function menuTree(): array
    {
        return Cache::rememberForever(self::MENU_CACHE_KEY, fn (): array => $this->categoryRepository->activeMenuTree()
            ->map(fn (Category $department): array => [
                'name' => $department->name,
                'slug' => $department->slug,
                'children' => $department->children
                    ->map(fn (Category $child): array => ['name' => $child->name, 'slug' => $child->slug])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all());
    }

    /**
     * @return array<int, string>
     */
    public function optionsForSelect(): array
    {
        return $this->categoryRepository->optionsForSelect();
    }

    public function create(CategoryData $data): Category
    {
        $category = $this->categoryRepository->create([
            ...$this->attributesFrom($data),
            'slug' => $data->slug ?? $this->uniqueSlugFor($data->name),
            'image' => $data->image === null ? null : $this->imageStorage->store($data->image, self::IMAGE_DIRECTORY),
        ]);
        Cache::forget(self::MENU_CACHE_KEY);

        return $category;
    }

    /**
     * @throws InvalidCategoryParentException
     */
    public function update(Category $category, CategoryData $data): Category
    {
        $this->ensureParentIsAllowed($category, $data->parentId);

        $oldImage = $category->image;
        $newImage = $this->imageStorage->resolveEditedImage($oldImage, $data->image, $data->shouldRemoveImage, self::IMAGE_DIRECTORY);

        $category = $this->categoryRepository->update($category, [
            ...$this->attributesFrom($data),
            'slug' => $data->slug ?? $this->slugForUpdate($category, $data->name),
            'image' => $newImage,
        ]);

        $this->imageStorage->deleteReplaced($oldImage, $newImage);
        Cache::forget(self::MENU_CACHE_KEY);

        return $category;
    }

    /**
     * @throws CategoryHasChildrenException
     * @throws CategoryHasProductsException
     */
    public function delete(Category $category): void
    {
        if ($this->categoryRepository->hasChildren($category)) {
            throw new CategoryHasChildrenException;
        }

        if ($this->productRepository->categoryHasProducts($category)) {
            throw new CategoryHasProductsException;
        }

        $this->categoryRepository->delete($category);
        $this->imageStorage->delete($category->image);
        Cache::forget(self::MENU_CACHE_KEY);
    }

    public function uniqueSlugFor(string $name): string
    {
        return UniqueSlug::generate($name, $this->categoryRepository->slugExists(...), self::FALLBACK_SLUG);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(CategoryData $data): array
    {
        return [
            'parent_id' => $data->parentId,
            'name' => $data->name,
            'sort_order' => $data->sortOrder,
            'is_active' => $data->isActive,
            'meta_title' => $data->metaTitle,
            'meta_description' => $data->metaDescription,
        ];
    }

    private function ensureParentIsAllowed(Category $category, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $category->id) {
            throw InvalidCategoryParentException::cannotBeItsOwnParent();
        }

        if ($this->categoryRepository->hasChildren($category)) {
            throw InvalidCategoryParentException::categoryHasSubcategories();
        }
    }

    /**
     * Keep the current slug when the slug field is left empty and the name is unchanged.
     */
    private function slugForUpdate(Category $category, string $name): string
    {
        return $category->name === $name ? $category->slug : $this->uniqueSlugFor($name);
    }
}
