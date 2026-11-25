{{--
    Slide-in drawer. Opened by any trigger with data-drawer="{id}"; closed via
    data-drawer-close, overlay click or Escape (resources/js/storefront.js).
--}}
@props([
    'id',
    'title',
    'side' => 'right',
    'footer' => null,
])

<aside {{ $attributes->class(['drawer', 'drawer-'.$side]) }} id="{{ $id }}" aria-hidden="true" aria-label="{{ $title }}">
    <div class="drawer-head">
        <h3>{{ $title }}</h3>
        <button class="modal-close" data-drawer-close aria-label="Close {{ strtolower($title) }}">
            <x-icon name="close" class="icon-sm" />
        </button>
    </div>
    <div class="drawer-body">
        {{ $slot }}
    </div>
    @if ($footer)
        <div class="drawer-foot">
            {{ $footer }}
        </div>
    @endif
</aside>
