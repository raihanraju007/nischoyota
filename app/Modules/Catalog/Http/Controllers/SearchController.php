<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Catalog\Data\ListingScope;
use App\Modules\Catalog\Enums\ProductSort;
use App\Modules\Catalog\Http\Requests\ListProductsForShopRequest;
use App\Modules\Catalog\Services\ProductListingService;
use Illuminate\View\View;

/**
 * Storefront search (F-018).
 */
final class SearchController extends Controller
{
    private const MIN_QUERY_LENGTH = 2;

    public function __construct(private readonly ProductListingService $listingService) {}

    public function index(ListProductsForShopRequest $request): View
    {
        $query = $request->searchQuery();

        if ($query === null || mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return view('shop.search-prompt', [
                'query' => $query,
                'departments' => $this->listingService->filterOptions(ListingScope::wholeShop())->categories,
            ]);
        }

        $scope = $this->listingService->searchScope($query);
        $filter = $request->toFilter(defaultSort: ProductSort::BestMatch);
        $products = $this->listingService->cards($scope, $filter);

        if ($products->total() === 0 && ! $filter->hasActiveFilters()) {
            return view('shop.search-empty', [
                'query' => $query,
                'suggestions' => $this->listingService->bestSellerCards(),
            ]);
        }

        $options = $this->listingService->filterOptions($scope);

        return view('shop.search', [
            'query' => $query,
            'products' => $products,
            'filterOptions' => $options,
            'filter' => $filter,
            'chips' => $this->listingService->activeFilterChips($filter, $options, $request->query(), route('search')),
            'sortOptions' => ProductSort::optionsFor(isSearch: true),
        ]);
    }
}
