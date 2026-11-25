{{--
    Contact page (ContactController@show).
    $settings  StoreSettings (contact channels)
    $subjects  ContactSubject value => label
    $customer  Signed-in user or null (pre-fills the form)
--}}
@php
    $contactChannels = array_filter([
        ['icon' => 'phone', 'label' => 'Phone', 'value' => $settings->supportPhone],
        ['icon' => 'mail', 'label' => 'Email', 'value' => $settings->supportEmail],
        ['icon' => 'map-pin', 'label' => 'Address', 'value' => $settings->storeAddress],
        ['icon' => 'clock', 'label' => 'Support Hours', 'value' => $settings->supportHours],
    ], fn (array $channel): bool => filled($channel['value']));
@endphp

<x-layouts.app :title="__('Contact Us')" :breadcrumbs="['Contact Us' => null]">
    <x-page-hero :title="__('Contact Us')">{{ __('We\'re here to help — reach us any way you like.') }}</x-page-hero>

    <div class="container contact-layout">
        <div class="contact-info-card">
            <ul>
                @foreach ($contactChannels as $channel)
                    <li>
                        <span class="trust-icon"><x-icon :name="$channel['icon']" class="icon" /></span>
                        <div><strong>{{ $channel['label'] }}</strong><span>{{ $channel['value'] }}</span></div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="checkout-panel">
            <h2>{{ __('Send us a message') }}</h2>
            <form method="POST" action="{{ route('contact.store') }}">
                @csrf
                <div class="visually-hidden" aria-hidden="true">
                    <label for="ct-website">{{ __('Leave this field empty') }}</label>
                    <input type="text" id="ct-website" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="form-row">
                    <x-form.input name="name" id="ct-name" :label="__('Full Name')" :placeholder="__('e.g. Rahim Uddin')" :value="$customer?->name" required maxlength="100" autocomplete="name" />
                    <x-form.input name="phone" id="ct-phone" type="tel" :label="__('Phone Number')" placeholder="01712-345678" :value="$customer?->phone" required autocomplete="tel" />
                </div>
                <x-form.input name="email" id="ct-email" type="email" :label="__('Email')" :placeholder="__('you@example.com')" :value="$customer?->email" optional autocomplete="email" />
                <x-form.select name="subject" id="ct-subject" :label="__('Subject')" :options="$subjects" required />
                <x-form.textarea name="message" id="ct-msg" :label="__('Message')" :placeholder="__('How can we help you?')" required minlength="10" maxlength="2000" />
                <button type="submit" class="btn btn-primary">{{ __('Send Message') }}</button>
            </form>
        </div>
    </div>
</x-layouts.app>
