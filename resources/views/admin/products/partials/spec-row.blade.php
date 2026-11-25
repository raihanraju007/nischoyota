{{-- One specification row. $index (number or "__INDEX__" in the repeater template), $row [label, value] --}}
<div class="spec-row" data-repeater-row>
    <label class="visually-hidden" for="spec-label-{{ $index }}">{{ __('Label') }}</label>
    <input class="input @error("specifications.$index.label") is-invalid @enderror" id="spec-label-{{ $index }}"
           name="specifications[{{ $index }}][label]" value="{{ $row['label'] ?? '' }}" placeholder="{{ __('Label, e.g. Motor') }}" maxlength="60">
    <label class="visually-hidden" for="spec-value-{{ $index }}">{{ __('Value') }}</label>
    <input class="input @error("specifications.$index.value") is-invalid @enderror" id="spec-value-{{ $index }}"
           name="specifications[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}" placeholder="{{ __('Value, e.g. 100% Copper') }}" maxlength="255">
    <button class="icon-btn icon-btn-sm danger" type="button" data-repeater-remove aria-label="{{ __('Remove specification') }}">
        <x-icon name="trash" class="icon-sm" />
    </button>
    @error("specifications.$index.label")<p class="form-error">{{ $message }}</p>@enderror
    @error("specifications.$index.value")<p class="form-error">{{ $message }}</p>@enderror
</div>
