{{-- Guest phone code before the order is placed (F-023, D2). $maskedPhone e.g. 017•••••678 --}}
<x-layouts.app title="Confirm Your Phone" :breadcrumbs="['Checkout' => route('checkout'), 'Confirm Phone' => null]">
    <x-auth-card title="Confirm Your Phone" :subtitle="'Enter the 6-digit code we sent to '.$maskedPhone.' to place your order.'">
        <form method="POST" action="{{ route('checkout.verify.store') }}">
            @csrf
            <x-form.input name="code" id="checkout-code" label="Verification Code" placeholder="6-digit code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus required />
            <button type="submit" class="btn btn-primary btn-lg btn-block">Confirm &amp; Place Order</button>
        </form>
        <form method="POST" action="{{ route('checkout.verify.resend') }}" class="auth-resend">
            @csrf
            Didn't get it? <button type="submit" class="link-button">Send a new code</button>
        </form>

        <x-slot:footer>
            Wrong number? <a href="{{ route('checkout') }}">Back to checkout</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
