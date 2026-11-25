{{-- One attribute value as a chip, with its swatch colour when set. $value --}}
<span class="badge badge-muted value-chip">
    @if ($value->color_hex)
        <span class="swatch swatch-sm" style="background: {{ $value->color_hex }}" aria-hidden="true"></span>
    @endif
    {{ $value->value }}
</span>
