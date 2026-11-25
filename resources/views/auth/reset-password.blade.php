{{-- Reset step (PasswordResetController@edit). $maskedRecipient e.g. 017•••••678 or ra•••@example.com --}}
<x-layouts.app title="Reset Password" :breadcrumbs="['Reset Password' => null]">
    <x-auth-card title="Reset Password" :subtitle="'Enter the code sent to '.$maskedRecipient.' and choose a new password.'">
        <form method="POST" action="{{ route('password.store') }}">
            @csrf
            <x-form.input name="code" id="rp-code" label="Reset Code" placeholder="6-digit code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" autofocus required />
            <x-form.input name="password" id="rp-pass" type="password" label="New Password" placeholder="At least 8 characters" autocomplete="new-password" required />
            <x-form.input name="password_confirmation" id="rp-pass2" type="password" label="Confirm New Password" placeholder="Re-enter new password" autocomplete="new-password" required />
            <button type="submit" class="btn btn-primary btn-lg btn-block">Reset Password</button>
        </form>
        <form method="POST" action="{{ route('password.resend') }}" class="auth-resend">
            @csrf
            Didn't get it? <button type="submit" class="link-button">Send a new code</button>
        </form>

        <x-slot:footer>
            Remembered it? <a href="{{ route('login') }}">Back to Login</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
