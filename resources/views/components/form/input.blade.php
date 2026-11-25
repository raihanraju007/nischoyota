{{--
    Labelled text input inside a .form-group, with hint and validation error support.

    Usage: <x-form.input name="phone" label="Phone Number" type="tel" required hint="…" placeholder="01712-345678" />
    Extra attributes (placeholder, autocomplete, inputmode…) are passed to the <input>.
    An old value that is not text (a field tampered into an array) is shown empty instead of breaking the page.
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'id' => null,
    'value' => null,
    'required' => false,
    'optional' => false,
    'hint' => null,
])

@php
    $id ??= $name;
    $currentValue = old($name, $value);
    $currentValue = is_scalar($currentValue) ? $currentValue : null;
@endphp

<div class="form-group">
    <label for="{{ $id }}">
        {{ __($label) }}
        @if ($required)<span class="required">*</span>@endif
        @if ($optional)<span class="text-muted">{{ __('(optional)') }}</span>@endif
    </label>
    <input
        {{ $attributes->class(['input', 'is-invalid' => $errors->has($name)]) }}
        type="{{ $type }}"
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($type !== 'password') value="{{ $currentValue }}" @endif
        @required($required)
        @error($name) aria-describedby="{{ $id }}-error" @enderror
    >
    @if ($hint)
        <p class="form-hint mb-0">{{ __($hint) }}</p>
    @endif
    @error($name)
        <p class="form-error" id="{{ $id }}-error">{{ $message }}</p>
    @enderror
</div>
