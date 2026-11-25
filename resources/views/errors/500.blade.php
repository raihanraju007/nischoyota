<x-error-page code="500" :title="__('Something Went Wrong')">
    {{ __('We\'re having a technical problem on our side. Please try again in a few minutes.') }}

    <x-slot:actions>
        <a class="btn btn-primary btn-lg" href="{{ route('home') }}">{{ __('Go Home') }}</a>
        <a class="btn btn-outline btn-lg" href="{{ route('contact') }}">{{ __('Contact Support') }}</a>
    </x-slot:actions>
</x-error-page>
