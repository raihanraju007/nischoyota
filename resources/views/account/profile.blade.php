{{-- Profile (ProfileController@show): name/email, phone (changed on its own page), password. --}}
@php
    $customer = auth()->user();
@endphp

<x-layouts.account title="Profile" active="profile" :breadcrumbs="['Profile' => null]">
    <div class="panel">
        <form method="POST" action="{{ route('account.profile.update') }}">
            @csrf
            @method('PUT')
            <div class="form-row">
                <x-form.input name="name" id="pf-name" label="Full Name" :value="$customer->name" maxlength="100" required />
                <div class="form-group">
                    <label>Phone Number</label>
                    <p class="stock-readonly"><strong>{{ $customer->phone }}</strong> <a href="{{ route('account.phone') }}">Change</a></p>
                </div>
            </div>
            <x-form.input name="email" id="pf-email" type="email" label="Email" :value="$customer->email" optional />
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>

    <div class="panel">
        <h2 class="panel-title">Change Password</h2>
        <form method="POST" action="{{ route('account.password.update') }}">
            @csrf
            @method('PUT')
            <x-form.input name="current_password" id="pf-cur" type="password" label="Current Password" placeholder="Enter current password" autocomplete="current-password" required />
            <div class="form-row">
                <x-form.input name="password" id="pf-new" type="password" label="New Password" placeholder="At least 8 characters" autocomplete="new-password" required />
                <x-form.input name="password_confirmation" id="pf-new2" type="password" label="Confirm New Password" placeholder="Re-enter new password" autocomplete="new-password" required />
            </div>
            <button type="submit" class="btn btn-outline-primary">Update Password</button>
        </form>
    </div>
</x-layouts.account>
