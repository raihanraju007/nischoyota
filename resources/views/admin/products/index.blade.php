{{--
    Product list (ProductController@index).
    $products         LengthAwarePaginator<Product> with category, defaultVariant, coverImage
    $tabCounts        tab value => count for the current search/category
    $filter           ProductFilter (tab, search, categoryId)
    $tabs             ProductListTab cases
    $bulkActions      BulkProductAction cases
    $categoryOptions  id => label for the category filter
    $lowStockThreshold  Store setting
--}}
@php
    $tabUrl = fn ($tab) => route('admin.products.index', array_filter([
        'tab' => $tab->value === 'all' ? null : $tab->value,
        'search' => $filter->search,
        'category' => $filter->categoryId,
    ]));
@endphp

<x-layouts.admin title="Products" active="products" :subtitle="trans_choice(':count product in your catalog.|:count products in your catalog.', $tabCounts['all'])">
    <x-slot:actions>
        <a class="btn btn-primary" href="{{ route('admin.products.create') }}"><x-icon name="plus" class="icon-sm" /> Add Product</a>
    </x-slot:actions>

    <section class="panel panel-flush">
        <nav class="admin-tabs" aria-label="Filter by status">
            @foreach ($tabs as $tab)
                <a href="{{ $tabUrl($tab) }}" @class(['active' => $filter->tab === $tab]) @if ($filter->tab === $tab) aria-current="page" @endif>
                    {{ $tab->label() }} <span class="tab-count">{{ $tabCounts[$tab->value] }}</span>
                </a>
            @endforeach
        </nav>

        <form class="admin-toolbar" method="GET" action="{{ route('admin.products.index') }}" role="search">
            @if ($filter->tab->value !== 'all')
                <input type="hidden" name="tab" value="{{ $filter->tab->value }}">
            @endif
            <div class="admin-search">
                <x-icon name="search" class="icon-sm" />
                <label for="product-search" class="visually-hidden">Search products</label>
                <input class="input" type="search" id="product-search" name="search" value="{{ $filter->search }}" placeholder="Product name or SKU…">
            </div>
            <label for="product-category" class="visually-hidden">Category</label>
            <select class="select" id="product-category" name="category">
                <option value="">All categories</option>
                @foreach ($categoryOptions as $categoryId => $categoryLabel)
                    <option value="{{ $categoryId }}" @selected($filter->categoryId === $categoryId)>{{ $categoryLabel }}</option>
                @endforeach
            </select>
            <div class="toolbar-end">
                <button class="btn btn-outline btn-sm" type="submit"><x-icon name="filter" class="icon-sm" /> Filter</button>
            </div>
        </form>

        @if ($products->isEmpty())
            @if ($filter->search || $filter->categoryId || $filter->tab->value !== 'all')
                <x-empty-state icon="search-off" title="No products match these filters" small>
                    <a href="{{ route('admin.products.index') }}">Clear all filters</a>
                </x-empty-state>
            @else
                <x-empty-state icon="box" title="No products yet" small :action-url="route('admin.products.create')" action-label="Add Product">
                    Add your first product to start selling.
                </x-empty-state>
            @endif
        @else
            <form id="bulk-form" class="admin-toolbar bulk-bar" method="POST" action="{{ route('admin.products.bulk') }}">
                @csrf
                <label for="bulk-action" class="visually-hidden">Bulk action</label>
                <select class="select" id="bulk-action" name="action">
                    <option value="">Bulk action…</option>
                    @foreach ($bulkActions as $bulkAction)
                        <option value="{{ $bulkAction->value }}">{{ $bulkAction->label() }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline btn-sm" type="submit">Apply to selected</button>
                @error('ids')<span class="form-error mb-0">{{ $message }}</span>@enderror
                @error('action')<span class="form-error mb-0">{{ $message }}</span>@enderror
            </form>

            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th class="col-check"><input type="checkbox" data-check-all="ids[]" aria-label="Select all products on this page"></th>
                            <th>Product</th>
                            <th>Category</th>
                            <th class="num">Price</th>
                            <th class="num">Stock</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            @php($variant = $product->defaultVariant)
                            <tr>
                                <td class="col-check"><input type="checkbox" name="ids[]" value="{{ $product->id }}" form="bulk-form" aria-label="Select {{ $product->name }}"></td>
                                <td>
                                    <div class="table-product">
                                        @if ($product->coverImage)
                                            <img src="{{ $product->coverImage->url() }}" alt="" width="44" height="44" loading="lazy">
                                        @else
                                            <span class="table-thumb-empty"><x-icon name="image" class="icon-sm" /></span>
                                        @endif
                                        <div>
                                            <a class="link" href="{{ route('admin.products.edit', $product) }}"><strong>{{ $product->name }}</strong></a>
                                            <span class="table-sub">SKU: {{ $variant?->sku ?? '—' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $product->category->name }}</td>
                                @php($stock = $product->activeStock())
                                <td class="num strong">
                                    @if ($product->variants_count > 1)<span class="table-sub">from</span>@endif
                                    <x-money :amount="$product->min_price" />
                                </td>
                                <td @class(['num', 'stock-out' => $stock <= 0, 'stock-low' => $stock > 0 && $stock <= $lowStockThreshold])>
                                    {{ $stock }}
                                    @if ($product->variants_count > 1)<span class="table-sub">{{ $product->variants_count }} variants</span>@endif
                                </td>
                                <td><x-admin.status :status="$product->status->value" /></td>
                                <td>
                                    <div class="table-actions">
                                        <a class="icon-btn icon-btn-sm" href="{{ route('admin.products.edit', $product) }}" aria-label="Edit {{ $product->name }}">
                                            <x-icon name="edit" class="icon-sm" />
                                        </a>
                                        <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-product"
                                                data-modal-action="{{ route('admin.products.destroy', $product) }}" aria-label="Delete {{ $product->name }}">
                                            <x-icon name="trash" class="icon-sm" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel-foot">
                <span>Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }} products</span>
                {{ $products->links() }}
            </div>
        @endif
    </section>

    @push('overlays')
        <x-confirm-modal id="modal-delete-product" title="Delete this product?" confirm-label="Yes, Delete" method="DELETE">
            The product, its stock record and its images are removed. To hide it instead, set its status to Archived.
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
