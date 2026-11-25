<?php

use App\Modules\Account\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Review\Enums\ReviewStatus;
use App\Modules\Review\Models\Review;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->product = sellableVariant([], ['name' => 'Ceiling Fan 56 inch'])->product;
    $this->customer = User::factory()->customer()->create(['name' => 'Rahim Uddin']);
});

function submitReview(Product $product, array $input = []): TestResponse
{
    return test()->post(route('products.reviews.store', $product->slug), ['rating' => 5, 'comment' => 'Quiet and strong airflow, great value.', ...$input]);
}

describe('writing', function () {
    it('saves a pending review and thanks the customer', function () {
        $this->actingAs($this->customer);

        submitReview($this->product)
            ->assertRedirect(route('product', $this->product->slug).'#tab-reviews')
            ->assertSessionHas('success');

        $review = Review::query()->sole();
        expect($review->status)->toBe(ReviewStatus::Pending)
            ->and($review->rating)->toBe(5)
            ->and($review->is_verified_purchase)->toBeFalse()
            ->and($this->product->fresh()->rating_count)->toBe(0);
    });

    it('marks a verified purchase when a delivered order has the product', function () {
        $order = Order::factory()->delivered()->create(['user_id' => $this->customer->id]);
        OrderItem::factory()->for($order)->create(['product_id' => $this->product->id]);

        $this->actingAs($this->customer);
        submitReview($this->product);

        expect(Review::query()->sole()->is_verified_purchase)->toBeTrue();
    });

    it('does not count an order that is not delivered yet', function () {
        $order = Order::factory()->shipped()->create(['user_id' => $this->customer->id]);
        OrderItem::factory()->for($order)->create(['product_id' => $this->product->id]);

        $this->actingAs($this->customer);
        submitReview($this->product);

        expect(Review::query()->sole()->is_verified_purchase)->toBeFalse();
    });

    it('keeps one review per customer and sends an edit back for approval', function () {
        Review::factory()->approved()->create(['product_id' => $this->product->id, 'user_id' => $this->customer->id, 'rating' => 5]);
        $this->product->forceFill(['rating_average' => 5, 'rating_count' => 1])->save();

        $this->actingAs($this->customer);
        submitReview($this->product, ['rating' => 2, 'comment' => 'Started making noise after a week.']);

        $review = Review::query()->sole();
        expect($review->rating)->toBe(2)
            ->and($review->status)->toBe(ReviewStatus::Pending)
            ->and($this->product->fresh()->rating_count)->toBe(0);
    });

    it('validates rating and comment', function () {
        $this->actingAs($this->customer);

        submitReview($this->product, ['rating' => 6, 'comment' => 'short'])
            ->assertRedirect(route('product', $this->product->slug).'#tab-reviews')
            ->assertSessionHasErrors(['rating', 'comment']);
        submitReview($this->product, ['rating' => null])->assertSessionHasErrors(['rating' => 'Choose a star rating.']);
    });

    it('sends guests to sign in and refuses staff', function () {
        submitReview($this->product)->assertRedirect(route('login'));

        $this->actingAs(User::factory()->staff()->create());
        submitReview($this->product)->assertForbidden();

        expect(Review::query()->count())->toBe(0);
    });

    it('returns 404 for a product that is not listed', function () {
        $draft = Product::factory()->create();

        $this->actingAs($this->customer);
        submitReview($draft)->assertNotFound();
    });
});

describe('moderation', function () {
    it('lists pending reviews first with counts per status', function () {
        Review::factory()->create(['product_id' => $this->product->id, 'comment' => 'Waiting for a check here.']);
        Review::factory()->approved()->create(['comment' => 'Already approved one.']);

        actingAsStaff()->get(route('admin.reviews.index'))
            ->assertOk()
            ->assertSee('Waiting for a check here.')
            ->assertDontSee('Already approved one.')
            ->assertSee('Ceiling Fan 56 inch')
            ->assertViewHas('statusCounts', ['pending' => 1, 'approved' => 1, 'rejected' => 0]);

        actingAsStaff()->get(route('admin.reviews.index', ['status' => 'approved']))->assertSee('Already approved one.');
    });

    it('approves and rejects, recalculating the product rating', function () {
        $five = Review::factory()->create(['product_id' => $this->product->id, 'rating' => 5]);
        $two = Review::factory()->create(['product_id' => $this->product->id, 'rating' => 2]);
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('admin.reviews.approve', $five))->assertSessionHas('success');
        $this->actingAs($staff)->post(route('admin.reviews.approve', $two));

        $product = $this->product->fresh();
        expect((float) $product->rating_average)->toBe(3.5)
            ->and($product->rating_count)->toBe(2)
            ->and($five->fresh()->moderated_by)->toBe($staff->id);

        $this->actingAs($staff)->post(route('admin.reviews.reject', $two));

        $product = $this->product->fresh();
        expect((float) $product->rating_average)->toBe(5.0)
            ->and($product->rating_count)->toBe(1)
            ->and($two->fresh()->status)->toBe(ReviewStatus::Rejected);
    });

    it('shows the pending count in the sidebar', function () {
        Review::factory()->count(2)->create();

        actingAsStaff()->get(route('admin.reviews.index'))
            ->assertSeeInOrder(['Reviews', '<span class="nav-count">2</span>'], escape: false);
    });

    it('keeps customers out', function () {
        $review = Review::factory()->create();

        $this->actingAs($this->customer)->post(route('admin.reviews.approve', $review))->assertForbidden();
    });
});

describe('storefront', function () {
    it('shows approved reviews only, with breakdown and verified badge', function () {
        Review::factory()->approved()->verified()->create(['product_id' => $this->product->id, 'rating' => 5, 'comment' => 'Arrived well packed and works great.']);
        Review::factory()->create(['product_id' => $this->product->id, 'comment' => 'This one is still pending.']);
        $this->product->forceFill(['rating_average' => 5, 'rating_count' => 1])->save();

        $this->get(route('product', $this->product->slug))
            ->assertOk()
            ->assertSee('Arrived well packed and works great.')
            ->assertDontSee('This one is still pending.')
            ->assertSee('Verified Purchase')
            ->assertSee('Reviews (1)')
            ->assertSee('Sign in to write a review');
    });

    it('shows the customer their own review status in the form', function () {
        Review::factory()->create(['product_id' => $this->product->id, 'user_id' => $this->customer->id, 'comment' => 'My pending words here.']);

        $this->actingAs($this->customer)->get(route('product', $this->product->slug))
            ->assertSee('Your review is waiting for approval.')
            ->assertSee('Update Review')
            ->assertSee('My pending words here.');
    });

    it('shows the newest well-rated approved reviews on the home page', function () {
        Review::factory()->approved()->create(['product_id' => $this->product->id, 'rating' => 5, 'comment' => 'Five stars from Dhaka.']);
        Review::factory()->approved()->create(['product_id' => $this->product->id, 'rating' => 2, 'comment' => 'Two stars only here.']);

        $this->get(route('home'))
            ->assertSee('What Our Customers Say')
            ->assertSee('Five stars from Dhaka.')
            ->assertDontSee('Two stars only here.');
    });

    it('hides the home reviews section without reviews', function () {
        $this->get(route('home'))->assertDontSee('What Our Customers Say');
    });
});
