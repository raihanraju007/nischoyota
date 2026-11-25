{{--
    Brands: searchable list + add/edit form (BrandController@index / @edit).
    $brands        LengthAwarePaginator<Brand>
    $search        Current search term or null
    $editingBrand  Brand being edited, or null for "Add Brand"
--}}
@php
    $isEditing = $editingBrand !== null;
@endphp

<x-layouts.admin :title="__('Brands')" active="brands" :subtitle="__('Brands customers can filter products by.')">
    <div class="admin-grid">
        <section class="panel panel-flush">
            <form class="admin-toolbar" method="GET" action="{{ route('admin.brands.index') }}" role="search">
                <div class="admin-search">
                    <x-icon name="search" class="icon-sm" />
                    <label for="brand-search" class="visually-hidden">{{ __('Search brands') }}</label>
                    <input class="input" type="search" id="brand-search" name="search" value="{{ $search }}" placeholder="{{ __('Brand name…') }}">
                </div>
                <div class="toolbar-end">
                    <button class="btn btn-outline btn-sm" type="submit"><x-icon name="search" class="icon-sm" /> {{ __('Search') }}</button>
                </div>
            </form>

            @if ($brands->isEmpty())
                @if ($search)
                    <x-empty-state icon="search-off" title="No brands match “{{ $search }}”" small>
                        <a href="{{ route('admin.brands.index') }}">{{ __('Clear the search') }}</a>
                    </x-empty-state>
                @else
                    <x-empty-state icon="shield" :title="__('No brands yet')" small>
                        {{ __('Add your first brand with the form.') }}
                    </x-empty-state>
                @endif
            @else
                <div class="order-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>{{ __('Brand') }}</th>
                                <th class="num">{{ __('Products') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
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
                    <span>{{ __('Showing :first–:last of :total :products', ['first' => \App\Support\Numerals::localize($brands->firstItem()), 'last' => \App\Support\Numerals::localize($brands->lastItem()), 'total' => \App\Support\Numerals::localize($brands->total()), 'products' => __('brands')]) }}</span>
                    {{ $brands->links() }}
                </div>
            @endif
        </section>

        <section class="panel">
            <h2 class="panel-title">{{ $isEditing ? __('Edit Brand') : __('Add Brand') }}</h2>
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $isEditing ? route('admin.brands.update', $editingBrand) : route('admin.brands.store') }}">
                @csrf
                @if ($isEditing)
                    @method('PUT')
                @endif

                <x-form.input name="name" id="brand-name" :label="__('Name')" :placeholder="__('e.g. Walton')" :value="$editingBrand?->name" required />
                <x-form.input name="name_bn" id="brand-name-bn" :label="__('Name (বাংলা)')" placeholder="যেমন ওয়ালটন" :value="$editingBrand?->name_bn" optional />
                <x-form.input name="slug" id="brand-slug" :label="__('Slug')" placeholder="walton" :value="$editingBrand?->slug" hint="Used in the URL. Leave empty to generate from the name." />

                <div class="form-group">
                    <label for="brand-logo">{{ __('Logo') }}</label>
                    @if ($editingBrand?->logo)
                        <div class="table-product form-current-image">
                            <img src="{{ $editingBrand->logoUrl() }}" alt="" width="44" height="44">
                            <label class="check"><input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))> {{ __('Remove logo') }}</label>
                        </div>
                    @endif
                    <label class="upload-box">
                        <x-icon name="image" />
                        <strong>{{ $editingBrand?->logo ? __('Replace logo') : __('Upload logo') }}</strong>
                        {{ __('PNG, JPG or WebP, max 1 MB') }}
                        <input type="file" id="brand-logo" name="logo" accept="image/png,image/jpeg,image/webp">
                    </label>
                    @error('logo')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <label class="switch form-group">
                    <span class="switch-text"><strong>{{ __('Active') }}</strong><span>{{ __('Shown in the storefront brand filter') }}</span></span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingBrand?->is_active ?? true))>
                </label>

                <button class="btn btn-primary btn-block" type="submit">{{ $isEditing ? __('Update Brand') : __('Save Brand') }}</button>
                @if ($isEditing)
                    <a class="btn btn-ghost btn-block" href="{{ route('admin.brands.index') }}">{{ __('Cancel') }}</a>
                @endif
            </form>
        </section>
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-delete-brand" :title="__('Delete this brand?')" confirm-label="Yes, Delete" method="DELETE">
            {{ __('The brand and its logo will be removed. A brand used by products cannot be deleted.') }}
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
