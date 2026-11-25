{{--
    Search page without (enough) text — the mobile Search tab (SearchController@index).
    $query        Text typed so far (shorter than 2 characters) or null
    $departments  [['slug', 'name', 'count']]
--}}
<x-layouts.app :title="__('Search')" :breadcrumbs="['Search' => null]" bottom-nav="search">
    <div class="container">
        <div class="page-title-bar">
            <h1>{{ __('Search') }}</h1>
        </div>

        <form class="search-form search-page-form" action="{{ route('search') }}" method="GET" role="search">
            <label class="visually-hidden" for="search-page-input">{{ __('Search products') }}</label>
            <input class="input" type="search" id="search-page-input" name="q" value="{{ $query }}" placeholder="{{ __('Search products, brands and categories...') }}" autofocus>
            <button class="search-btn" type="submit" aria-label="{{ __('Search') }}"><x-icon name="search" class="icon-sm" stroke-width="2" /></button>
        </form>
        @if ($query !== null)
            <p class="form-error">{{ __('Type at least 2 characters.') }}</p>
        @endif

        @if ($departments !== [])
            <h2 class="panel-title search-browse-title">{{ __('Or browse a category') }}</h2>
            <div class="subcategory-chips">
                @foreach ($departments as $department)
                    <a href="{{ route('category', $department['slug']) }}">{{ $department['name'] }}</a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
