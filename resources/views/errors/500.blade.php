<x-error-page code="500" title="Something Went Wrong">
    We're having a technical problem on our side. Please try again in a few minutes.

    <x-slot:actions>
        <a class="btn btn-primary btn-lg" href="{{ route('home') }}">Go Home</a>
        <a class="btn btn-outline btn-lg" href="{{ route('contact') }}">Contact Support</a>
    </x-slot:actions>
</x-error-page>
