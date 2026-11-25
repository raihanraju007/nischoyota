{{--
    Turns flashed session messages (success, error, info) into toasts.
    resources/js/storefront.js reads the data attributes and shows them with dmToast().
--}}
@php
    $toastTypes = ['success' => 'success', 'error' => 'danger', 'info' => 'info'];
@endphp

@foreach ($toastTypes as $sessionKey => $toastType)
    @if (session()->has($sessionKey))
        <template data-flash-toast="{{ session($sessionKey) }}" data-flash-toast-type="{{ $toastType }}"></template>
    @endif
@endforeach
