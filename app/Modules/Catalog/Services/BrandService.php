<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\BrandData;
use App\Modules\Catalog\Exceptions\BrandHasProductsException;
use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Repositories\BrandRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Support\PublicImageStorage;
use App\Support\UniqueSlug;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BrandService
{
    private const LOGO_DIRECTORY = 'brands';

    private const FALLBACK_SLUG = 'brand';

    public function __construct(
        private readonly BrandRepository $brandRepository,
        private readonly ProductRepository $productRepository,
        private readonly PublicImageStorage $imageStorage,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Brand>
     */
    public function paginateForAdmin(?string $search): LengthAwarePaginator
    {
        return $this->brandRepository->paginateForAdmin($search);
    }

    /**
     * @return array<int, string>
     */
    public function optionsForSelect(): array
    {
        return $this->brandRepository->optionsForSelect();
    }

    public function create(BrandData $data): Brand
    {
        return $this->brandRepository->create([
            'name' => $data->name,
            'name_bn' => $data->nameBn,
            'slug' => $data->slug ?? $this->uniqueSlugFor($data->name),
            'logo' => $data->logo === null ? null : $this->imageStorage->store($data->logo, self::LOGO_DIRECTORY),
            'is_active' => $data->isActive,
        ]);
    }

    public function update(Brand $brand, BrandData $data): Brand
    {
        $oldLogo = $brand->logo;
        $newLogo = $this->imageStorage->resolveEditedImage($oldLogo, $data->logo, $data->shouldRemoveLogo, self::LOGO_DIRECTORY);

        $brand = $this->brandRepository->update($brand, [
            'name' => $data->name,
            'name_bn' => $data->nameBn,
            'slug' => $data->slug ?? ($brand->name === $data->name ? $brand->slug : $this->uniqueSlugFor($data->name)),
            'logo' => $newLogo,
            'is_active' => $data->isActive,
        ]);

        $this->imageStorage->deleteReplaced($oldLogo, $newLogo);

        return $brand;
    }

    /**
     * @throws BrandHasProductsException
     */
    public function delete(Brand $brand): void
    {
        if ($this->productRepository->brandHasProducts($brand->id)) {
            throw new BrandHasProductsException;
        }

        $this->brandRepository->delete($brand);
        $this->imageStorage->delete($brand->logo);
    }

    private function uniqueSlugFor(string $name): string
    {
        return UniqueSlug::generate($name, $this->brandRepository->slugExists(...), self::FALLBACK_SLUG);
    }
}
