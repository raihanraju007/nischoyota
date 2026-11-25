{{--
    "Sort by" as its own small GET form that keeps the current filters (hidden inputs).
    Submits on change with JS (data-auto-submit); the "Sort" button covers no-JS.
    Props: id, mobile, options (ProductSort cases), current (ProductSort), defaultSort (value left out of the link)
--}}
@props([
    'id' => 'sort-products',
    'mobile' => false,
    'options' => [],
    'current' => null,
    'defaultSort' => 'popular',
])

@php
    $keep = collect(request()->query())->except(['sort', 'page']);
@endphp

<form method="GET" action="{{ url()->current() }}" @class(['sort-select' => ! $mobile]) data-auto-submit data-default-sort="{{ $defaultSort }}">
    @foreach ($keep as $key => $value)
        @foreach ((array) $value as $item)
            <input type="hidden" name="{{ is_array($value) ? $key.'[]' : $key }}" value="{{ $item }}">
        @endforeach
    @endforeach
    <label @class(['visually-hidden' => $mobile]) for="{{ $id }}">Sort by:</label>
    <select class="select" id="{{ $id }}" name="sort">
        @foreach ($options as $option)
            <option value="{{ $option->value }}" @selected($current === $option)>{{ $mobile && $loop->first ? 'Sort: '.$option->label() : $option->label() }}</option>
        @endforeach
    </select>
    <noscript><button class="btn btn-sm btn-outline" type="submit">Sort</button></noscript>
</form>
