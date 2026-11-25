{{--
    One variant's stock: adjust form + movement history (InventoryController@show).
    $variant    ProductVariant with product, attributeValues.attribute
    $movements  LengthAwarePaginator<StockMovement> with user, newest first
    $reasons    Manual StockMovementReason cases
    $lowStockThreshold  Store setting
--}}
@php
    $title = $variant->attributeValues->isEmpty() ? $variant->product->name : $variant->product->name.' — '.$variant->label();
    $selectedReason = old('reason', 'restock');
@endphp

<x-layouts.admin :title="$title" active="inventory" :subtitle="'SKU: '.$variant->sku"
                 :back-url="route('admin.inventory.index')" back-label="Inventory">
    <div class="admin-grid">
        <section class="panel panel-flush">
            <div class="panel-head variants-head">
                <h2 class="panel-title mb-0">Stock history</h2>
                <a class="btn btn-ghost btn-sm" href="{{ route('admin.products.edit', $variant->product_id) }}">Open product</a>
            </div>
            @if ($movements->isEmpty())
                <x-empty-state icon="clock" title="No stock changes yet" small>
                    Changes made with the form are listed here.
                </x-empty-state>
            @else
                <div class="order-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reason</th>
                                <th class="num">Change</th>
                                <th class="num">Balance</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movements as $movement)
                                <tr>
                                    <td>{{ $movement->created_at->format('d M Y, h:i A') }}</td>
                                    <td>
                                        {{ $movement->reason->label() }}
                                        @if ($movement->note)<span class="table-sub table-wrap">{{ $movement->note }}</span>@endif
                                    </td>
                                    <td @class(['num', 'stock-change-up' => $movement->quantity_change > 0, 'stock-change-down' => $movement->quantity_change < 0])>
                                        {{ $movement->quantity_change > 0 ? '+' : '' }}{{ $movement->quantity_change }}
                                    </td>
                                    <td class="num">{{ $movement->stock_after }}</td>
                                    <td>{{ $movement->user?->name ?? 'System' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="panel-foot">
                    <span>{{ trans_choice(':count change|:count changes', $movements->total()) }}</span>
                    {{ $movements->links() }}
                </div>
            @endif
        </section>

        <div>
            <section class="panel">
                <div class="stock-summary">
                    <div>
                        <span class="text-muted">In stock</span>
                        <strong @class(['stock-out' => $variant->isOutOfStock(), 'stock-low' => $variant->isLowOnStock($lowStockThreshold)])>{{ $variant->stock_quantity }}</strong>
                    </div>
                    <div>
                        <span class="text-muted">Price</span>
                        <strong><x-money :amount="$variant->price" /></strong>
                    </div>
                </div>
            </section>

            <section class="panel">
                <h2 class="panel-title">Adjust stock</h2>
                <form method="POST" action="{{ route('admin.inventory.adjust', $variant) }}">
                    @csrf
                    <fieldset class="adjust-reasons">
                        <legend class="visually-hidden">What happened?</legend>
                        @foreach ($reasons as $reason)
                            <label class="check">
                                <input type="radio" name="reason" value="{{ $reason->value }}" @checked($selectedReason === $reason->value)>
                                <span>
                                    <strong>{{ $reason->label() }}</strong>
                                    <span class="table-sub">{{ $reason->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </fieldset>
                    @error('reason')<p class="form-error">{{ $message }}</p>@enderror
                    <x-form.input name="quantity" type="number" min="0" label="Quantity" placeholder="e.g. 10" required hint="For a stock count, enter the total you counted." />
                    <x-form.input name="note" label="Note" placeholder="e.g. Supplier invoice #123" maxlength="255" optional />
                    <button class="btn btn-primary btn-block" type="submit">Save adjustment</button>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
