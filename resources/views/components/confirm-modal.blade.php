{{--
    Centered confirmation dialog (delete, clear cart, cancel order…).

    Usage (UI only):
        <x-confirm-modal id="modal-confirm-clear" title="Clear your cart?" confirm-label="Yes, Clear Cart" toast="Cart cleared">
            This will remove all items from your cart.
        </x-confirm-modal>

    Usage (submits a form): pass method; each trigger sets the form action with data-modal-action.
        <button data-modal="modal-delete-category" data-modal-action="{{ route('admin.categories.destroy', $category) }}">…</button>
        <x-confirm-modal id="modal-delete-category" title="Delete this category?" method="DELETE">…</x-confirm-modal>
--}}
@props([
    'id',
    'title',
    'icon' => 'trash',
    'confirmLabel' => 'Delete',
    'cancelLabel' => 'Cancel',
    'toast' => null,
    'method' => null,
    'action' => '',
])

<x-modal :id="$id" centered>
    <div class="confirm-icon">
        <x-icon :name="$icon" class="icon" />
    </div>
    <h2 id="{{ $id }}-title">{{ $title }}</h2>
    <p class="mb-0">{{ $slot }}</p>

    <x-slot:footer>
        <button class="btn btn-ghost" type="button" data-modal-close>{{ $cancelLabel }}</button>
        @if ($method)
            <form method="POST" action="{{ $action }}" data-confirm-form>
                @csrf
                @method($method)
                <button class="btn btn-danger" type="submit">{{ $confirmLabel }}</button>
            </form>
        @else
            <button class="btn btn-danger" type="button" data-modal-close @if ($toast) data-toast="{{ $toast }}" data-toast-type="info" @endif>{{ $confirmLabel }}</button>
        @endif
    </x-slot:footer>
</x-modal>
