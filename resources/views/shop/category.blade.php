{{--
    One category (ShopController@category). A department includes its subcategories.
    $category  Category with parent.children and children (active only, ordered)
--}}
@php
    $department = $category->parent ?? $category;
    $subcategories = $department->children;
    $breadcrumbs = $category->parent
        ? [$department->name => route('category', $department->slug), $category->name => null]
        : [$category->name => null];
@endphp

<x-layouts.app :title="$category->meta_title ?? $category->name" :description="$category->meta_description" :breadcrumbs="$breadcrumbs" :active-nav="$department->slug">
    <section class="category-banner" aria-label="{{ $category->name }} category">
        <div class="container category-banner-inner">
            @if ($category->image)
                <img src="{{ $category->imageUrl() }}" alt="" width="110" height="110">
            @endif
            <div>
                <h1>{{ $category->name }}</h1>
                <p>{{ $category->meta_description ?? 'Quality '.$category->name.' products, delivered across Bangladesh with Cash on Delivery.' }}</p>
            </div>
        </div>
    </section>

    <div class="container">
        @if ($subcategories->isNotEmpty())
            <div class="subcategory-chips">
                <a href="{{ route('category', $department->slug) }}" @class(['active' => $category->is($department)])>All {{ $department->name }}</a>
                @foreach ($subcategories as $subcategory)
                    <a href="{{ route('category', $subcategory->slug) }}" @class(['active' => $category->is($subcategory)])>{{ $subcategory->name }}</a>
                @endforeach
            </div>
        @endif

        <x-shop.listing :products="$products" :options="$filterOptions" :filter="$filter" :sort-options="$sortOptions"
                        :chips="$chips" :clear-url="route('category', $category->slug)"
                        :filters="['price', 'brand', 'rating', 'availability', 'discount', 'color', 'size']" />
    </div>
</x-layouts.app>
