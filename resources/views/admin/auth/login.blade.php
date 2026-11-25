<x-layouts.admin-auth title="Admin Login">
    <div>
        <x-auth-card title="Admin Login" subtitle="Sign in to manage your store">
            <form method="POST" action="{{ route('admin.login.store') }}">
                @csrf
                <x-form.input name="login" id="admin-login" label="Phone or Email" placeholder="01XXXXXXXXX or admin@nischoyota.com" autocomplete="username" autofocus required />
                <x-form.input name="password" id="admin-password" type="password" label="Password" placeholder="Enter your password" autocomplete="current-password" required />
                <div class="auth-row">
                    <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    <x-icon name="lock" class="icon-sm" /> Sign In
                </button>
            </form>

            <x-slot:footer>
                Only staff accounts can access the admin panel.
            </x-slot:footer>
        </x-auth-card>
    </div>
</x-layouts.admin-auth>
