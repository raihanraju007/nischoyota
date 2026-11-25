<x-layouts.app title="Your Wishlist is Empty" :breadcrumbs="['Wishlist' => null]" bottom-nav="wishlist">
    <x-empty-state icon="heart" heading="h1" title="Your wishlist is empty" :action-url="route('shop')" action-label="Start Shopping" action-class="btn-primary btn-lg">
        Save products you love and find them here anytime.
    </x-empty-state>
</x-layouts.app>
