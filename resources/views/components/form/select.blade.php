{{--
    Labelled select inside a .form-group.
    Usage: <x-form.select name="division" label="Division" :options="$divisions" selected="Dhaka" required />
    options: a list (value = label) or a map of value => label, e.g. [3 => 'Electrical'].
    placeholder: optional first option with an empty value, e.g. "None (top level)".
--}}
@props([
    'name',
    'label',
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'id' => null,
    'required' => false,
])

@php
    $id ??= $name;
    $selected = old($name, $selected);
    $selected = is_scalar($selected) ? (string) $selected : '';
    $options = array_is_list($options) ? array_combine($options, $options) : $options;
@endphp

<div class="form-group">
    <label for="{{ $id }}">
        {{ __($label) }}
        @if ($required)<span class="required">*</span>@endif
    </label>
    <select {{ $attributes->class(['select', 'is-invalid' => $errors->has($name)]) }} id="{{ $id }}" name="{{ $name }}" @required($required)>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected((string) $value === $selected)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
