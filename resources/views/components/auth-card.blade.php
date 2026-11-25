{{-- Centered auth card (login, register, password reset). Slots: default (form), footer (alt link). --}}
@props([
    'title',
    'subtitle',
    'footer',
])

<div class="auth-wrap">
    <div class="auth-card">
        <img class="auth-logo" src="{{ asset('images/logo-full.png') }}" alt="Nischoyota" width="140" height="140" decoding="async">
        <h1>{{ $title }}</h1>
        <p class="auth-sub">{{ $subtitle }}</p>
        {{ $slot }}
        <p class="auth-alt">{{ $footer }}</p>
    </div>
    <p class="auth-back"><a href="{{ route('home') }}">← Back to Home</a></p>
</div>
