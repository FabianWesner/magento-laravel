#!/usr/bin/env php
<?php

declare(strict_types=1);

function usage(): void
{
    echo "Usage: DB_DSN='mysql:host=127.0.0.1;dbname=magento' DB_USER=root DB_PASS=secret php dev/modernization/fixture-coverage-report.php [--format=json|markdown] [--fail-on-gaps]\n";
    echo "Optional: DB_TABLE_PREFIX=prefix_\n";
}

$format = 'markdown';
$failOnGaps = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--help' || $arg === '-h') {
        usage();
        exit(0);
    }
    if ($arg === '--fail-on-gaps') {
        $failOnGaps = true;

        continue;
    }
    if (str_starts_with($arg, '--format=')) {
        $format = substr($arg, 9);
    }
}

$dsn = getenv('DB_DSN') ?: '';
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$prefix = getenv('DB_TABLE_PREFIX') ?: '';

if ($dsn === '') {
    fwrite(STDERR, "DB_DSN is required.\n");
    usage();
    exit(2);
}

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $throwable) {
    fwrite(STDERR, "Fixture coverage report failed: {$throwable->getMessage()}\n");
    exit(1);
}

function tableName(string $name): string
{
    global $prefix;

    return $prefix.$name;
}

function quoteTable(string $name): string
{
    return '`'.str_replace('`', '``', tableName($name)).'`';
}

function tableExists(PDO $pdo, string $table): bool
{
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $statement->execute([tableName($table)]);

    return (int) $statement->fetchColumn() > 0;
}

function scalar(PDO $pdo, string $sql, array $bindings = []): int|string|null
{
    $statement = $pdo->prepare($sql);
    $statement->execute($bindings);
    $value = $statement->fetchColumn();

    return $value === false ? null : $value;
}

function countTable(PDO $pdo, string $table): int
{
    if (! tableExists($pdo, $table)) {
        return 0;
    }

    return (int) scalar($pdo, 'SELECT COUNT(*) FROM '.quoteTable($table));
}

function groupCounts(PDO $pdo, string $sql): array
{
    $rows = $pdo->query($sql)->fetchAll();
    $counts = [];
    foreach ($rows as $row) {
        $counts[(string) $row['label']] = (int) $row['total'];
    }
    ksort($counts);

    return $counts;
}

function implodeCounts(array $counts): string
{
    if ($counts === []) {
        return 'none';
    }

    $parts = [];
    foreach ($counts as $label => $total) {
        $parts[] = "{$label}: {$total}";
    }

    return implode('; ', $parts);
}

function addCheck(array &$checks, string $area, string $required, string $evidence, bool $covered): void
{
    $checks[] = [
        'area' => $area,
        'required' => $required,
        'evidence' => $evidence,
        'status' => $covered ? 'covered' : 'gap',
    ];
}

$checks = [];

$productTypes = groupCounts($pdo, 'SELECT type_id AS label, COUNT(*) AS total FROM '.quoteTable('catalog_product_entity').' GROUP BY type_id');
$requiredProductTypes = ['bundle', 'configurable', 'downloadable', 'grouped', 'simple', 'virtual'];
$missingProductTypes = array_diff($requiredProductTypes, array_keys($productTypes));
addCheck(
    $checks,
    'Product types',
    'Simple, virtual, grouped, configurable, bundle, and downloadable products.',
    'Observed '.implodeCounts($productTypes).($missingProductTypes === [] ? '' : '; missing: '.implode(', ', $missingProductTypes)),
    $missingProductTypes === [],
);

$websites = countTable($pdo, 'core_website');
$storeGroups = countTable($pdo, 'core_store_group');
$stores = countTable($pdo, 'core_store');
$disabledStores = tableExists($pdo, 'core_store')
    ? (int) scalar($pdo, 'SELECT COUNT(*) FROM '.quoteTable('core_store').' WHERE is_active = 0')
    : 0;
addCheck(
    $checks,
    'Websites and stores',
    'At least 2 websites, 2 store groups, 3 store views, and 1 disabled store view.',
    "websites: {$websites}; store groups: {$storeGroups}; store views: {$stores}; disabled store views: {$disabledStores}",
    $websites >= 2 && $storeGroups >= 2 && $stores >= 3 && $disabledStores >= 1,
);

