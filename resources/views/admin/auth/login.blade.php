<x-layouts.admin-auth :title="__('Admin Login')">
    <div>
        <x-auth-card :title="__('Admin Login')" :subtitle="__('Sign in to manage your store')">
            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf
                <x-form.input name="login" id="admin-login" :label="__('Phone or Email')" :placeholder="__('01XXXXXXXXX or admin@nischoyota.com')" autocomplete="username" autofocus required />
                <x-form.input name="password" id="admin-password" type="password" :label="__('Password')" :placeholder="__('Enter your password')" autocomplete="current-password" required />
                <div class="auth-row">
                    <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> {{ __('Remember me') }}</label>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <x-icon name="lock" class="icon-sm" /> {{ __('Sign In') }}
                </button>
            </form>

            <x-slot:footer>
                {{ __('Only staff accounts can access the admin panel.') }}
            </x-slot:footer>
        </x-auth-card>
    </div>
</x-layouts.admin-auth>
