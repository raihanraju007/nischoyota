<x-error-page code="404" title="Page Not Found">
    The page you're looking for doesn't exist or may have been moved.

    <x-slot:actions>
        <a class="btn btn-primary btn-lg" href="{{ route('home') }}">Go Home</a>
        <a class="btn btn-outline btn-lg" href="{{ route('shop') }}">Browse Products</a>
    </x-slot:actions>
</x-error-page>
