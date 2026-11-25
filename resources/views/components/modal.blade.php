{{--
    Modal shell. Opened by any trigger with data-modal="{id}"; closed via data-modal-close,
    backdrop click or Escape (resources/js/storefront.js).

    Props:
        title     Renders a header with a close button (omit for headerless confirm dialogs).
        size      lg → wide modal (820px).
        centered  Center the body text.
    Slots: default (body), footer.
--}}
@props([
    'id',
    'title' => null,
    'size' => null,
    'centered' => false,
    'footer' => null,
])

<div class="modal-root" id="{{ $id }}" aria-hidden="true" role="dialog" aria-labelledby="{{ $id }}-title">
    <div class="modal-backdrop"></div>
    <div @class(['modal', 'modal-'.$size => $size])>
        @if ($title)
            <div class="modal-head">
                <h2 id="{{ $id }}-title">{{ $title }}</h2>
                <button class="modal-close" data-modal-close aria-label="Close">
                    <x-icon name="close" class="icon-sm" />
                </button>
            </div>
        @endif
        <div @class(['modal-body', 'modal-center' => $centered])>
            {{ $slot }}
        </div>
        @if ($footer)
            <div class="modal-foot">
                {{ $footer }}
            </div>
        @endif
    </div>
</div>
