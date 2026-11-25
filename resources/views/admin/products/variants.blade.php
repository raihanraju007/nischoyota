{{--
    Variants of one product (ProductVariantController@edit).
    $product          Product with variants (attributeValues.attribute, ordered by id) and images
    $attributes       ProductAttribute collection with values, for the options panel
    $maxAttributes / $maxCombinations   Limits shown in the hint
    $lowStockThreshold  Store setting
    Prices are typed in taka; cost price is shown to admins only (ADR-012).
--}}
@use('App\Support\Money')

@php
    $canSeeCostPrice = auth()->user()->can('manage-store');
    $chosenValueIds = $product->variants->flatMap->attributeValues->pluck('id')->unique()->values()->all();
    $defaultVariantId = (int) old('default_variant', $product->variants->firstWhere('is_default', true)?->id);
    $takaValue = fn (?int $paisa) => $paisa === null ? null : Money::toTakaInput($paisa);
    $hasOptions = $chosenValueIds !== [];
@endphp

<x-layouts.admin :title="__('Variants')" active="products" :subtitle="$product->name"
                 :back-url="route('admin.products.edit', $product)" back-label="Back to product">
    <section class="panel">
        <h2 class="panel-title">{{ __('Options') }}</h2>
        <p class="form-hint">
            Tick the values this product is sold in, then generate. Existing variants keep their SKU, price and stock;
            new ones copy the default variant's price and start with stock 0 (add stock in Inventory); unticked combinations are deleted.
            Up to {{ $maxAttributes }} options and {{ $maxCombinations }} variants. Untick everything to make it a simple product again.
        </p>

        @if ($attributes->isEmpty())
            <x-empty-state icon="filter" :title="__('No options yet')" small :action-url="route('admin.attributes.index')" action-label="Create options">
                {{ __('Create options such as Color or Size first.') }}
            </x-empty-state>
        @else
            <form method="POST" action="{{ route('admin.products.variants.generate', $product) }}">
                @csrf
                <div class="option-groups">
                    @foreach ($attributes as $attribute)
                        <fieldset class="option-group">
                            <legend>{{ $attribute->name }}</legend>
                            @forelse ($attribute->values as $value)
                                <label class="check">
                                    <input type="checkbox" name="values[{{ $attribute->id }}][]" value="{{ $value->id }}"
                                           @checked(in_array($value->id, old("values.{$attribute->id}", $chosenValueIds)))>
                                    @if ($value->color_hex)
                                        <span class="swatch swatch-sm" style="background: {{ $value->color_hex }}" aria-hidden="true"></span>
                                    @endif
                                    {{ $value->value }}
                                </label>
                            @empty
                                <span class="text-muted">{{ __('No values') }}</span>
                            @endforelse
                        </fieldset>
                    @endforeach
                </div>
                @error('values')<p class="form-error">{{ $message }}</p>@enderror
                <button class="btn btn-primary" type="submit">{{ __('Generate variants') }}</button>
            </form>
        @endif
    </section>

    <section class="panel panel-flush">
        <form method="POST" action="{{ route('admin.products.variants.update', $product) }}">
            @csrf
            @method('PUT')
            <div class="panel-head variants-head">
                <h2 class="panel-title mb-0">{{ trans_choice(':count variant|:count variants', $product->variants->count()) }}</h2>
                <button class="btn btn-primary btn-sm" type="submit">{{ __('Save variants') }}</button>
            </div>
            @error('default_variant')<p class="form-error variants-error">{{ $message }}</p>@enderror

            <div class="order-table-wrap">
                <table class="admin-table variants-table">
                    <thead>
                        <tr>
                            <th>{{ __('Variant') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Price (৳)') }}</th>
                            <th>{{ __('Old price (৳)') }}</th>
                            @if ($canSeeCostPrice)<th>{{ __('Cost (৳)') }}</th>@endif
                            <th>{{ __('Stock') }}</th>
                            <th>{{ __('Photo') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th>{{ __('Default') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($product->variants as $variant)
                            @php($field = fn (string $name) => "variants[{$variant->id}][{$name}]")
                            @php($old = fn (string $name, mixed $current) => old("variants.{$variant->id}.{$name}", $current))
                            <tr>
                                <td><strong>{{ $variant->label() }}</strong></td>
                                <td>
                                    <input class="input input-sm @error("variants.{$variant->id}.sku") is-invalid @enderror" name="{{ $field('sku') }}"
                                           value="{{ $old('sku', $variant->sku) }}" aria-label="SKU of {{ $variant->label() }}" maxlength="40">
                                    @error("variants.{$variant->id}.sku")<p class="form-error">{{ $message }}</p>@enderror
                                </td>
                                <td>
                                    <input class="input input-sm @error("variants.{$variant->id}.price") is-invalid @enderror" type="number" min="0" step="0.01" required
                                           name="{{ $field('price') }}" value="{{ $old('price', $takaValue($variant->price)) }}" aria-label="Price of {{ $variant->label() }}">
                                    @error("variants.{$variant->id}.price")<p class="form-error">{{ $message }}</p>@enderror
                                </td>
                                <td>
                                    <input class="input input-sm @error("variants.{$variant->id}.compare_price") is-invalid @enderror" type="number" min="0" step="0.01"
                                           name="{{ $field('compare_price') }}" value="{{ $old('compare_price', $takaValue($variant->compare_price)) }}" aria-label="Old price of {{ $variant->label() }}">
                                    @error("variants.{$variant->id}.compare_price")<p class="form-error">{{ $message }}</p>@enderror
                                </td>
                                @if ($canSeeCostPrice)
                                    <td>
                                        <input class="input input-sm" type="number" min="0" step="0.01"
                                               name="{{ $field('cost_price') }}" value="{{ $old('cost_price', $takaValue($variant->cost_price)) }}" aria-label="Cost price of {{ $variant->label() }}">
                                    </td>
                                @endif
                                <td>
                                    <a href="{{ route('admin.inventory.show', $variant) }}" @class(['stock-link', 'stock-out' => $variant->isOutOfStock(), 'stock-low' => $variant->isLowOnStock($lowStockThreshold)])
                                       aria-label="Adjust stock of {{ $variant->label() }}">{{ $variant->stock_quantity }}</a>
                                </td>
                                <td>
                                    <select class="select input-sm" name="{{ $field('product_image_id') }}" aria-label="Photo of {{ $variant->label() }}">
                                        <option value="">{{ __('Product cover') }}</option>
                                        @foreach ($product->images as $image)
                                            <option value="{{ $image->id }}" @selected((int) $old('product_image_id', $variant->product_image_id) === $image->id)>Photo {{ $loop->iteration }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="{{ $field('is_active') }}" value="0">
                                    <input type="checkbox" name="{{ $field('is_active') }}" value="1" @checked($old('is_active', $variant->is_active)) aria-label="{{ $variant->label() }} is active">
                                </td>
                                <td>
                                    <input type="radio" name="default_variant" value="{{ $variant->id }}" @checked($defaultVariantId === $variant->id) aria-label="{{ $variant->label() }} is the default">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($product->images->isNotEmpty())
                <div class="variant-photos">
                    <span class="table-sub">{{ __('Photos:') }}</span>
                    @foreach ($product->images as $image)
                        <figure>
                            <img src="{{ $image->url() }}" alt="" width="56" height="56" loading="lazy">
                            <figcaption>Photo {{ $loop->iteration }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif
        </form>
    </section>
</x-layouts.admin>
