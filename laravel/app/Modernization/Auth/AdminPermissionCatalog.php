<?php

namespace App\Modernization\Auth;

use Illuminate\Support\Str;

class AdminPermissionCatalog
{
    public function __construct(
        private readonly PermissionManifest $permissionManifest,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(): array
    {
        return [
            ...$this->roleRows(),
            ...$this->resourceRows(),
            ...$this->apiRows(),
        ];
    }

    /**
     * @param  array<string, string>  $filters
     * @return list<array<string, mixed>>
     */
    public function filtered(array $filters): array
    {
        $query = Str::lower(trim(Str::substr($filters['query'] ?? '', 0, 128)));
        $status = $filters['status'] ?? '';
        $kind = $filters['kind'] ?? '';
        $featureId = $filters['feature_id'] ?? '';
        $role = $filters['role'] ?? '';

        return collect($this->rows())
            ->filter(function (array $row) use ($query, $status, $kind, $featureId, $role): bool {
                if ($status !== '' && $row['status'] !== $status && ($status !== 'attention' || ! $row['needs_attention'])) {
                    return false;
                }

                if ($kind !== '' && $row['kind'] !== $kind) {
                    return false;
                }

                if ($featureId !== '' && ! in_array($featureId, $row['feature_ids'], true)) {
                    return false;
                }

                if ($role !== '' && ! in_array($role, $row['roles'], true)) {
                    return false;
                }

                if ($query === '') {
                    return true;
                }

                return Str::contains($row['haystack'], $query);
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, int>
     */
    public function summary(array $rows): array
    {
        return [
            'admin_roles' => collect($rows)->where('kind', 'admin_role')->count(),
            'acl_resources' => collect($rows)->where('kind', 'acl_resource')->count(),
            'api_permissions' => collect($rows)->where('kind', 'api_permission')->count(),
            'allowed_rows' => collect($rows)->reject(fn (array $row): bool => $row['needs_attention'])->count(),
            'attention' => collect($rows)->where('needs_attention', true)->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function roleRows(): array
    {
        $roles = [
            'full' => [
                'title' => 'Full Access',
                'summary' => 'Full admin users retain dashboard, catalog, sales, reports, and OAuth API permissions.',
                'status' => 'allowed',
                'feature_ids' => ['AD-001', 'AD-012', 'API-003'],
            ],
            'partial' => [
                'title' => 'Catalog And Reports Operator',
                'summary' => 'Partial admin users keep catalog and reports permissions but remain blocked from dashboard and OAuth resources.',
                'status' => 'limited',
                'feature_ids' => ['AD-012'],
            ],
            'read-only' => [
                'title' => 'Reports Read Only',
                'summary' => 'Read-only admins can inspect reports and diagnostic workbenches without mutation rights.',
                'status' => 'read-only',
                'feature_ids' => ['AD-012'],
            ],
            'denied' => [
                'title' => 'Denied Admin',
                'summary' => 'Denied admins exercise menu-filtered and direct-URL forbidden states for admin ACL checks.',
                'status' => 'denied',
                'feature_ids' => ['AD-001', 'AD-012'],
            ],
        ];

        return collect($roles)
            ->map(fn (array $role, string $key): array => $this->row([
                'kind' => 'admin_role',
                'kind_label' => 'Admin Role',
                'key' => $key,
                'title' => $role['title'],
                'summary' => $role['summary'],
                'feature_ids' => $role['feature_ids'],
                'status' => $role['status'],
                'roles' => [$key],
                'permissions' => $this->permissionManifest->forRole($key),
                'guard' => 'admin',
                'route' => '/_modernization/auth/admin/session',
                'legacy_resource' => 'admin/roles_users',
                'risk' => $key === 'denied' ? 'permission-denied fixture' : 'fixture role parity',
            ]))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resourceRows(): array
    {
        $resources = [
            [
                'key' => 'admin_dashboard',
                'title' => 'Admin Dashboard',
                'permission' => 'admin.dashboard',
                'feature_ids' => ['AD-001', 'AD-012'],
                'status' => 'allowed',
                'summary' => 'Dashboard access stays limited to the full admin role while menu-visible and direct-URL denied states remain explicit.',
                'legacy_resource' => 'admin/dashboard',
                'risk' => 'admin shell access',
            ],
            [
                'key' => 'catalog_products',
                'title' => 'Catalog Products',
                'permission' => 'catalog.products',
                'feature_ids' => ['AD-012'],
                'status' => 'limited',
                'summary' => 'Catalog permissions are visible for full and partial roles without enabling product mutation.',
                'legacy_resource' => 'admin/catalog/products',
                'risk' => 'catalog mutation blocked',
            ],
            [
                'key' => 'sales_orders',
                'title' => 'Sales Orders',
                'permission' => 'sales.orders',
                'feature_ids' => ['AD-012'],
                'status' => 'limited',
                'summary' => 'Sales order permissions remain inspect-only because order workflows are high-risk.',
                'legacy_resource' => 'admin/sales/order',
                'risk' => 'order write blocked',
            ],
            [
                'key' => 'reports_read',
                'title' => 'Reports Read',
                'permission' => 'reports.read',
                'feature_ids' => ['AD-012'],
                'status' => 'read-only',
                'summary' => 'Report viewing is the retained low-risk permission for full, partial, and read-only roles.',
                'legacy_resource' => 'admin/report',
                'risk' => 'read-only reporting',
            ],
            [
                'key' => 'api_oauth',
                'title' => 'API OAuth Permission',
                'permission' => 'api.oauth',
                'feature_ids' => ['AD-012', 'API-003'],
                'status' => 'contract-test-required',
                'summary' => 'OAuth consumers, tokens, REST role attributes, and declared-vs-runtime ACL paths need retained fixtures before API cutover.',
                'legacy_resource' => 'admin/system/api/oauth',
                'risk' => 'OAuth token, role, and ACL path parity',
            ],
        ];

        return collect($resources)
            ->map(fn (array $resource): array => $this->row([
                'kind' => 'acl_resource',
                'kind_label' => 'ACL Resource',
                'key' => $resource['key'],
                'title' => $resource['title'],
                'summary' => $resource['summary'],
                'feature_ids' => $resource['feature_ids'],
                'status' => $resource['status'],
                'roles' => $this->rolesAllowing($resource['permission']),
                'permissions' => [$resource['permission']],
                'guard' => 'admin',
                'route' => 'can:admin.access',
                'legacy_resource' => $resource['legacy_resource'],
                'risk' => $resource['risk'],
            ]))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function apiRows(): array
    {
        $apiRows = [
            [
                'key' => 'classic_soap_xmlrpc',
                'title' => 'Classic SOAP/XML-RPC API User',
                'summary' => 'Classic API users authenticate with username/API key and return SOAP/XML-RPC faults for permission failures.',
                'feature_ids' => ['AD-012', 'API-001', 'API-002'],
                'roles' => ['admin', 'full'],
                'permissions' => ['api.classic.user', 'api.classic.role'],
                'route' => '/api/soap, /api/xmlrpc',
                'legacy_resource' => 'classic API users and roles',
                'risk' => 'fault-format and ACL parity',
            ],
            [
                'key' => 'api2_admin_role',
                'title' => 'REST/API2 Admin Role',
                'summary' => 'Admin REST role and attribute permissions must distinguish allowed resources from 401 and 403 failures.',
                'feature_ids' => ['AD-012', 'API-003'],
                'roles' => ['admin', 'full'],
                'permissions' => ['api2.admin.role', 'api2.attribute.map'],
                'route' => '/api/rest',
                'legacy_resource' => 'REST API2 admin role',
                'risk' => 'REST role and attribute parity',
            ],
            [
                'key' => 'api2_customer_role',
                'title' => 'REST/API2 Customer Role',
                'summary' => 'Customer REST role permissions need retained attribute visibility and denied-resource evidence.',
                'feature_ids' => ['API-003'],
                'roles' => ['customer'],
                'permissions' => ['api2.customer.role'],
                'route' => '/api/rest',
                'legacy_resource' => 'REST API2 customer role',
                'risk' => 'customer API visibility',
            ],
            [
                'key' => 'api2_guest_role',
                'title' => 'REST/API2 Guest Role',
                'summary' => 'Guest REST fallback must be explicit so missing OAuth is not confused with invalid OAuth.',
                'feature_ids' => ['API-003'],
                'roles' => ['guest'],
                'permissions' => ['api2.guest.role'],
                'route' => '/api/rest',
                'legacy_resource' => 'REST API2 guest role',
                'risk' => 'guest fallback semantics',
            ],
            [
                'key' => 'oauth_consumers_tokens',
                'title' => 'OAuth Consumers And Tokens',
                'summary' => 'Consumer keys, request tokens, access tokens, nonces, revocation, callback verifiers, and API2/OAuth ACL path mismatches need fixtures.',
                'feature_ids' => ['AD-012', 'API-003'],
                'roles' => ['admin', 'full'],
                'permissions' => ['oauth.consumer', 'oauth.token', 'oauth.nonce'],
                'route' => '/oauth',
                'legacy_resource' => 'OAuth consumers and tokens',
                'risk' => 'token lifecycle and runtime ACL parity',
            ],
        ];

        return collect($apiRows)
            ->map(fn (array $apiRow): array => $this->row([
                'kind' => 'api_permission',
                'kind_label' => 'API Permission',
                'key' => $apiRow['key'],
                'title' => $apiRow['title'],
                'summary' => $apiRow['summary'],
                'feature_ids' => $apiRow['feature_ids'],
                'status' => 'contract-test-required',
                'roles' => $apiRow['roles'],
                'permissions' => $apiRow['permissions'],
                'guard' => 'api',
                'route' => $apiRow['route'],
                'legacy_resource' => $apiRow['legacy_resource'],
                'risk' => $apiRow['risk'],
            ]))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function row(array $row): array
    {
        $row['status_label'] = Str::headline($row['status']);
        $row['rollback'] = 'legacy_runtime_fallback';
        $row['needs_attention'] = in_array($row['status'], ['denied', 'contract-test-required'], true);
        $row['haystack'] = $this->haystack($row);

        return $row;
    }

    /**
     * @return list<string>
     */
    private function rolesAllowing(string $permission): array
    {
        return collect($this->permissionManifest->permissions())
            ->filter(fn (array $permissions): bool => in_array($permission, $permissions, true))
            ->keys()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function haystack(array $row): string
    {
        return Str::lower(implode(' ', array_filter([
            $row['kind_label'] ?? '',
            $row['key'] ?? '',
            $row['title'] ?? '',
            $row['summary'] ?? '',
            implode(' ', $row['feature_ids'] ?? []),
            $row['status'] ?? '',
            implode(' ', $row['roles'] ?? []),
            implode(' ', $row['permissions'] ?? []),
            $row['guard'] ?? '',
            $row['route'] ?? '',
            $row['legacy_resource'] ?? '',
            $row['risk'] ?? '',
            $row['rollback'] ?? '',
        ])));
    }
}
