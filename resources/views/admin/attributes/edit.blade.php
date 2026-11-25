{{--
    One attribute: its values (table + Add/Edit Value form) and the attribute's own name/slug form.
    $attribute     ProductAttribute with ->values loaded in order
    $editingValue  AttributeValue being edited (?value={id}), or null for "Add Value"
--}}
@php
    $isEditingValue = $editingValue !== null;
@endphp

<x-layouts.admin :title="$attribute->name" active="attributes" subtitle="Values customers can choose for this option."
                 :back-url="route('admin.attributes.index')" back-label="All attributes">
    <div class="admin-grid">
        <section class="panel panel-flush">
            @if ($attribute->values->isEmpty())
                <x-empty-state icon="filter" title="No values yet" small>
                    Add values such as White or XL with the form.
                </x-empty-state>
            @else
                <div class="order-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Value</th>
                                <th>Swatch</th>
                                <th class="num">Sort</th>
                                <th><span class="visually-hidden">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attribute->values as $value)
                                <tr>
                                    <td><strong>{{ $value->value }}</strong></td>
                                    <td>
                                        @if ($value->color_hex)
                                            <span class="value-chip">
                                                <span class="swatch swatch-sm" style="background: {{ $value->color_hex }}" aria-hidden="true"></span>
                                                <span class="table-sub">{{ $value->color_hex }}</span>
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="num">{{ $value->sort_order }}</td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="icon-btn icon-btn-sm" href="{{ route('admin.attributes.edit', [$attribute, 'value' => $value->id]) }}" aria-label="Edit {{ $value->value }}">
                                                <x-icon name="edit" class="icon-sm" />
                                            </a>
                                            <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-value"
                                                    data-modal-action="{{ route('admin.attributes.values.destroy', [$attribute, $value]) }}" aria-label="Delete {{ $value->value }}">
                                                <x-icon name="trash" class="icon-sm" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <div>
            <section class="panel">
                <h2 class="panel-title">{{ $isEditingValue ? 'Edit Value' : 'Add Value' }}</h2>
                <form method="POST"
                      action="{{ $isEditingValue ? route('admin.attributes.values.update', [$attribute, $editingValue]) : route('admin.attributes.values.store', $attribute) }}">
                    @csrf
                    @if ($isEditingValue)
                        @method('PUT')
                    @endif
                    <x-form.input name="value" id="value-value" label="Value" placeholder="e.g. White" :value="$editingValue?->value" required />
                    <x-form.input name="color_hex" id="value-color" label="Swatch Colour" placeholder="#FFFFFF" maxlength="7" :value="$editingValue?->color_hex" optional hint="Only for colours. Hex format, e.g. #1160A8." />
                    <x-form.input name="sort_order" id="value-sort" type="number" min="0" label="Sort Order" :value="$editingValue?->sort_order ?? $attribute->values->count()" hint="Lower numbers come first." />
                    <button class="btn btn-primary btn-block" type="submit">{{ $isEditingValue ? 'Update Value' : 'Add Value' }}</button>
                    @if ($isEditingValue)
                        <a class="btn btn-ghost btn-block" href="{{ route('admin.attributes.edit', $attribute) }}">Cancel</a>
                    @endif
                </form>
            </section>

            <section class="panel">
                <h2 class="panel-title">Attribute</h2>
                <form method="POST" action="{{ route('admin.attributes.update', $attribute) }}">
                    @csrf
                    @method('PUT')
                    <x-form.input name="name" id="attribute-name" label="Name" :value="$attribute->name" required />
                    <x-form.input name="slug" id="attribute-slug" label="Slug" :value="$attribute->slug" />
                    <button class="btn btn-outline btn-block" type="submit">Update Attribute</button>
                </form>
            </section>
        </div>
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-delete-value" title="Delete this value?" confirm-label="Yes, Delete" method="DELETE">
            The value is removed from this attribute.
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
