{{--
    Attributes list + "Add Attribute" form (AttributeController@index).
    $attributes  ProductAttribute collection with ->values loaded in order
--}}
<x-layouts.admin :title="__('Attributes')" active="attributes" :subtitle="__('Options such as Color or Size that products can vary by.')">
    <div class="admin-grid">
        <section class="panel panel-flush">
            @if ($attributes->isEmpty())
                <x-empty-state icon="filter" :title="__('No attributes yet')" small>
                    {{ __('Add an attribute such as Color, then add its values.') }}
                </x-empty-state>
            @else
                <div class="order-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>{{ __('Attribute') }}</th>
                                <th>{{ __('Values') }}</th>
                                <th><span class="visually-hidden">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attributes as $attribute)
                                <tr>
                                    <td>
                                        <strong>{{ $attribute->name }}</strong>
                                        <span class="table-sub">{{ trans_choice(':count value|:count values', $attribute->values->count()) }}</span>
                                    </td>
                                    <td>
                                        <div class="value-chips">
                                            @forelse ($attribute->values as $value)
                                                @include('admin.attributes.partials.value-chip')
                                            @empty
                                                <span class="text-muted">{{ __('No values yet') }}</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="icon-btn icon-btn-sm" href="{{ route('admin.attributes.edit', $attribute) }}" aria-label="Edit {{ $attribute->name }} and its values">
                                                <x-icon name="edit" class="icon-sm" />
                                            </a>
                                            <button class="icon-btn icon-btn-sm danger" type="button" data-modal="modal-delete-attribute"
                                                    data-modal-action="{{ route('admin.attributes.destroy', $attribute) }}" aria-label="Delete {{ $attribute->name }}">
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

        <section class="panel">
            <h2 class="panel-title">{{ __('Add Attribute') }}</h2>
            <form method="POST" action="{{ route('admin.attributes.store') }}">
                @csrf
                <x-form.input name="name" id="attribute-name" :label="__('Name')" :placeholder="__('e.g. Color')" required />
                <x-form.input name="slug" id="attribute-slug" :label="__('Slug')" placeholder="color" :hint="__('Used in shop filter URLs. Leave empty to generate from the name.')" />
                <button class="btn btn-primary btn-block" type="submit">{{ __('Save & Add Values') }}</button>
            </form>
        </section>
    </div>

    @push('overlays')
        <x-confirm-modal id="modal-delete-attribute" :title="__('Delete this attribute?')" confirm-label="Yes, Delete" method="DELETE">
            {{ __('All of its values are deleted too. This cannot be undone.') }}
        </x-confirm-modal>
    @endpush
</x-layouts.admin>
