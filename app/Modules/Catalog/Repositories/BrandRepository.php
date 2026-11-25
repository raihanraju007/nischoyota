<?php

namespace App\Modules\Catalog\Repositories;

use App\Modules\Catalog\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BrandRepository
{
    private const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginateForAdmin(?string $search): LengthAwarePaginator
    {
        return Brand::query()
            ->when($search, fn ($query) => $query->whereLike('name', '%'.$this->escapeLike($search).'%'))
            ->withCount('products')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @return array<int, string> id => name
     */
    public function optionsForSelect(): array
    {
        return Brand::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function slugExists(string $slug): bool
    {
        return Brand::query()->where('slug', $slug)->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Brand
    {
        return Brand::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Brand $brand, array $attributes): Brand
    {
        $brand->update($attributes);

        return $brand;
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
