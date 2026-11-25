{{--
    Review moderation (F-027). Data from Admin\ReviewController@index:
    $reviews       LengthAwarePaginator<Review> with product.coverImage, user
    $statusCounts  status value => count
    $activeStatus  ReviewStatus (tab)
    $statuses      ReviewStatus cases
--}}
@use('App\Modules\Review\Enums\ReviewStatus')

<x-layouts.admin :title="__('Reviews')" active="reviews" :subtitle="__('Approve reviews before they appear on product pages.')">
    <section class="panel panel-flush">
        <nav class="admin-tabs" aria-label="{{ __('Filter by status') }}">
            @foreach ($statuses as $status)
                <a href="{{ route('admin.reviews.index', $status === ReviewStatus::Pending ? [] : ['status' => $status->value]) }}"
                    @class(['active' => $activeStatus === $status]) @if ($activeStatus === $status) aria-current="page" @endif>
                    {{ $status->label() }} <span class="tab-count">{{ $statusCounts[$status->value] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($reviews->isEmpty())
            <x-empty-state icon="star" title="No {{ strtolower($activeStatus->label()) }} reviews" small>
                @if ($activeStatus === ReviewStatus::Pending)
                    All caught up. New reviews will appear here.
                @endif
            </x-empty-state>
        @else
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Review') }}</th>
                            <th>{{ __('Customer') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviews as $review)
                            <tr>
                                <td>
                                    <div class="table-product">
                                        <img src="{{ $review->product->coverImage?->url() ?? asset('images/products/placeholder.svg') }}" alt="" width="44" height="44" loading="lazy">
                                        <a class="link" href="{{ route('product', $review->product->slug) }}" target="_blank"><strong>{{ $review->product->name }}</strong></a>
                                    </div>
                                </td>
                                <td>
                                    <x-stars :rating="$review->rating" />
                                    <span class="visually-hidden">{{ __(':rating out of 5', ['rating' => \App\Support\Numerals::localize($review->rating)]) }}</span>
                                    <span class="table-sub table-wrap">{{ $review->comment }}</span>
                                </td>
                                <td>
                                    {{ $review->user->name }}
                                    <span class="table-sub">
                                        {{ \App\Support\Numerals::date($review->created_at->timezone(config('shop.timezone')), 'd M Y') }}@if ($review->is_verified_purchase) · <span class="text-success">{{ __('Verified') }}</span>@endif
                                    </span>
                                </td>
                                <td><x-admin.status :status="$review->status->value" /></td>
                                <td>
                                    <div class="table-actions">
                                        @if ($review->status !== ReviewStatus::Approved)
                                            <form method="POST" action="{{ route('admin.reviews.approve', $review) }}">
                                                @csrf
                                                <button class="icon-btn icon-btn-sm" type="submit" aria-label="Approve review by {{ $review->user->name }}">
                                                    <x-icon name="check" class="icon-sm" />
                                                </button>
                                            </form>
                                        @endif
                                        @if ($review->status !== ReviewStatus::Rejected)
                                            <form method="POST" action="{{ route('admin.reviews.reject', $review) }}">
                                                @csrf
                                                <button class="icon-btn icon-btn-sm danger" type="submit" aria-label="Reject review by {{ $review->user->name }}">
                                                    <x-icon name="close" class="icon-sm" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel-foot">
                <span>{{ __('Showing :first–:last of :total :products', ['first' => \App\Support\Numerals::localize($reviews->firstItem()), 'last' => \App\Support\Numerals::localize($reviews->lastItem()), 'total' => \App\Support\Numerals::localize($reviews->total()), 'products' => __('reviews')]) }}</span>
                {{ $reviews->links() }}
            </div>
        @endif
    </section>
</x-layouts.admin>
