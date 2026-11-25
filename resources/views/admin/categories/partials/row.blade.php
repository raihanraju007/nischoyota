{{-- One category row. $category, $isChild (indents subcategories under their parent). --}}
<tr @class(['table-row-child' => $isChild])>
    <td>
        <div class="table-product">
            @if ($category->image)
                <img src="{{ $category->imageUrl() }}" alt="" width="44" height="44">
            @else
                <span class="table-thumb-empty"><x-icon name="image" class="icon-sm" /></span>
            @endif
            <div>
                <strong>{{ $isChild ? '— ' : '' }}{{ $category->name }}</strong>
                <span class="table-sub">/{{ $category->slug }}</span>
            </div>
        </div>
    </td>
    <td class="num">{{ $category->products_count }}</td>
    <td><x-admin.status :status="$category->is_active ? 'active' : 'hidden'" /></td>
    <td>
        <div class="table-actions">
            <a class="icon-btn icon-btn-sm" href="{{ route('admin.categories.edit', $category) }}" aria-label="Edit {{ $category->name }}">
                <x-icon name="edit" class="icon-sm" />
            </a>
            <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-category"
                    data-modal-action="{{ route('admin.categories.destroy', $category) }}" aria-label="Delete {{ $category->name }}">
                <x-icon name="trash" class="icon-sm" />
            </button>
        </div>
    </td>
</tr>
