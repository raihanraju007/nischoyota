<x-layouts.app title="Forgot Password" :breadcrumbs="['Forgot Password' => null]">
    <x-auth-card title="Forgot Password?" subtitle="Enter your phone number or email and we'll send you a reset code.">
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <x-form.input name="login" id="fp-id" label="Phone Number or Email" placeholder="01712-345678 or you@example.com" autocomplete="username" autofocus required />
            <button type="submit" class="btn btn-primary btn-lg btn-block">Send Reset Code</button>
        </form>

        <x-slot:footer>
            Remembered it? <a href="{{ route('login') }}">Back to Login</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
