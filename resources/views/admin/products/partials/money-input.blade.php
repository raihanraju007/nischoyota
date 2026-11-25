{{-- Taka amount input with the ৳ prefix. $name, $label, $value (taka string or null), $required?, $hint? --}}
@php
    $isRequired = $required ?? false;
@endphp
<div class="form-group">
    <label for="{{ $name }}">
        {{ __($label) }}
        @if ($isRequired)<span class="required">*</span>@else<span class="text-muted">{{ __('(optional)') }}</span>@endif
    </label>
    <div class="input-prefix">
        <span>৳</span>
        <input @class(['input', 'is-invalid' => $errors->has($name)]) type="number" id="{{ $name }}" name="{{ $name }}"
               min="0" step="0.01" placeholder="0" value="{{ old($name, $value) }}" @required($isRequired)
               @error($name) aria-describedby="{{ $name }}-error" @enderror>
    </div>
    @isset($hint)<p class="form-hint mb-0">{{ __($hint) }}</p>@endisset
    @error($name)<p class="form-error" id="{{ $name }}-error">{{ $message }}</p>@enderror
</div>
