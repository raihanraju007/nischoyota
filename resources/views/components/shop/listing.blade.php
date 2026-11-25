{{--
    Product listing: filter sidebar (GET form) + mobile filter drawer + toolbar + product grid + pagination.

    Props:
        products     LengthAwarePaginator<ProductCardData>
        options      FilterOptions
        filter       ProductListingFilter
        sortOptions  ProductSort cases
        chips        [['label', 'url']] active filters (removable)
        clearUrl     URL without any filter
        filters      Filter blocks to show (see <x-shop.filters>)
        hiddenFields Extra fields the filter forms keep, e.g. ['q' => 'fan'] on search
        defaultSort  The page's default sort value (left out of links)
--}}
@props([
    'products',
    'options',
    'filter',
    'sortOptions',
    'chips' => [],
    'clearUrl',
    'filters' => ['category', 'price', 'brand', 'rating', 'availability', 'discount', 'color', 'size'],
    'hiddenFields' => [],
    'defaultSort' => 'popular',
])

<div class="shop-layout">
    <aside class="filters-sidebar" aria-label="{{ __('Product filters') }}">
        <form method="GET" action="{{ url()->current() }}" data-auto-submit data-default-sort="{{ $defaultSort }}">
            <input type="hidden" name="sort" value="{{ $filter->sort->value }}">
            @foreach ($hiddenFields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <x-shop.filters :blocks="$filters" prefix="f" :options="$options" :filter="$filter" />
            <button class="btn btn-primary btn-block filter-submit" type="submit">{{ __('Apply Filters') }}</button>
        </form>
    </aside>

    <div>
        <div class="mobile-filter-btns">
            <button class="btn btn-outline" type="button" data-drawer="filter-drawer">
                <x-icon name="filter" class="icon-sm" />
                {{ __('Filter') }}
            </button>
            <x-shop.sort-select id="mobile-sort" mobile :options="$sortOptions" :current="$filter->sort" :default-sort="$defaultSort" />
        </div>

        <div class="shop-toolbar">
            <span class="showing">
                @if ($products->total() > 0)
                    {{ __('Showing :first–:last of :total :products', ['first' => \App\Support\Numerals::localize($products->firstItem()), 'last' => \App\Support\Numerals::localize($products->lastItem()), 'total' => \App\Support\Numerals::localize(number_format($products->total())), 'products' => trans_choice(__('product|products'), $products->total(), [], app()->getLocale())]) }}
                @else
                    {{ __('No products') }}
                @endif
            </span>
            <div class="toolbar-controls">
                <x-shop.sort-select :options="$sortOptions" :current="$filter->sort" :default-sort="$defaultSort" />
            </div>
        </div>

        @if ($chips !== [])
            <div class="active-filters">
                @foreach ($chips as $chip)
                    <a class="filter-chip" href="{{ $chip['url'] }}">{{ $chip['label'] }} <span aria-hidden="true">✕</span><span class="visually-hidden">{{ __('Remove filter') }}</span></a>
                @endforeach
                <a class="filter-clear" href="{{ $clearUrl }}">{{ __('Clear all') }}</a>
            </div>
        @endif

        @if ($products->isEmpty())
            <x-empty-state icon="search-off" :title="__('No products match these filters')" :action-url="$clearUrl" action-label="Clear all filters" small>
                {{ __('Try removing a filter or choosing a different price range.') }}
            </x-empty-state>
        @else
            <div class="grid-products">
                @foreach ($products as $card)
                    <x-product-card :card="$card" />
                @endforeach
            </div>

            {{ $products->links() }}
        @endif
    </div>
</div>

@push('overlays')
    <x-drawer id="filter-drawer" side="left" :title="__('Filters')">
        <form method="GET" action="{{ url()->current() }}" id="mobile-filters">
            <input type="hidden" name="sort" value="{{ $filter->sort->value }}">
            @foreach ($hiddenFields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <x-shop.filters :blocks="$filters" prefix="m" :options="$options" :filter="$filter" />
        </form>

        <x-slot:footer>
            <button class="btn btn-primary btn-block" type="submit" form="mobile-filters">{{ __('Apply Filters') }}</button>
        </x-slot:footer>
    </x-drawer>
@endpush
