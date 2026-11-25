{{-- All products (ShopController@index). --}}
<x-layouts.app :title="__('All Products')" :breadcrumbs="['Shop' => route('shop'), 'All Products' => null]" active-nav="shop">
    <div class="container">
        <div class="page-title-bar">
            <h1>{{ __('All Products') }}</h1>
            <p class="result-count">{{ \App\Support\Numerals::localize(number_format($products->total())) }} {{ trans_choice(__('product|products'), $products->total()) }}</p>
        </div>

        <x-shop.listing :products="$products" :options="$filterOptions" :filter="$filter" :sort-options="$sortOptions"
                        :chips="$chips" :clear-url="route('shop')" />
    </div>
</x-layouts.app>
