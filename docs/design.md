# UI Design

## Source of truth

- **Approved prototype:** `nischoyota-frontend/` (git submodule, static HTML).
- **Blade implementation:** `resources/views/`. All storefront pages already exist as static views (see `routes/web.php`).

When a feature makes a page dynamic, the rendered result must still match the prototype.

## Layout and components

- Layouts: TODO (list the layout files in `resources/views/components/`)
- Reusable components: `resources/views/components/`. Check here before creating a new one.

## Storefront shell

Header, footer, mobile menu and bottom nav get their data from `StorefrontShellComposer` (cached category menu, store settings, login state). Pass `active-nav` = `home`, `shop`, `deals` or a department slug.

## Product cards and listings

- Real product cards: `<x-product-card :card="$productCardData" />` (`ProductCardData` from `ProductListingService::toCard()`, money in paisa). Pages not yet dynamic still pass the old props.
- Listing filters are a GET form (`x-shop.listing` / `x-shop.filters`), auto-submitted by `data-auto-submit`; sort is its own small GET form that keeps the filters.
- Colour and size filters use the attributes with slug `color` and `size`.
- Listing scope is a `ListingScope` (whole shop / categories / deals / search). Forms carry `data-default-sort` so the page's default sort is left out of links.

## Design tokens

TODO: brand colours, typography, spacing, breakpoints (from the Tailwind config / `resources/css/app.css`).

## UX rules

TODO: e.g. empty states, loading states, form error display, toast messages, currency format (৳).

## Admin panel

Static Blade views under `/admin` (`admin.*` routes in `routes/web.php`), same design-phase approach as the storefront.

- **Layouts:** `x-layouts.admin` (dark sidebar + top bar + page header with `actions` slot), `x-layouts.admin-auth` (login). Shared `<head>`: `x-admin.head`.
- **Components:** `x-admin.stat-card`, `x-admin.status` (non-order statuses), `x-admin.sales-chart`. Reuses the storefront's `x-order.*`, `x-form.*`, `x-modal`, `x-confirm-modal`, `x-pagination`, `x-avatar`, `x-icon`.
- **Styles:** `resources/css/admin.css`, loaded after `app.css` and built on the same tokens. Classes: `.admin-table`, `.admin-toolbar`, `.admin-tabs`, `.admin-grid`, `.panel-flush`, `.switch`, `.upload-box`.
- **Responsive:** below 992px the sidebar becomes an off-canvas drawer (`data-drawer="admin-sidebar"`, handled by `storefront.js`) and page grids stack; below 768px toolbars stack and tables scroll horizontally.
- **Pages:** login, dashboard, orders (list + detail), products (list + form), categories, brands, attributes (list + per-attribute values page), product variants, inventory (list + per-variant adjust/history), customers, reviews, coupons, settings.
- **List + form pages** (categories, brands): table panel on the left (toolbar search, empty state, `panel-foot` with count + pagination), form panel on the right that switches between Add and Edit (`/{id}/edit`); delete via `<x-confirm-modal method="DELETE">`. New admin CRUD pages copy this layout.
