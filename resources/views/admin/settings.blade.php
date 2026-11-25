{{--
    Settings (SettingsController@show).
    $settings  StoreSettings (current values or defaults)
    Store panels are admin-only (manage-store, ADR-012); staff see Change Password only.
--}}
@use('App\Support\Money')

<x-layouts.admin title="Settings" active="settings" subtitle="Store details, shop rules and your password.">
    <div class="admin-grid-even">
        @can('manage-store')
            <form method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')

                <section class="panel">
                    <h2 class="panel-title">Store Information</h2>
                    <p class="form-hint">Shown to customers in the header, footer and contact page.</p>
                    <x-form.input name="store_name" label="Store Name" :value="$settings->storeName" required />
                    <div class="form-row">
                        <x-form.input name="support_phone" type="tel" label="Support Phone" :value="$settings->supportPhone" required />
                        <x-form.input name="support_email" type="email" label="Support Email" :value="$settings->supportEmail" required />
                    </div>
                    <x-form.textarea name="store_address" label="Address" rows="2" :value="$settings->storeAddress" />
                    <x-form.input name="support_hours" label="Support Hours" placeholder="Everyday, 9:00 AM – 10:00 PM" :value="$settings->supportHours" optional />
                </section>

                <section class="panel">
                    <h2 class="panel-title">Shop Rules</h2>
                    @include('admin.products.partials.money-input', [
                        'name' => 'free_delivery_threshold',
                        'label' => 'Free Delivery From',
                        'value' => $settings->freeDeliveryThreshold === null ? null : Money::toTakaInput($settings->freeDeliveryThreshold),
                        'hint' => 'Orders of this amount or more ship free. Leave empty for no free delivery.',
                    ])
                    <x-form.input name="low_stock_threshold" type="number" min="0" max="1000" label="Low Stock At" :value="$settings->lowStockThreshold" required
                                  hint="Stock at or below this number is marked as low in Inventory." />
                </section>

                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Save Settings</button>
                </div>
            </form>
        @endcan

        <div>
            @can('manage-store')
                <section class="panel">
                    <div class="panel-head">
                        <h2>Delivery Zones</h2>
                        <a class="btn btn-outline btn-sm" href="{{ route('admin.delivery-zones.index') }}">Manage</a>
                    </div>
                    <p class="mb-0">Delivery charges, delivery times and which area belongs to which zone are managed on the Delivery page.</p>
                </section>

                <section class="panel">
                    <h2 class="panel-title">Payment Methods</h2>
                    <ul class="payment-method-list">
                        <li><strong>Cash on Delivery</strong> <x-admin.status status="active" /></li>
                        <li><strong>bKash</strong> <span class="status status-muted">Coming after launch</span></li>
                        <li><strong>Nagad</strong> <span class="status status-muted">Coming after launch</span></li>
                    </ul>
                </section>
            @endcan

            <section class="panel">
                <h2 class="panel-title">Change Password</h2>
                <form method="POST" action="{{ route('admin.password.update') }}">
                    @csrf
                    @method('PUT')
                    <x-form.input name="current_password" type="password" label="Current Password" autocomplete="current-password" required />
                    <div class="form-row">
                        <x-form.input name="password" type="password" label="New Password" autocomplete="new-password" required />
                        <x-form.input name="password_confirmation" type="password" label="Confirm Password" autocomplete="new-password" required />
                    </div>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Update Password</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-layouts.admin>
