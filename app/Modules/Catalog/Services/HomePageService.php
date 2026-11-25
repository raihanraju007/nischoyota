<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Data\HomePageData;
use App\Modules\Catalog\Data\ProductCardData;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Repositories\ProductListingRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Home page sections (F-019), cached for a few minutes.
 */
final class HomePageService
{
    public const CACHE_KEY = 'storefront.home_sections.v3';

    private const CACHE_MINUTES = 10;

    private const DEPARTMENT_TILES = 7;

    private const FEATURED_LIMIT = 8;

    private const SECTION_LIMIT = 4;

    /**
     * Departments the static promo banners link to.
     */
    private const PROMO_DEPARTMENT_SLUGS = ['honey', 'clothing'];

    public function __construct(
        private readonly ProductListingRepository $listingRepository,
        private readonly ProductListingService $listingService,
    ) {}

    public function sections(): HomePageData
    {
        $sections = Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), fn (): array => $this->buildSections()->toArray());

        return HomePageData::fromArray($sections);
    }

    private function buildSections(): HomePageData
    {
        $departments = $this->listingRepository->departmentTiles(self::DEPARTMENT_TILES);

        return new HomePageData(
            departments: $departments,
            highestDiscount: $this->listingRepository->highestDiscount(),
            featured: $this->cards($this->listingRepository->featuredProducts(self::FEATURED_LIMIT)),
            deals: $this->cards($this->listingRepository->biggestDeals(self::SECTION_LIMIT)),
            newArrivals: $this->cards($this->listingRepository->newestProducts(self::SECTION_LIMIT)),
            bestSellers: $this->cards($this->listingRepository->bestSellers(self::SECTION_LIMIT)),
            promoLinks: $this->promoLinks(array_column($departments, 'slug')),
        );
    }

    /**
     * @param  list<string>  $listedDepartmentSlugs
     * @return array<string, string>
     */
    private function promoLinks(array $listedDepartmentSlugs): array
    {
        $links = [];

        foreach (self::PROMO_DEPARTMENT_SLUGS as $slug) {
            $links[$slug] = in_array($slug, $listedDepartmentSlugs, true) ? route('category', $slug) : route('shop');
        }

        return $links;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<ProductCardData>
     */
    private function cards(Collection $products): array
    {
        return $products->map($this->listingService->toCard(...))->values()->all();
    }
}
