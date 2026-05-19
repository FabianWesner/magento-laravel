<?php

namespace App\Livewire;

use App\Models\User;
use App\Modernization\Domain\DomainCatalog;
use App\Modernization\Domain\DomainQueryService;
use App\Policies\Modernization\Domain\DomainPolicy;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CustomerAccountWorkbench extends Component
{
    public string $storeView = 'default';

    public string $storeId = '';

    public string $customerId = '';

    public string $addressRole = '';

    public string $section = 'dashboard';

    public string $role = 'customer';

    public function setSection(string $section): void
    {
        if (! in_array($section, ['dashboard', 'addresses', 'orders', 'security'], true)) {
            return;
        }

        $this->section = $section;
    }

    public function clearScope(): void
    {
        $this->storeView = 'default';
        $this->storeId = '';
        $this->customerId = '';
        $this->addressRole = '';
    }

    public function render(DomainCatalog $catalog, DomainQueryService $queryService, DomainPolicy $policy): View
    {
        $canViewAccount = $this->canViewAccount($policy);
        $customerSnapshot = $canViewAccount ? $queryService->snapshot('customer', $this->snapshotFilters()) : null;
        $addressSnapshot = $canViewAccount ? $queryService->snapshot('customer_address', $this->snapshotFilters()) : null;
        $allCustomers = $this->snapshotRows($customerSnapshot);
        $customers = $this->filteredCustomers($allCustomers);
        $selectedCustomer = $customers[0]['payload'] ?? [];
        $addresses = $this->filteredAddresses($this->snapshotRows($addressSnapshot), $selectedCustomer);

        return view('livewire.customer-account-workbench', [
            'feature' => $catalog->get('customer'),
            'addressFeature' => $catalog->get('customer_address'),
            'featureIds' => array_values(array_unique([
                ...$catalog->get('customer')->featureIds,
                ...$catalog->get('customer_address')->featureIds,
            ])),
            'canViewAccount' => $canViewAccount,
            'customers' => $customers,
            'customerOptions' => $allCustomers,
            'addresses' => $addresses,
            'selectedCustomer' => $selectedCustomer,
            'activeScope' => $this->activeScope(),
            'snapshotStoreView' => $customerSnapshot['store_view'] ?? null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotFilters(): array
    {
        return array_filter([
            'store_id' => $this->storeId,
            'store_view' => $this->storeView,
        ], fn (mixed $value): bool => $value !== '' && $value !== null);
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return list<array<string, mixed>>
     */
    private function snapshotRows(?array $snapshot): array
    {
        return $snapshot['payload']['rows'] ?? [];
    }

    /**
     * @param  list<array<string, mixed>>  $customers
     * @return list<array<string, mixed>>
     */
    private function filteredCustomers(array $customers): array
    {
        if ($this->customerId === '') {
            return $customers;
        }

        return collect($customers)
            ->filter(fn (array $customer): bool => (string) ($customer['payload']['customer_id'] ?? '') === $this->customerId)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $addresses
     * @param  array<string, mixed>  $selectedCustomer
     * @return list<array<string, mixed>>
     */
    private function filteredAddresses(array $addresses, array $selectedCustomer): array
    {
        $customerId = (string) ($selectedCustomer['customer_id'] ?? '');

        return collect($addresses)
            ->filter(function (array $address) use ($customerId): bool {
                $payload = $address['payload'] ?? [];

                if ($customerId !== '' && (string) ($payload['customer_id'] ?? '') !== $customerId) {
                    return false;
                }

                if ($this->addressRole === 'default_billing') {
                    return (bool) ($payload['is_default_billing'] ?? false);
                }

                if ($this->addressRole === 'default_shipping') {
                    return (bool) ($payload['is_default_shipping'] ?? false);
                }

                if ($this->addressRole === 'additional') {
                    return ! (bool) ($payload['is_default_billing'] ?? false)
                        && ! (bool) ($payload['is_default_shipping'] ?? false);
                }

                return true;
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function activeScope(): array
    {
        return array_filter([
            'store' => $this->storeView,
            'store_id' => $this->storeId,
            'customer' => $this->customerId,
            'address_role' => $this->addressRole,
        ], fn (string $value): bool => $value !== '');
    }

    private function canViewAccount(DomainPolicy $policy): bool
    {
        if (! app()->environment(['local', 'testing'])) {
            return false;
        }

        return $policy->viewDiagnostics($this->fixtureUser());
    }

    private function fixtureUser(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 904,
            'name' => "{$this->role} customer fixture",
            'email' => "{$this->role}-customer@example.test",
        ]);
        $user->setAttribute('role', $this->role);
        $user->exists = true;

        return $user;
    }
}
