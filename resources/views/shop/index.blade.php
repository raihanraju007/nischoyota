{{-- All products (ShopController@index). --}}
<x-layouts.app title="All Products" :breadcrumbs="['Shop' => route('shop'), 'All Products' => null]" active-nav="shop">
    <div class="container">
        <div class="page-title-bar">
            <h1>All Products</h1>
            <p class="result-count">{{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }}</p>
        </div>

        <x-shop.listing :products="$products" :options="$filterOptions" :filter="$filter" :sort-options="$sortOptions"
                        :chips="$chips" :clear-url="route('shop')" />
    </div>
</x-layouts.app>
