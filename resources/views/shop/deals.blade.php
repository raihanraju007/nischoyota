{{--
    Discounted products (ShopController@deals).
    $highestDiscount  Highest % off among deals
    $hoursLeft        Hours until midnight (Bangladesh time), for the countdown
--}}
<x-layouts.app :title="'Today\'s Deals'.($highestDiscount ? ' — Up to '.$highestDiscount.'% OFF' : '')" :breadcrumbs="['Deals' => null]" active-nav="deals">
    <section class="section deals-section" aria-labelledby="deals-title">
        <div class="container">
            <div class="deals-head">
                <h1 id="deals-title">{{ __('Today\'s Deals') }}</h1>
                @if ($highestDiscount)
                    <span class="deals-tag">{{ __('Up to :percent% OFF', ['percent' => \App\Support\Numerals::localize($highestDiscount)]) }}</span>
                @endif
                <x-countdown :hours="$hoursLeft" />
            </div>

            @if ($filterOptions->categories !== [])
                <div class="subcategory-chips">
                    <a href="{{ route('deals') }}" @class(['active' => $filter->categorySlugs === []])>{{ __('All') }}</a>
                    @foreach ($filterOptions->categories as $department)
                        <a href="{{ route('deals', ['category' => [$department['slug']]]) }}" @class(['active' => $filter->categorySlugs === [$department['slug']]])>{{ $department['name'] }}</a>
                    @endforeach
                </div>
            @endif

            <div class="shop-toolbar">
                <span class="showing">{{ trans_choice(':count deal available|:count deals available', $products->total()) }}</span>
                <div class="toolbar-controls">
                    <x-shop.sort-select id="sort-deals" :options="$sortOptions" :current="$filter->sort" default-sort="popular" />
                </div>
            </div>

            @if ($products->isEmpty())
                <x-empty-state icon="tag" :title="__('No deals right now')" :action-url="route('shop')" action-label="Browse all products" small>
                    {{ __('Check back soon for new offers.') }}
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
    </section>
</x-layouts.app>
