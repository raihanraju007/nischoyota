{{-- Phone verification code step (PhoneVerificationController@show). $maskedPhone e.g. 017•••••678 --}}
<x-layouts.app title="Verify Phone" :breadcrumbs="['Verify Phone' => null]">
    <x-auth-card title="Verify Your Phone" :subtitle="'Enter the 6-digit code we sent to '.$maskedPhone.'.'">
        <form method="POST" action="{{ route('phone.verify.store') }}">
            @csrf
            <x-form.input name="code" id="verify-code" label="Verification Code" placeholder="6-digit code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus required />
            <button type="submit" class="btn btn-primary btn-lg btn-block">Verify &amp; Continue</button>
        </form>
        <form method="POST" action="{{ route('phone.verify.resend') }}" class="auth-resend">
            @csrf
            Didn't get it? <button type="submit" class="link-button">Send a new code</button>
        </form>

        <x-slot:footer>
            Wrong number? <a href="{{ route('register') }}">Register again</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
