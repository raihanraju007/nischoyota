<x-layouts.app title="Create Account" :breadcrumbs="['Register' => null]">
    <x-auth-card title="Create Account" subtitle="Join Nischoyota and start shopping">
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <x-form.input name="name" id="reg-name" label="Full Name" placeholder="e.g. Rahim Uddin" autocomplete="name" maxlength="100" required />
            <x-form.input name="phone" id="reg-phone" type="tel" label="Phone Number" placeholder="01712-345678" autocomplete="tel" hint="We'll send a code to verify it, and use it for delivery updates." required />
            <x-form.input name="email" id="reg-email" type="email" label="Email" placeholder="you@example.com" autocomplete="email" optional />
            <x-form.input name="password" id="reg-pass" type="password" label="Password" placeholder="At least 8 characters" autocomplete="new-password" required />
            <x-form.input name="password_confirmation" id="reg-pass2" type="password" label="Confirm Password" placeholder="Re-enter your password" autocomplete="new-password" required />
            <div class="form-group">
                <label class="check"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> I agree to the <a href="{{ route('terms') }}">Terms &amp; Conditions</a> and <a href="{{ route('privacy') }}">Privacy Policy</a></label>
                @error('terms')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Create Account</button>
        </form>

        <x-slot:footer>
            Already have an account? <a href="{{ route('login') }}">Login</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
