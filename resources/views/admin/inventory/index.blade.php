{{--
    Stock levels of all variants (InventoryController@index).
    $variants   LengthAwarePaginator<ProductVariant> with product, attributeValues.attribute
    $tabCounts  tab value => count
    $filter     StockFilter (tab, search)
    $tabs       StockListTab cases
    $lowStockThreshold  Store setting
--}}
@php
    $tabUrl = fn ($tab) => route('admin.inventory.index', array_filter([
        'tab' => $tab->value === 'all' ? null : $tab->value,
        'search' => $filter->search,
    ]));
@endphp

<x-layouts.admin title="Inventory" active="inventory" subtitle="Stock of every product variant. Every change is logged.">
    <section class="panel panel-flush">
        <nav class="admin-tabs" aria-label="Filter by stock level">
            @foreach ($tabs as $tab)
                <a href="{{ $tabUrl($tab) }}" @class(['active' => $filter->tab === $tab]) @if ($filter->tab === $tab) aria-current="page" @endif>
                    {{ $tab->label() }} <span class="tab-count">{{ $tabCounts[$tab->value] }}</span>
                </a>
            @endforeach
        </nav>

        <form class="admin-toolbar" method="GET" action="{{ route('admin.inventory.index') }}" role="search">
            @if ($filter->tab->value !== 'all')
                <input type="hidden" name="tab" value="{{ $filter->tab->value }}">
            @endif
            <div class="admin-search">
                <x-icon name="search" class="icon-sm" />
                <label for="stock-search" class="visually-hidden">Search stock</label>
                <input class="input" type="search" id="stock-search" name="search" value="{{ $filter->search }}" placeholder="Product name or SKU…">
            </div>
            <div class="toolbar-end">
                <button class="btn btn-outline btn-sm" type="submit"><x-icon name="search" class="icon-sm" /> Search</button>
            </div>
        </form>

        @if ($variants->isEmpty())
            <x-empty-state icon="box" title="No variants here" small>
                @if ($filter->search || $filter->tab->value !== 'all')
                    <a href="{{ route('admin.inventory.index') }}">Show all stock</a>
                @else
                    Add products first.
                @endif
            </x-empty-state>
        @else
            <div class="order-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="num">Stock</th>
                            <th>Status</th>
                            <th><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($variants as $variant)
                            <tr>
                                <td>
                                    <strong>{{ $variant->product->name }}</strong>
                                    @if ($variant->attributeValues->isNotEmpty())
                                        <span class="table-sub">{{ $variant->label() }}</span>
                                    @endif
                                </td>
                                <td>{{ $variant->sku }}</td>
                                <td @class(['num', 'stock-out' => $variant->isOutOfStock(), 'stock-low' => $variant->isLowOnStock($lowStockThreshold)])>{{ $variant->stock_quantity }}</td>
                                <td>
                                    <x-admin.status :status="$variant->isOutOfStock() ? 'out of stock' : ($variant->isLowOnStock($lowStockThreshold) ? 'low stock' : 'in stock')" />
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a class="btn btn-outline btn-sm" href="{{ route('admin.inventory.show', $variant) }}">Adjust</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="panel-foot">
                <span>Showing {{ $variants->firstItem() }}–{{ $variants->lastItem() }} of {{ $variants->total() }} variants</span>
                {{ $variants->links() }}
            </div>
        @endif
    </section>
</x-layouts.admin>
