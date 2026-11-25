<x-layouts.app title="Login" :breadcrumbs="['Login' => null]">
    <x-auth-card title="Welcome Back" subtitle="Login to your Nischoyota account">
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <x-form.input name="login" id="login-id" label="Phone Number or Email" placeholder="01712-345678 or you@example.com" autocomplete="username" autofocus required />
            <x-form.input name="password" id="login-pass" type="password" label="Password" placeholder="Enter your password" autocomplete="current-password" required />
            <div class="auth-row">
                <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me</label>
                <a href="{{ route('password.request') }}">Forgot Password?</a>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Login</button>
        </form>

        <x-slot:footer>
            New to Nischoyota? <a href="{{ route('register') }}">Create an account</a>
        </x-slot:footer>
    </x-auth-card>
</x-layouts.app>
