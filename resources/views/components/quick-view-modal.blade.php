{{-- Product quick view (static sample until product data is wired up). Pushed once by <x-product-card>. --}}
<x-modal id="modal-quick-view" :title="__('Quick View')" size="lg">
    <div class="quickview-grid">
        <img src="{{ asset('images/products/honey-bottle-1.jpg') }}" alt="{{ __('Pure honey') }}" width="600" height="600">
        <div>
            <p class="product-cat">{{ __('Honey') }}</p>
            <h3 class="quickview-title">{{ __('Ceiling Fan 56"') }}</h3>
            <x-rating :rating="4.7" :reviews="215" reviews-suffix=" reviews" />
            <div class="detail-price-row">
                <span class="detail-price quickview-price">৳ 4,850</span>
                <span class="detail-price-old">৳ 5,500</span>
                <span class="badge badge-sale">-12%</span>
            </div>
            <p class="detail-short-desc">{{ __('High-speed 56" ceiling fan with copper motor, 5-year service warranty and energy-efficient performance for Bangladeshi summers.') }}</p>
            <p class="stock-note stock-in">{{ __('✔ In Stock') }}</p>
            <div class="buy-row">
                <x-qty-control />
                <button class="btn btn-primary" data-add-to-cart>{{ __('Add to Cart') }}</button>
            </div>
            <a href="{{ route('shop') }}" class="section-link">{{ __('View Full Details →') }}</a>
        </div>
    </div>
</x-modal>
