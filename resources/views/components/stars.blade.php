{{-- Five-star meter. rating: 0–5. --}}
@props(['rating'])

<span class="stars" aria-hidden="true"><span class="stars-fill" style="width: {{ round($rating / 5 * 100) }}%"></span></span>