$categoryCount = countTable($pdo, 'catalog_category_entity');
$maxCategoryLevel = (int) (scalar($pdo, 'SELECT COALESCE(MAX(level), 0) FROM '.quoteTable('catalog_category_entity')) ?? 0);
$disabledCategories = (int) scalar(
    $pdo,
    'SELECT COUNT(DISTINCT value.entity_id)
        FROM '.quoteTable('catalog_category_entity_int').' value
        INNER JOIN '.quoteTable('eav_attribute').' attribute ON attribute.attribute_id = value.attribute_id
        WHERE attribute.attribute_code = ? AND value.value = 0',
    ['is_active'],
);
$emptyCategories = (int) scalar(
    $pdo,
    'SELECT COUNT(*)
        FROM '.quoteTable('catalog_category_entity').' category
        LEFT JOIN '.quoteTable('catalog_category_product').' relation ON relation.category_id = category.entity_id
        WHERE category.level > 1 AND relation.product_id IS NULL',
);
addCheck(
    $checks,
    'Categories',
    'Nested categories at least 4 levels deep, disabled category, and empty category.',
    "categories: {$categoryCount}; max level: {$maxCategoryLevel}; disabled: {$disabledCategories}; empty: {$emptyCategories}",
    $maxCategoryLevel >= 4 && $disabledCategories >= 1 && $emptyCategories >= 1,
);

$requiredOptions = tableExists($pdo, 'catalog_product_option')
    ? (int) scalar($pdo, 'SELECT COUNT(*) FROM '.quoteTable('catalog_product_option').' WHERE is_require = 1')
    : 0;
$optionalOptions = tableExists($pdo, 'catalog_product_option')
    ? (int) scalar($pdo, 'SELECT COUNT(*) FROM '.quoteTable('catalog_product_option').' WHERE is_require = 0')
    : 0;
addCheck(
    $checks,
    'Custom options',
    'Products with required and optional custom options.',
    "required options: {$requiredOptions}; optional options: {$optionalOptions}",
    $requiredOptions >= 1 && $optionalOptions >= 1,
);

$attributeBackendTypes = groupCounts($pdo, 'SELECT backend_type AS label, COUNT(*) AS total FROM '.quoteTable('eav_attribute').' GROUP BY backend_type');
$requiredBackendTypes = ['datetime', 'decimal', 'int', 'static', 'text', 'varchar'];
$missingBackendTypes = array_diff($requiredBackendTypes, array_keys($attributeBackendTypes));
$attributeScopes = tableExists($pdo, 'catalog_eav_attribute')
    ? groupCounts($pdo, 'SELECT is_global AS label, COUNT(*) AS total FROM '.quoteTable('catalog_eav_attribute').' GROUP BY is_global')
    : [];
addCheck(
    $checks,
    'EAV attributes',
    'Every backend type and global, website, and store scope coverage.',
    'backend types: '.implodeCounts($attributeBackendTypes).'; scopes: '.implodeCounts($attributeScopes).($missingBackendTypes === [] ? '' : '; missing backend types: '.implode(', ', $missingBackendTypes)),
    $missingBackendTypes === [] && isset($attributeScopes['0'], $attributeScopes['1'], $attributeScopes['2']),
);

$specialPriceProducts = (int) scalar(
    $pdo,
    'SELECT COUNT(DISTINCT value.entity_id)
        FROM '.quoteTable('catalog_product_entity_decimal').' value
        INNER JOIN '.quoteTable('eav_attribute').' attribute ON attribute.attribute_id = value.attribute_id
        WHERE attribute.attribute_code = ? AND value.value IS NOT NULL',
    ['special_price'],
);
$tierPrices = countTable($pdo, 'catalog_product_entity_tier_price');
$groupPrices = countTable($pdo, 'catalog_product_entity_group_price');
$catalogRules = countTable($pdo, 'catalogrule');
$cartRules = countTable($pdo, 'salesrule');
addCheck(
    $checks,
    'Pricing and promotions',
    'Special price, tier price, group price, catalog rule, and cart rule data.',
    "special-price products: {$specialPriceProducts}; tier prices: {$tierPrices}; group prices: {$groupPrices}; catalog rules: {$catalogRules}; cart rules: {$cartRules}",
    $specialPriceProducts >= 1 && $tierPrices >= 1 && $groupPrices >= 1 && $catalogRules >= 1 && $cartRules >= 1,
);

$stockItems = countTable($pdo, 'cataloginventory_stock_item');
$outOfStock = tableExists($pdo, 'cataloginventory_stock_item')
    ? (int) scalar($pdo, 'SELECT COUNT(*) FROM '.quoteTable('cataloginventory_stock_item').' WHERE is_in_stock = 0')
    : 0;
$backorders = tableExists($pdo, 'cataloginventory_stock_item')
    ? (int) scalar($pdo, 'SELECT COUNT(*) FROM '.quoteTable('cataloginventory_stock_item').' WHERE backorders > 0')
    : 0;
addCheck(
    $checks,
    'Inventory',
    'In-stock, out-of-stock, and backorder product coverage.',
    "stock items: {$stockItems}; out of stock: {$outOfStock}; backorder-enabled: {$backorders}",
    $stockItems >= 1 && $outOfStock >= 1 && $backorders >= 1,
);

$customerGroups = countTable($pdo, 'customer_group');
$customers = countTable($pdo, 'customer_entity');
$addresses = countTable($pdo, 'customer_address_entity');
addCheck(
    $checks,
    'Customers',
    'Registered customers, customer groups, and customer addresses.',
    "customers: {$customers}; customer groups: {$customerGroups}; addresses: {$addresses}",
    $customers >= 1 && $customerGroups >= 2 && $addresses >= 1,
);

