<x-layouts.app :title="__('Your Wishlist is Empty')" :breadcrumbs="['Wishlist' => null]" bottom-nav="wishlist">
    <x-empty-state icon="heart" heading="h1" :title="__('Your wishlist is empty')" :action-url="route('shop')" action-label="Start Shopping" action-class="btn-primary btn-lg">
        {{ __('Save products you love and find them here anytime.') }}
    </x-empty-state>
</x-layouts.app>
