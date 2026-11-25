{{--
    Brands: searchable list + add/edit form (BrandController@index / @edit).
    $brands        LengthAwarePaginator<Brand>
    $search        Current search term or null
    $editingBrand  Brand being edited, or null for "Add Brand"
--}}
@php
    $isEditing = $editingBrand !== null;
@endphp

<x-layouts.admin title="Brands" active="brands" subtitle="Brands customers can filter products by.">
    <div class="admin-grid">
        <section class="panel panel-flush">
            <form class="admin-toolbar" method="GET" action="{{ route('admin.brands.index') }}" role="search">
                <div class="admin-search">
                    <x-icon name="search" class="icon-sm" />
                    <label for="brand-search" class="visually-hidden">Search brands</label>
                    <input class="input" type="search" id="brand-search" name="search" value="{{ $search }}" placeholder="Brand name…">
                </div>
                <div class="toolbar-end">
                    <button class="btn btn-outline btn-sm" type="submit"><x-icon name="search" class="icon-sm" /> Search</button>
                </div>
            </form>

            @if ($brands->isEmpty())
                @if ($search)
                    <x-empty-state icon="search-off" title="No brands match “{{ $search }}”" small>
                        <a href="{{ route('admin.brands.index') }}">Clear the search</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="shield" title="No brands yet" small>
                        Add your first brand with the form.
                    </x-empty-state>
                @endif
            @else
                <div class="order-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Brand</th>
                                <th class="num">Products</th>
                                <th>Status</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($brands as $brand)
                                <tr>
                                    <td>
                                        <div class="table-product">
                                            @if ($brand->logo)
                                                <img src="{{ $brand->logoUrl() }}" alt="" width="44" height="44">
                                            @else
                                                <span class="table-thumb-empty"><x-icon name="image" class="icon-sm" /></span>
                                            @endif
                                            <div>
                                                <strong>{{ $brand->name }}</strong>
                                                <span class="table-sub">/{{ $brand->slug }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num">{{ $brand->products_count }}</td>
                                    <td><x-admin.status :status="$brand->is_active ? 'active' : 'hidden'" /></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="icon-btn icon-btn-sm" href="{{ route('admin.brands.edit', $brand) }}" aria-label="Edit {{ $brand->name }}">
                                                <x-icon name="edit" class="icon-sm" />
                                            </a>
                                            <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-brand"
                                                    data-modal-action="{{ route('admin.brands.destroy', $brand) }}" aria-label="Delete {{ $brand->name }}">
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
                    <span>Showing {{ $brands->firstItem() }}–{{ $brands->lastItem() }} of {{ $brands->total() }} brands</span>
                    {{ $brands->links() }}
                </div>
            @endif
        </section>

        <section class="panel">
            <h2 class="panel-title">{{ $isEditing ? 'Edit Brand' : 'Add Brand' }}</h2>
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $isEditing ? route('admin.brands.update', $editingBrand) : route('admin.brands.store') }}">
                @csrf
                @if ($isEditing)
                    @method('PUT')
                @endif

                <x-form.input name="name" id="brand-name" label="Name" placeholder="e.g. Walton" :value="$editingBrand?->name" required />
                <x-form.input name="slug" id="brand-slug" label="Slug" placeholder="walton" :value="$editingBrand?->slug" hint="Used in the URL. Leave empty to generate from the name." />

                <div class="form-group">
                    <label for="brand-logo">Logo</label>
                    @if ($editingBrand?->logo)
                        <div class="table-product form-current-image">
                            <img src="{{ $editingBrand->logoUrl() }}" alt="" width="44" height="44">
                            <label class="check"><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> Remove logo</label>
                        </div>
                    @endif
                    <label class="upload-box">
                        <x-icon name="image" />
                        <strong>{{ $editingBrand?->logo ? 'Replace logo' : 'Upload logo' }}</strong>
                        PNG, JPG or WebP, max 1 MB
                        <input type="file" id="brand-logo" name="logo" accept="image/png,image/jpeg,image/webp">
                    </label>
                    @error('logo')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="switch form-group">
                    <span class="switch-text"><strong>Active</strong><span>Shown in the storefront brand filter</span></span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingBrand?->is_active ?? true))>
                </label>

                <button class="btn btn-primary btn-block" type="submit">{{ $isEditing ? 'Update Brand' : 'Save Brand' }}</button>
                @if ($isEditing)
                    <a class="btn btn-ghost btn-block" href="{{ route('admin.brands.index') }}">Cancel</a>
                @endif
            </form>
        </section>
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-delete-brand" title="Delete this brand?" confirm-label="Yes, Delete" method="DELETE">
            The brand and its logo will be removed. A brand used by products cannot be deleted.
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
