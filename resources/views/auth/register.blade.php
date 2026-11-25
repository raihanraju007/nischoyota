<x-layouts.app :title="__('Create Account')" :breadcrumbs="['Register' => null]">
    <x-auth-card :title="__('Create Account')" :subtitle="__('Join Nischoyota and start shopping')">
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <x-form.input name="name" id="reg-name" :label="__('Full Name')" :placeholder="__('e.g. Rahim Uddin')" autocomplete="name" maxlength="100" required />
            <x-form.input name="phone" id="reg-phone" type="tel" :label="__('Phone Number')" placeholder="01712-345678" autocomplete="tel" :hint="__('We\'ll send a code to verify it, and use it for delivery updates.')" required />
            <x-form.input name="email" id="reg-email" type="email" :label="__('Email')" :placeholder="__('you@example.com')" autocomplete="email" optional />
            <x-form.input name="password" id="reg-pass" type="password" :label="__('Password')" :placeholder="__('At least 8 characters')" autocomplete="new-password" required />
            <x-form.input name="password_confirmation" id="reg-pass2" type="password" :label="__('Confirm Password')" :placeholder="__('Re-enter your password')" autocomplete="new-password" required />
            <div class="form-group">
                <label class="check"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> {{ __('I agree to the') }} <a href="{{ route('terms') }}">{{ __('Terms & Conditions') }}</a> {{ __('and') }} <a href="{{ route('privacy') }}">{{ __('Privacy Policy') }}</a></label>
                @error('terms')<p class="form-error">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Create Account') }}</button>
        </form>

        <x-slot:footer>
            {{ __('Already have an account?') }} <a href="{{ route('login') }}">{{ __('Login') }}</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
