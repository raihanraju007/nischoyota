{{--
    Breadcrumb bar. items: [label => url|null]; the last item is rendered as the current page.
    The "›" separators come from CSS.
--}}
@props(['items' => []])

@php
    $items = ['Home' => route('home')] + $items;
@endphp

<div class="breadcrumb-bar">
    <div class="container">
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                @foreach ($items as $label => $url)
                    @if ($loop->last)
                        <li><span aria-current="page">{{ $label }}</span></li>
                    @else
                        <li><a href="{{ $url }}">{{ $label }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
</div>
