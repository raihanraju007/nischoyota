{{-- Language toggle: বাংলা | EN. The active language is highlighted; the other posts to locale.update (choice kept in a cookie). --}}
@php
    $currentLocale = \App\Support\Locale::current();
@endphp

<div class="language-switcher" role="group" aria-label="{{ __('Switch language') }}">
    @foreach (\App\Support\Locale::cases() as $locale)
        @if ($locale === $currentLocale)
            <span class="language-switcher-option is-active" lang="{{ $locale->value }}" aria-current="true">{{ $locale->shortLabel() }}</span>
        @else
            <form method="POST" action="{{ route('locale.update', $locale->value) }}">
                @csrf
                <button type="submit" class="language-switcher-option" lang="{{ $locale->value }}" title="{{ $locale->nativeName() }}">{{ $locale->shortLabel() }}</button>
            </form>
        @endif
    @endforeach
</div>
