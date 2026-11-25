{{--
    Product create / edit form (ProductController@create / @edit).
    $product          Product with defaultVariant + images, or null when creating
    $categoryOptions  id => "Electrical" / "— Fans"
    $brandOptions     id => name
    $statusOptions    value => label
    $maxImages        Image limit per product
    $hasOptions       true when variants are managed on the Variants page (no price/stock fields here)
    Prices are typed in taka; cost price is shown to admins only (ADR-012).
--}}
@use('App\Support\Money')

@php
    $isEditing = $product !== null;
    $variant = $product?->defaultVariant;
    $canSeeCostPrice = auth()->user()->can('manage-store');
    $specifications = old('specifications', $product?->specifications ?? []);
    $specificationRows = $specifications === [] ? [['label' => '', 'value' => '']] : $specifications;
    $takaValue = fn (?int $paisa) => $paisa === null ? null : Money::toTakaInput($paisa);
@endphp

<x-layouts.admin :title="$isEditing ? 'Edit Product' : 'Add Product'" active="products"
                 :subtitle="$isEditing ? $product->name : 'Fill in the details and save to publish.'"
                 :back-url="route('admin.products.index')" back-label="Products">
    <form method="POST" enctype="multipart/form-data"
          action="{{ $isEditing ? route('admin.products.update', $product) : route('admin.products.store') }}">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="admin-grid">
            <div>
                <section class="panel">
                    <h2 class="panel-title">Basic Information</h2>
                    <x-form.input name="name" label="Product Name" placeholder="e.g. Sundarban Honey 500g" :value="$product?->name" required />
                    <div class="form-row">
                        <x-form.select name="sell_unit" label="Sold by (unit)" :options="\App\Modules\Catalog\Enums\SellUnit::options()" :selected="$product?->sell_unit?->value ?? 'piece'" />
                        <x-form.input name="unit_quantity" type="number" step="0.01" min="0.01" label="Amount per unit" placeholder="e.g. 500" :value="$product?->unit_quantity" optional hint="e.g. 500 + Gram = price is per 500 g. Leave empty for 1 pc." />
                    </div>
                    <x-form.textarea name="short_description" label="Short Description" rows="2" placeholder="One or two lines shown near the price" :value="$product?->short_description" />
                    <x-form.textarea name="description" label="Full Description" rows="6" placeholder="Features, materials, what's in the box…" :value="$product?->description" />
                    <x-form.input name="name_bn" label="Product Name (বাংলা)" placeholder="যেমন সুন্দরবনের মধু ৫০০ গ্রাম" :value="$product?->name_bn" optional hint="Shown instead of the name when the visitor uses Bangla." />
                    <x-form.textarea name="short_description_bn" label="Short Description (বাংলা)" rows="2" :value="$product?->short_description_bn" />
                    <x-form.textarea name="description_bn" label="Full Description (বাংলা)" rows="6" :value="$product?->description_bn" />
                    <p class="form-hint">Formatting: empty line = new paragraph, <code>- item</code> = bullet list, <code>**bold**</code>. HTML is removed.</p>
                </section>

                @if ($hasOptions)
                    <section class="panel">
                        <div class="panel-head">
                            <h2 class="panel-title mb-0">Pricing &amp; Stock</h2>
                            <a class="btn btn-outline btn-sm" href="{{ route('admin.products.variants.edit', $product) }}">Manage variants</a>
                        </div>
                        <p class="mb-0">
                            This product has options, so each variant has its own SKU, price and stock.
                            Lowest price: <strong><x-money :amount="$product->min_price" /></strong>.
                        </p>
                    </section>
                @else
                    <section class="panel">
                        <h2 class="panel-title">Pricing &amp; Stock</h2>
                        <div @class(['form-row-3' => $canSeeCostPrice, 'form-row' => ! $canSeeCostPrice])>
                            @include('admin.products.partials.money-input', ['name' => 'price', 'label' => 'Selling Price', 'value' => $takaValue($variant?->price), 'required' => true])
                            @include('admin.products.partials.money-input', ['name' => 'compare_price', 'label' => 'Old Price', 'value' => $takaValue($variant?->compare_price), 'hint' => 'Shown with a strikethrough.'])
                            @if ($canSeeCostPrice)
                                @include('admin.products.partials.money-input', ['name' => 'cost_price', 'label' => 'Cost Price', 'value' => $takaValue($variant?->cost_price), 'hint' => 'Admin only, for profit reports.'])
                            @endif
                        </div>
                        <div class="form-row-3">
                            <x-form.input name="sku" label="SKU" placeholder="Auto: NS-EL-0035" :value="$variant?->sku" hint="Leave empty to generate." />
                            @if ($isEditing)
                                <div class="form-group">
                                    <label>Stock</label>
                                    <p class="stock-readonly">
                                        <strong>{{ $variant?->stock_quantity ?? 0 }}</strong>
                                        @if ($variant)<a href="{{ route('admin.inventory.show', $variant) }}">Adjust stock</a>@endif
                                    </p>
                                    <p class="form-hint mb-0">Stock changes are logged in Inventory.</p>
                                </div>
                            @else
                                <x-form.input name="stock_quantity" type="number" label="Opening Stock" min="0" placeholder="0" value="0" required />
                            @endif
                            <x-form.input name="weight_grams" type="number" label="Weight (grams)" min="1" placeholder="e.g. 1500" :value="$variant?->weight_grams" optional />
                        </div>
                    </section>
                    @if ($isEditing)
                        <p class="form-hint">Sold in different colours or sizes? <a href="{{ route('admin.products.variants.edit', $product) }}">Add options and variants</a>.</p>
                    @endif
                @endif

                <section class="panel">
                    <h2 class="panel-title">Product Images</h2>
                    @if ($isEditing && $product->images->isNotEmpty())
                        <div class="image-manager">
                            @foreach ($product->images as $image)
                                <div class="image-manager-item">
                                    <img src="{{ $image->url() }}" alt="Image {{ $loop->iteration }}" width="120" height="120">
                                    <label class="check"><input type="radio" name="cover_image" value="{{ $image->id }}" @checked((int) old('cover_image', $product->images->first()->id) === $image->id)> Cover</label>
                                    <label class="check"><input type="checkbox" name="remove_images[]" value="{{ $image->id }}" @checked(in_array($image->id, old('remove_images', [])))> Remove</label>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label class="upload-box">
                        <x-icon name="image" />
                        <strong>{{ $isEditing && $product->images->isNotEmpty() ? 'Add more images' : 'Click to upload images' }}</strong>
                        JPG, PNG or WebP, up to 2 MB each, max {{ $maxImages }} per product. The first image is the cover.
                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    </label>
                    @error('images')<p class="form-error">{{ $message }}</p>@enderror
                    @foreach ($errors->get('images.*') as $imageErrors)
                        <p class="form-error">{{ $imageErrors[0] }}</p>
                    @endforeach
                </section>

                <section class="panel" data-repeater>
                    <h2 class="panel-title">Specifications</h2>
                    <p class="form-hint">Shown in the product's "Specifications" tab, e.g. Motor → 100% Copper.</p>
                    <div class="spec-rows" data-repeater-rows>
                        @foreach ($specificationRows as $index => $row)
                            @include('admin.products.partials.spec-row', ['index' => $index, 'row' => $row])
                        @endforeach
                    </div>
                    <template data-repeater-template>
                        @include('admin.products.partials.spec-row', ['index' => '__INDEX__', 'row' => ['label' => '', 'value' => '']])
                    </template>
                    <button class="btn btn-outline btn-sm" type="button" data-repeater-add><x-icon name="plus" class="icon-sm" /> Add Specification</button>
                    @error('specifications')<p class="form-error">{{ $message }}</p>@enderror
                </section>
            </div>

            <div>
                <section class="panel">
                    <h2 class="panel-title">Publish</h2>
                    <x-form.select name="status" label="Status" :options="$statusOptions" :selected="$product?->status->value ?? 'draft'" />
                    <label class="switch">
                        <span class="switch-text"><strong>Featured</strong><span>Show on the home page</span></span>
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product?->is_featured))>
                    </label>
                    <label class="switch">
                        <span class="switch-text"><strong>Allow pre-order</strong><span>Sell even when out of stock</span></span>
                        <input type="hidden" name="allow_preorder" value="0">
                        <input type="checkbox" name="allow_preorder" value="1" @checked(old('allow_preorder', $product?->allow_preorder))>
                    </label>
                </section>

                <section class="panel">
                    <h2 class="panel-title">Organization</h2>
                    <x-form.select name="category_id" label="Category" placeholder="Choose a category" :options="$categoryOptions" :selected="$product?->category_id" required />
                    <x-form.select name="brand_id" label="Brand" placeholder="No brand" :options="$brandOptions" :selected="$product?->brand_id" />
                </section>

                <section class="panel">
                    <h2 class="panel-title">Search Engine (SEO)</h2>
                    <x-form.input name="slug" label="URL Slug" placeholder="ceiling-fan-56-inch" :value="$product?->slug" hint="Leave empty to generate from the name." />
                    <x-form.input name="meta_title" label="SEO Title" :value="$product?->meta_title" optional />
                    <x-form.textarea name="meta_description" label="SEO Description" rows="3" :value="$product?->meta_description" />
                </section>

                <div class="form-actions">
                    <a class="btn btn-outline" href="{{ route('admin.products.index') }}">Cancel</a>
                    <button class="btn btn-primary" type="submit">{{ $isEditing ? 'Update Product' : 'Save Product' }}</button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.admin>
