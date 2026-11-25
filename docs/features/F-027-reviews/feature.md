# F-027: Reviews

- **Status:** Done
- **Module:** `app/Modules/Review` (new, planned in ADR-008)
- **Phase:** 1
- **Depends on:** F-024

## 1. Objective
Customers rate and review products; staff approve them before they appear; product ratings come from approved reviews.

## 2. User stories
- As a **customer**, I want to rate a product and write a review, and change it later.
- As a **shopper**, I want to see approved reviews, the rating breakdown and whether a reviewer actually bought the product.
- As **staff**, I want to approve or reject reviews.

## 3. Business rules
1. Only signed-in, active **customers** can write a review. Guests see "Sign in to write a review".
2. **One review per customer per product.** Submitting again updates it and sends it back to `pending`.
3. Rating 1–5 (required), comment 10–1000 characters (required, plain text).
4. **Verified purchase** = the customer has a **delivered** order containing the product, checked when the review is saved (`CustomerOrderService::hasDeliveredProduct()`).
5. New and edited reviews are `pending`; only `approved` reviews are public. Staff approve / reject from `/admin/reviews` (tabs Pending / Approved / Rejected with counts, newest first, 20 per page). Rejected reviews are kept (a customer who edits gets another check).
6. **Rating cache:** `products.rating_average` (2 decimals) and `rating_count` are recalculated from approved reviews after every approve, reject or edit, through `ProductService::updateRatingCache()` (Catalog owns the product row).
7. Product page "Reviews" tab: average, count, 5★…1★ breakdown bars, the 10 newest approved reviews (name, date, rating, verified badge, comment), then the review form or the customer's own review status ("waiting for approval" / "not published").
8. Home page "What Our Customers Say": the 3 newest approved reviews with 4–5 stars; the section is hidden when there are none.
9. Sidebar "Reviews" badge = pending reviews (hidden at 0).

## 4. Scope
- Migration `reviews`; `Review` model + factory; `ReviewStatus`, `ReviewData`, `ReviewSummary`
- `ReviewRepository`, `ReviewService`, `Http\Controllers\ReviewController` (store), `Admin\ReviewController` (index, approve, reject), `SaveReviewRequest`, `ListReviewsRequest`
- Catalog: `ProductService::updateRatingCache()`; Order: `CustomerOrderService::hasDeliveredProduct()`
- `ProductReviewsComposer` (product page), `HomeReviewsComposer` (home), admin badge
- Views: product page Reviews tab, `admin/reviews/index`, home testimonials

## 5. Out of scope
- Review photos, helpful votes, staff replies, review reminders by SMS (F-029), showing reviews on cards beyond the rating

## 7. Database
**reviews**: `product_id` FK cascade, `user_id` FK cascade, `rating` tinyint, `comment` text, `is_verified_purchase` bool, `status` string(20) index, `moderated_by` FK users null on delete, `moderated_at` timestamp null, timestamps; unique (`product_id`, `user_id`); index (`product_id`, `status`).

## 8. Routes
| Method | URI | Name | Auth |
|---|---|---|---|
| POST | `/product/{slug}/reviews` | `products.reviews.store` | `auth`, `active`, `throttle:10,1` |
| GET | `/admin/reviews` | `admin.reviews.index` | staff + admin |
| POST | `/admin/reviews/{review}/approve` | `admin.reviews.approve` | staff + admin |
| POST | `/admin/reviews/{review}/reject` | `admin.reviews.reject` | staff + admin |

## 9. Security
- The comment is escaped output (no Markdown/HTML). Staff/admin accounts cannot post reviews (403).
- A review can only be written for a listed (active) product.

## 10. Edge cases
- Reject an approved review → it disappears and the rating is recalculated
- Edit an approved review → back to pending, rating recalculated without it
- Product with no approved reviews → average 0, count 0

## 11. Acceptance criteria
- [x] One review per customer per product; edit sends back to pending
- [x] Verified purchase from delivered orders
- [x] Admin approve/reject with tabs and counts
- [x] Rating cache updated on every moderation/edit
- [x] Product page shows summary, breakdown, approved reviews, form
- [x] Home page shows real reviews

## 12. Multi-vendor impact
None; reviews belong to products, which would later belong to vendors.

## 13. Open questions (decided — user: "all approved", 2026-10-07)
- [x] Any signed-in customer may review (badge shows verified purchases), all reviews moderated first.
- [x] Reviewer shown by full account name.
