{{--
    Formatted money amount.
    Props:
        amount  Amount in paisa (int), e.g. 485000 → "৳ 4,850".
--}}
@props(['amount'])

{{ \App\Support\Money::format($amount) }}
