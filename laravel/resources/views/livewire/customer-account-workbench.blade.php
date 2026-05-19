<section aria-label="Customer account workbench" class="customer-card">
    <header class="customer-section-header">
        <p>Customer account</p>
        <h2>{{ $feature->label }}</h2>
        <span>{{ implode(', ', $featureIds) }}</span>
    </header>

    <div class="customer-controls">
        <label>
            Store scope
            <select wire:model.live="storeId">
                <option value="">All stores</option>
                <option value="9001">Store 9001</option>
                <option value="9002">Store 9002</option>
            </select>
        </label>

        <label>
            Store view
            <select wire:model.live="storeView">
                <option value="">All store views</option>
                <option value="default">Default</option>
                <option value="de">DE</option>
            </select>
        </label>

        <label>
            Role
            <select wire:model.live="role">
                <option value="customer">Customer</option>
                <option value="read-only">Read only</option>
                <option value="denied">Denied</option>
            </select>
        </label>

        <label>
            Customer
            <select wire:model.live="customerId">
                <option value="">All customers</option>
                @foreach ($customerOptions as $customer)
                    @php($customerPayload = $customer['payload'])
                    <option value="{{ $customerPayload['customer_id'] }}">{{ $customerPayload['full_name'] }}</option>
                @endforeach
            </select>
        </label>

        <label>
            Address type
            <select wire:model.live="addressRole">
                <option value="">All addresses</option>
                <option value="default_billing">Default billing</option>
                <option value="default_shipping">Default shipping</option>
                <option value="additional">Additional</option>
            </select>
        </label>
    </div>

    <div class="customer-toolbar">
        <div aria-label="Customer summary" class="customer-summary">
            <span>{{ count($customers) }} customer rows</span>
            <span>{{ count($addresses) }} address rows</span>
            <span>{{ $snapshotStoreView ?? 'all stores' }}</span>
        </div>
        <div role="toolbar" aria-label="Customer account sections">
            <button type="button" wire:click="setSection('dashboard')" @class(['is-active' => $section === 'dashboard'])>Dashboard</button>
            <button type="button" wire:click="setSection('addresses')" @class(['is-active' => $section === 'addresses'])>Address Book</button>
            <button type="button" wire:click="setSection('orders')" @class(['is-active' => $section === 'orders'])>Orders</button>
            <button type="button" wire:click="setSection('security')" @class(['is-active' => $section === 'security'])>Security</button>
        </div>
    </div>

    @if ($activeScope !== [])
        <div class="customer-active-scope" aria-label="Active scope">
            @foreach ($activeScope as $name => $value)
                <span>{{ \Illuminate\Support\Str::headline($name) }}: {{ $value }}</span>
            @endforeach
            <button type="button" wire:click="clearScope">Clear scope</button>
        </div>
    @endif

    @if (! $canViewAccount)
        <div class="customer-state customer-state-danger" role="alert">
            Permission denied for customer role `{{ $role }}`.
        </div>
    @elseif ($selectedCustomer === [])
        <div class="customer-state" role="status">
            There are no customer account facts for this scope.
        </div>
    @elseif ($section === 'dashboard')
        <div class="customer-dashboard">
            <article>
                <span>Profile</span>
                <h3>{{ $selectedCustomer['full_name'] ?? 'Customer' }}</h3>
                <dl>
                    <dt>Email</dt>
                    <dd>{{ $selectedCustomer['email'] ?? 'Unavailable' }}</dd>
                    <dt>Group</dt>
                    <dd>{{ $selectedCustomer['group'] ?? 'General' }}</dd>
                    <dt>Status</dt>
                    <dd>{{ (bool) ($selectedCustomer['is_active'] ?? false) ? 'Active' : 'Disabled' }}</dd>
                    <dt>Website</dt>
                    <dd>{{ $selectedCustomer['store_view'] ?? ($storeView === 'de' ? 'German Store View' : 'Default Store View') }}</dd>
                </dl>
            </article>

            <article>
                <span>Commerce activity</span>
                <div class="customer-metrics">
                    <strong>{{ (int) ($selectedCustomer['orders_count'] ?? 0) }}</strong>
                    <small>Orders</small>
                    <strong>{{ \Illuminate\Support\Number::currency((float) ($selectedCustomer['lifetime_value'] ?? 0), in: 'USD', locale: 'en_US') }}</strong>
                    <small>Lifetime value</small>
                    <strong>{{ $selectedCustomer['customer_id'] ?? 'n/a' }}</strong>
                    <small>Customer ID</small>
                </div>
            </article>

            <article>
                <span>Preferences</span>
                <dl>
                    <dt>Newsletter</dt>
                    <dd>{{ (bool) ($selectedCustomer['newsletter_subscribed'] ?? false) ? 'Subscribed' : 'Not subscribed' }}</dd>
                    <dt>Last login</dt>
                    <dd>{{ $selectedCustomer['last_login_at'] ?? 'Not recorded' }}</dd>
                    <dt>Password</dt>
                    <dd>{{ $selectedCustomer['password_state'] ?? 'Compatible hash pending upgrade' }}</dd>
                </dl>
            </article>
        </div>
    @elseif ($section === 'addresses')
        <div class="address-grid">
            @forelse ($addresses as $address)
                @php($payload = $address['payload'])
                <article>
                    <header>
                        <span>{{ \Illuminate\Support\Str::headline($payload['address_type'] ?? 'address') }}</span>
                        <h3>{{ trim(($payload['firstname'] ?? '') . ' ' . ($payload['lastname'] ?? '')) }}</h3>
                    </header>
                    <p>{{ implode(', ', $payload['street'] ?? ['Street unavailable']) }}</p>
                    <p>{{ $payload['postcode'] ?? '' }} {{ $payload['city'] ?? '' }}, {{ $payload['country_id'] ?? '' }}</p>
                    <small>{{ $payload['telephone'] ?? 'No phone' }}</small>
                    <div class="address-badges">
                        @if ((bool) ($payload['is_default_billing'] ?? false))
                            <span>Default billing</span>
                        @endif
                        @if ((bool) ($payload['is_default_shipping'] ?? false))
                            <span>Default shipping</span>
                        @endif
                        <span>{{ $payload['country_id'] ?? 'Valid' }}</span>
                    </div>
                </article>
            @empty
                <div class="customer-state" role="status">
                    There are no addresses matching this account scope.
                </div>
            @endforelse
        </div>
    @elseif ($section === 'orders')
        <div class="order-list">
            <article>
                <strong>{{ (int) ($selectedCustomer['orders_count'] ?? 0) }} orders</strong>
                <span>{{ $selectedCustomer['group'] ?? 'General' }}</span>
                <span>{{ \Illuminate\Support\Number::currency((float) ($selectedCustomer['lifetime_value'] ?? 0), in: 'USD', locale: 'en_US') }}</span>
                <small>Customer since {{ $selectedCustomer['created_at'] ?? 'unknown' }}</small>
            </article>
        </div>
    @else
        <div class="customer-security">
            <article>
                <span>Session boundary</span>
                <h3>{{ $selectedCustomer['session_boundary'] ?? 'explicit_non_sharing' }}</h3>
                <p>{{ $selectedCustomer['form_key_state'] ?? 'Magento form-key compatibility is tracked separately.' }}</p>
            </article>
            <article>
                <span>Password reset</span>
                <h3>{{ $selectedCustomer['reset_token_state'] ?? 'No active token' }}</h3>
                <p>{{ $selectedCustomer['password_state'] ?? 'Compatible hash pending upgrade' }}</p>
            </article>
            <button type="button" disabled>Save account changes</button>
        </div>
    @endif
</section>
