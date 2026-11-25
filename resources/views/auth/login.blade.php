<x-layouts.app :title="__('Login')" :breadcrumbs="['Login' => null]">
    <x-auth-card :title="__('Welcome Back')" :subtitle="__('Login to your Nischoyota account')">
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <x-form.input name="login" id="login-id" :label="__('Phone Number or Email')" :placeholder="__('01712-345678 or you@example.com')" autocomplete="username" autofocus required />
            <x-form.input name="password" id="login-pass" type="password" :label="__('Password')" :placeholder="__('Enter your password')" autocomplete="current-password" required />
            <div class="auth-row">
                <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> {{ __('Remember me') }}</label>
                <a href="{{ route('password.request') }}">{{ __('Forgot Password?') }}</a>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">{{ __('Login') }}</button>
        </form>

        <x-slot:footer>
            {{ __('New to Nischoyota?') }} <a href="{{ route('register') }}">{{ __('Create an account') }}</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
