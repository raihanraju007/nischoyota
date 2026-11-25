{{-- Labelled textarea inside a .form-group. --}}
@props([
    'name',
    'label',
    'id' => null,
    'value' => null,
    'required' => false,
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
    </label>
    <textarea {{ $attributes->class(['textarea', 'is-invalid' => $errors->has($name)]) }} id="{{ $id }}" name="{{ $name }}" @required($required)>{{ $currentValue }}</textarea>
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
