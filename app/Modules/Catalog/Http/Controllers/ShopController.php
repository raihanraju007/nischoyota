<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Data\ListingScope;
use App\Modules\Catalog\Enums\ProductSort;
use App\Modules\Catalog\Http\Requests\ListProductsForShopRequest;
use App\Modules\Catalog\Services\ProductListingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Storefront Shop, Category and Deals pages (F-016).
 */
final class ShopController extends Controller
{
    public function __construct(private readonly ProductListingService $listingService) {}

    public function index(ListProductsForShopRequest $request): View
    {
        $filter = $request->toFilter();
        $scope = ListingScope::wholeShop();
        $options = $this->listingService->filterOptions($scope);

        return view('shop.index', [
            'products' => $this->listingService->cards($scope, $filter),
            'filterOptions' => $options,
            'filter' => $filter,
            'chips' => $this->listingService->activeFilterChips($filter, $options, $request->query(), route('shop')),
            'sortOptions' => ProductSort::optionsFor(isSearch: false),
        ]);
    }

    public function category(ListProductsForShopRequest $request, ?string $slug = null): View|RedirectResponse
    {
        if ($slug === null) {
            return to_route('shop');
        }

        $category = $this->listingService->findListedCategory($slug) ?? abort(404);
        $scope = ListingScope::categories($this->listingService->categoryWithChildrenIds($category));
        $filter = $request->toFilter();
        $options = $this->listingService->filterOptions($scope);

        return view('shop.category', [
            'category' => $category,
            'products' => $this->listingService->cards($scope, $filter),
            'filterOptions' => $options,
            'filter' => $filter,
            'chips' => $this->listingService->activeFilterChips($filter, $options, $request->query(), route('category', $category->slug)),
            'sortOptions' => ProductSort::optionsFor(isSearch: false),
        ]);
    }

    public function deals(ListProductsForShopRequest $request): View
    {
        $filter = $request->toFilter();
        $scope = ListingScope::deals();
        $options = $this->listingService->filterOptions($scope);

        return view('shop.deals', [
            'products' => $this->listingService->cards($scope, $filter),
            'filterOptions' => $options,
            'filter' => $filter,
            'highestDiscount' => $this->listingService->highestDiscount(),
            'hoursLeft' => $this->listingService->dealHoursLeft(),
            'sortOptions' => ProductSort::optionsFor(isSearch: false),
        ]);
    }
}
