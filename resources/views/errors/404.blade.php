<x-error-page code="404" :title="__('Page Not Found')">
    {{ __('The page you\'re looking for doesn\'t exist or may have been moved.') }}

    <x-slot:actions>
        <a class="btn btn-primary btn-lg" href="{{ route('home') }}">{{ __('Go Home') }}</a>
        <a class="btn btn-outline btn-lg" href="{{ route('shop') }}">{{ __('Browse Products') }}</a>
    </x-slot:actions>
</x-error-page>
