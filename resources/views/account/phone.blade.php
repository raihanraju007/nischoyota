{{--
    Change phone (PhoneChangeController@show).
    $pendingPhone / $maskedPendingPhone  The new number waiting for its code, or null
--}}
<x-layouts.account title="Change Phone Number" active="profile" :breadcrumbs="['Profile' => route('account.profile'), 'Phone' => null]">
    <div class="panel">
        <p class="text-light">Current number: <strong>{{ auth()->user()->phone }}</strong>. We'll send a code to the new number to make sure it's yours.</p>
        <form method="POST" action="{{ route('account.phone.send-code') }}">
            @csrf
            <x-form.input name="phone" id="new-phone" type="tel" label="New Phone Number" placeholder="01712-345678" autocomplete="tel" :value="$pendingPhone" required />
            <button type="submit" class="btn btn-outline-primary">{{ $pendingPhone ? 'Send a new code' : 'Send Code' }}</button>
        </form>
    </div>

    @if ($pendingPhone)
        <div class="panel">
            <h2 class="panel-title">Enter the code sent to {{ $maskedPendingPhone }}</h2>
            <form method="POST" action="{{ route('account.phone.verify') }}">
                @csrf
                <x-form.input name="code" id="phone-code" label="Verification Code" placeholder="6-digit code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required />
                <button type="submit" class="btn btn-primary">Confirm New Number</button>
            </form>
        </div>
    @endif
</x-layouts.account>
