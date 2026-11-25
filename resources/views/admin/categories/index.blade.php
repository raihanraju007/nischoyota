{{--
    Categories: tree list + add/edit form (CategoryController@index / @edit).
    $categories        Top-level categories with ->children loaded
    $parentOptions     id => name of top-level categories (for the parent select)
    $editingCategory   Category being edited, or null for "Add Category"
--}}
@php
    $isEditing = $editingCategory !== null;
@endphp

<x-layouts.admin :title="__('Categories')" active="categories" :subtitle="__('Group products so customers can find them.')">
    <div class="admin-grid">
        <section class="panel panel-flush">
            @if ($categories->isEmpty())
                <x-empty-state icon="tag" :title="__('No categories yet')" small>
                    {{ __('Add your first category with the form.') }}
                </x-empty-state>
            @else
                <div class="order-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>{{ __('Category') }}</th>
                                <th class="num">{{ __('Products') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                @include('admin.categories.partials.row', ['category' => $category, 'isChild' => false])
                                @foreach ($category->children as $child)
                                    @include('admin.categories.partials.row', ['category' => $child, 'isChild' => true])
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel">
            <h2 class="panel-title">{{ $isEditing ? __('Edit Category') : __('Add Category') }}</h2>
            <form method="POST" enctype="multipart/form-data"
                  action="{{ $isEditing ? route('admin.categories.update', $editingCategory) : route('admin.categories.store') }}">
                @csrf
                @if ($isEditing)
                    @method('PUT')
                @endif

                <x-form.input name="name" id="category-name" :label="__('Name')" :placeholder="__('e.g. Electrical')" :value="$editingCategory?->name" required />
                <x-form.input name="name_bn" id="category-name-bn" :label="__('Name (বাংলা)')" placeholder="যেমন বৈদ্যুতিক" :value="$editingCategory?->name_bn" optional />
                <x-form.input name="slug" id="category-slug" :label="__('Slug')" placeholder="electrical" :value="$editingCategory?->slug" hint="Used in the URL. Leave empty to generate from the name." />
                <x-form.select name="parent_id" id="category-parent" :label="__('Parent Category')" :placeholder="__('None (top level)')" :options="$parentOptions" :selected="$editingCategory?->parent_id" />

                <div class="form-group">
                    <label for="category-image">{{ __('Image') }}</label>
                    @if ($editingCategory?->image)
                        <div class="table-product form-current-image">
                            <img src="{{ $editingCategory->imageUrl() }}" alt="" width="44" height="44">
                            <label class="check"><input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))> {{ __('Remove image') }}</label>
                        </div>
                    @endif
                    <label class="upload-box">
                        <x-icon name="image" />
                        <strong>{{ $editingCategory?->image ? __('Replace image') : __('Upload image') }}</strong>
                        {{ __('PNG, JPG or WebP, square, max 1 MB') }}
                        <input type="file" id="category-image" name="image" accept="image/png,image/jpeg,image/webp">
                    </label>
                    @error('image')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <x-form.input name="sort_order" id="category-sort" type="number" min="0" :label="__('Sort Order')" :value="$editingCategory?->sort_order ?? 0" hint="Lower numbers come first." />

                <label class="switch form-group">
                    <span class="switch-text"><strong>{{ __('Active') }}</strong><span>{{ __('Visible in the storefront menu') }}</span></span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingCategory?->is_active ?? true))>
                </label>

                <x-form.input name="meta_title" id="category-meta-title" :label="__('SEO Title')" :value="$editingCategory?->meta_title" optional />
                <x-form.textarea name="meta_description" id="category-meta-description" :label="__('SEO Description')" rows="2" :value="$editingCategory?->meta_description" />

                <button class="btn btn-primary btn-block" type="submit">{{ $isEditing ? __('Update Category') : __('Save Category') }}</button>
                @if ($isEditing)
                    <a class="btn btn-ghost btn-block" href="{{ route('admin.categories.index') }}">{{ __('Cancel') }}</a>
                @endif
            </form>
        </section>
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-delete-category" :title="__('Delete this category?')" confirm-label="Yes, Delete" method="DELETE">
            {{ __('A category that still has subcategories or products cannot be deleted. Move them first.') }}
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
