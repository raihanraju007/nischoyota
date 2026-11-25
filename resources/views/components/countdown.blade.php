{{-- Deal countdown; storefront.js ticks it down from `hours`. --}}
@props(['hours' => 14])

<div class="countdown" data-countdown-hours="{{ $hours }}" aria-label="Deal ends in">
    <span class="countdown-label">Ends in</span>
    <span class="cd-box"><span class="cd-num" data-u="h">{{ sprintf('%02d', $hours - 1) }}</span><span class="cd-unit">Hrs</span></span>
    <span class="cd-sep">:</span>
    <span class="cd-box"><span class="cd-num" data-u="m">59</span><span class="cd-unit">Min</span></span>
    <span class="cd-sep">:</span>
    <span class="cd-box"><span class="cd-num" data-u="s">59</span><span class="cd-unit">Sec</span></span>
</div>