$orders = countTable($pdo, 'sales_flat_order');
$invoices = countTable($pdo, 'sales_flat_invoice');
$shipments = countTable($pdo, 'sales_flat_shipment');
$creditMemos = countTable($pdo, 'sales_flat_creditmemo');
addCheck(
    $checks,
    'Sales lifecycle',
    'Orders, invoices, shipments, and credit memos.',
    "orders: {$orders}; invoices: {$invoices}; shipments: {$shipments}; credit memos: {$creditMemos}",
    $orders >= 1 && $invoices >= 1 && $shipments >= 1 && $creditMemos >= 1,
);

$taxRates = countTable($pdo, 'tax_calculation_rate');
$taxRules = countTable($pdo, 'tax_calculation_rule');
$tableRates = countTable($pdo, 'shipping_tablerate');
addCheck(
    $checks,
    'Tax and shipping',
    'Multiple tax rates/rules and table-rate shipping data.',
    "tax rates: {$taxRates}; tax rules: {$taxRules}; table rates: {$tableRates}",
    $taxRates >= 2 && $taxRules >= 1 && $tableRates >= 1,
);

$activePaymentConfig = tableExists($pdo, 'core_config_data')
    ? (int) scalar($pdo, 'SELECT COUNT(*) FROM '.quoteTable('core_config_data')." WHERE path LIKE 'payment/%/active' AND value = '1'")
    : 0;
$apiUsers = countTable($pdo, 'api_user');
$oauthConsumers = countTable($pdo, 'oauth_consumer');
addCheck(
    $checks,
    'Payment and API users',
    'Active payment method config plus SOAP/XML-RPC API users and OAuth consumers.',
    "active payment configs: {$activePaymentConfig}; API users: {$apiUsers}; OAuth consumers: {$oauthConsumers}",
    $activePaymentConfig >= 1 && $apiUsers >= 1 && $oauthConsumers >= 1,
);

$cmsPages = countTable($pdo, 'cms_page');
$cmsBlocks = countTable($pdo, 'cms_block');
$widgets = countTable($pdo, 'widget_instance');
$mediaGallery = countTable($pdo, 'catalog_product_entity_media_gallery');
addCheck(
    $checks,
    'CMS and media',
    'CMS pages, blocks, widgets, and product media gallery references.',
    "CMS pages: {$cmsPages}; CMS blocks: {$cmsBlocks}; widgets: {$widgets}; product media rows: {$mediaGallery}",
    $cmsPages >= 1 && $cmsBlocks >= 1 && $widgets >= 1 && $mediaGallery >= 1,
);

$adminUsers = countTable($pdo, 'admin_user');
$adminRoles = countTable($pdo, 'admin_role');
addCheck(
    $checks,
    'Admin users and roles',
    'Full, partial, denied, and API/admin role fixtures.',
    "admin users: {$adminUsers}; admin roles: {$adminRoles}",
    $adminUsers >= 2 && $adminRoles >= 4,
);

$cronRows = countTable($pdo, 'cron_schedule');
$reportRows = countTable($pdo, 'sales_order_aggregated_created')
    + countTable($pdo, 'sales_bestsellers_aggregated_daily')
    + countTable($pdo, 'tax_order_aggregated_created');
addCheck(
    $checks,
    'Cron and reports',
    'Cron schedule rows and populated report aggregate tables.',
    "cron rows: {$cronRows}; selected report aggregate rows: {$reportRows}",
    $cronRows >= 1 && $reportRows >= 1,
);

$covered = count(array_filter($checks, static fn (array $check): bool => $check['status'] === 'covered'));
$gaps = count($checks) - $covered;
$report = [
    'database' => (string) scalar($pdo, 'SELECT DATABASE()'),
    'checks' => $checks,
    'summary' => [
        'total' => count($checks),
        'covered' => $covered,
        'gaps' => $gaps,
    ],
];

if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit($failOnGaps && $gaps > 0 ? 1 : 0);
}

if ($format !== 'markdown') {
    fwrite(STDERR, "Unsupported format: {$format}\n");
    exit(1);
}

echo "# Fixture Coverage Report\n\n";
echo "- Database: `{$report['database']}`\n";
echo "- Checks: {$report['summary']['total']}\n";
echo "- Covered: {$report['summary']['covered']}\n";
echo "- Gaps: {$report['summary']['gaps']}\n\n";
echo "| Area | Required Coverage | Current Evidence | Status |\n";
echo "| --- | --- | --- | --- |\n";
foreach ($report['checks'] as $check) {
    echo "| {$check['area']} | {$check['required']} | {$check['evidence']} | `{$check['status']}` |\n";
}

exit($failOnGaps && $gaps > 0 ? 1 : 0);
