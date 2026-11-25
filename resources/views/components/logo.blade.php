{{-- Brand logo: the Nischoyota mark (from the Facebook page, public/images/logo-mark.png) + wordmark. --}}
<a {{ $attributes->merge(['class' => 'logo', 'href' => route('home')]) }} aria-label="Nischoyota — নিশ্চয়তা">
    <img class="logo-mark" src="{{ asset('images/logo-mark.png') }}" alt="" width="44" height="44" decoding="async">
    <span class="logo-text">Nischoyota <small>নিশ্চয়তা</small></span>
</a>
