{{--
    Product filter blocks, shared by the desktop sidebar and the mobile filter drawer (inside a GET form).

    Props:
        blocks   Which blocks to show: category, price, brand, rating, availability, discount, color, size
        prefix   Unique id prefix (the sidebar and drawer render on the same page)
        options  FilterOptions (categories with counts, brands, colours, sizes from the listed products)
        filter   ProductListingFilter (current selection)
    Blocks without options (e.g. no brands) are skipped.
--}}
@use('App\Support\Money')

@props([
    'blocks' => ['category', 'price', 'brand', 'rating', 'availability', 'discount', 'color', 'size'],
    'prefix' => 'f',
    'options',
    'filter',
])

@php
    $hasOptions = [
        'category' => $options->categories !== [],
        'brand' => $options->brands !== [],
        'color' => $options->colors !== [],
        'size' => $options->sizes !== [],
    ];
@endphp

@foreach ($blocks as $block)
    @continue(($hasOptions[$block] ?? true) === false)
    <div class="filter-block">
        @switch($block)
            @case('category')
                <h3>{{ __('Category') }}</h3>
                <ul class="filter-list">
                    @foreach ($options->categories as $category)
                        <li><label class="check"><input type="checkbox" name="category[]" value="{{ $category['slug'] }}" @checked(in_array($category['slug'], $filter->categorySlugs, true))> {{ $category['name'] }} <span class="filter-count">({{ $category['count'] }})</span></label></li>
                    @endforeach
                </ul>
                @break

            @case('price')
                <h3>{{ __('Price (৳)') }}</h3>
                <div class="price-inputs">
                    <label class="visually-hidden" for="{{ $prefix }}-price-min">{{ __('Minimum price (৳)') }}</label>
                    <input class="input" type="number" id="{{ $prefix }}-price-min" name="price_min" placeholder="{{ __('Min') }}" min="0" step="1"
                           value="{{ $filter->priceMin === null ? '' : Money::toTakaInput($filter->priceMin) }}">
                    <span>–</span>
                    <label class="visually-hidden" for="{{ $prefix }}-price-max">{{ __('Maximum price (৳)') }}</label>
                    <input class="input" type="number" id="{{ $prefix }}-price-max" name="price_max" placeholder="{{ __('Max') }}" min="0" step="1"
                           value="{{ $filter->priceMax === null ? '' : Money::toTakaInput($filter->priceMax) }}">
                </div>
                <div class="filter-apply"><button class="btn btn-sm btn-primary" type="submit">{{ __('Go') }}</button></div>
                @break

            @case('brand')
                <h3>{{ __('Brand') }}</h3>
                <ul class="filter-list">
                    @foreach ($options->brands as $brand)
                        <li><label class="check"><input type="checkbox" name="brand[]" value="{{ $brand['slug'] }}" @checked(in_array($brand['slug'], $filter->brandSlugs, true))> {{ $brand['name'] }}</label></li>
                    @endforeach
                </ul>
                @break

            @case('rating')
                <h3>{{ __('Rating') }}</h3>
                <ul class="filter-list">
                    @foreach ([4, 3, 2] as $stars)
                        <li><label class="check"><input type="radio" name="rating" value="{{ $stars }}" @checked($filter->minRating === $stars)> <x-stars :rating="$stars" /> {{ __(':stars★ & up', ['stars' => \App\Support\Numerals::localize($stars)]) }}</label></li>
                    @endforeach
                    @if ($filter->minRating !== null)
                        <li><label class="check"><input type="radio" name="rating" value=""> {{ __('Any rating') }}</label></li>
                    @endif
                </ul>
                @break

            @case('availability')
                <h3>{{ __('Availability') }}</h3>
                <ul class="filter-list">
                    <li><label class="check"><input type="checkbox" name="availability[]" value="in-stock" @checked($filter->onlyInStock)> {{ __('In Stock') }}</label></li>
                    <li><label class="check"><input type="checkbox" name="availability[]" value="pre-order" @checked($filter->onlyPreorder)> {{ __('Pre-order') }}</label></li>
                </ul>
                @break

            @case('discount')
                <h3>{{ __('Discount') }}</h3>
                <ul class="filter-list">
                    @foreach ([10, 20, 40] as $percent)
                        <li><label class="check"><input type="radio" name="discount" value="{{ $percent }}" @checked($filter->minDiscount === $percent)> {{ __(':percent% or more', ['percent' => \App\Support\Numerals::localize($percent)]) }}</label></li>
                    @endforeach
                    @if ($filter->minDiscount !== null)
                        <li><label class="check"><input type="radio" name="discount" value=""> {{ __('Any discount') }}</label></li>
                    @endif
                </ul>
                @break

            @case('color')
                <h3>{{ __('Color') }}</h3>
                <div class="color-swatches">
                    @foreach ($options->colors as $color)
                        <label class="swatch-check" title="{{ __($color['value']) }}">
                            <input type="checkbox" name="color[]" value="{{ $color['id'] }}" @checked(in_array($color['id'], $filter->colorValueIds, true))>
                            <span class="swatch" style="background: {{ $color['hex'] ?? '#e5e7eb' }}" aria-hidden="true"></span>
                            <span class="visually-hidden">{{ __('Filter by color: :color', ['color' => $color['value']]) }}</span>
                        </label>
                    @endforeach
                </div>
                @break

            @case('size')
                <h3>{{ __('Size') }}</h3>
                <div class="size-options">
                    @foreach ($options->sizes as $size)
                        <label class="size-check">
                            <input type="checkbox" name="size[]" value="{{ $size['id'] }}" @checked(in_array($size['id'], $filter->sizeValueIds, true))>
                            <span class="size-btn">{{ __($size['value']) }}</span>
                        </label>
                    @endforeach
                </div>
                @break
        @endswitch
    </div>
@endforeach
