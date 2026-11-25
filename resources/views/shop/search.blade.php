{{--
    Search results (SearchController@index).
    $query  What the customer typed; other data as on the shop page.
--}}
<x-layouts.app :title="'Search results for “'.$query.'”'" :breadcrumbs="['Search' => null]" bottom-nav="search">
    <div class="container">
        <div class="page-title-bar">
            <h1>{{ __('Search results for “:query”', ['query' => $query]) }}</h1>
            <p class="result-count">{{ trans_choice(':count product found|:count products found', $products->total()) }}</p>
        </div>

        <x-shop.listing :products="$products" :options="$filterOptions" :filter="$filter" :sort-options="$sortOptions"
                        :chips="$chips" :clear-url="route('search', ['q' => $query])" :hidden-fields="['q' => $query]" default-sort="relevance" />
    </div>
</x-layouts.app>
