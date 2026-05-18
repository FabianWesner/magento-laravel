#!/usr/bin/env php
<?php

declare(strict_types=1);

$final = in_array('--final', $argv, true);
$errors = [];

validateDomainSpecs($errors);

if ($final) {
    validateFinalDomainImplementation($errors);
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

echo 'Domain target ';
echo $final ? 'final implementation check' : 'template check';
echo " passed.\n";

/**
 * @param  list<string>  $errors
 */
function validateDomainSpecs(array &$errors): void
{
    $requiredFiles = [
        'specs/GOAL.md' => [
            'Demo data must be predefined and reproducible. It must cover all Magento product types, discounts, coupons, tax/shipping/payment paths, customer groups, admin roles, API users, reports, cron jobs, multistore scope, media, and edge cases.',
            'Every existing UI must be inventoried and screenshotted for Magento and Laravel across required roles, states, and viewports.',
            'Every Magento feature must be tracked by stable feature ID from specs/modernization/magento-feature-catalog.md and cannot be marked done without fixture, characterization, implementation, test, and release evidence.',
        ],
        'specs/modernization/roadmap.md' => [
            'Implement catalog services for products, categories, attributes, pricing display, media, and search.',
            'Implement customer services for accounts, addresses, sessions, passwords, and groups.',
            'Implement CMS, newsletter, reports, sitemap, and search services.',
            'Add domain-level contract tests.',
        ],
        'specs/modernization/magento-feature-catalog.md' => [
            'CMS pages and blocks',
            'Category browsing',
            'Product listing items',
            'Search',
            'Customer commerce features',
            'Transactional communication',
            'SEO and feeds',
            'Import/export and dataflow',
            'Newsletter and polls',
        ],
        'specs/modernization/data-fixtures.md' => [
            'Product types | Simple, virtual, grouped, configurable, bundle fixed price, bundle dynamic price, downloadable, product with required custom options, product with optional custom options, product with related/up-sell/cross-sell links.',
            'Customers | Guest, registered customer, customer in each group, locked/disabled edge if project supports it, multiple addresses, default billing/shipping, newsletter subscriber, wishlist owner.',
            'CMS and content | Home page, standard content page, no-route page, static block, widget instance, WYSIWYG media reference, store-scoped content.',
            'Media | Product images, category images, CMS media, downloadable files, missing media reference, image cache regeneration case.',
        ],
        'specs/modernization/ui-screen-inventory.md' => [
            'CMS | Home, CMS page, CMS block embedded in layout, widget output, no-route/404, redirects.',
            'Product detail | Simple, configurable, grouped, bundle, downloadable, virtual, custom options, tier price, special price, out-of-stock, low stock, related, up-sell, cross-sell, reviews, tags, product alerts.',
            'Wishlist/compare/review/tag | Wishlist list/share/move to cart, compare list, review list/form, product tags if enabled.',
            'RSS/sitemap | RSS links, HTML sitemap if enabled, generated XML sitemap result.',
            'System | Configuration by scope, cache management, index management, permissions, roles, users, backups, import/export, dataflow, web services, design, stores, currency, transactional emails, custom variables, encryption key if available.',
        ],
        'specs/modernization/test-plan.md' => [
            'Product page | Configurable/simple/bundle/downloadable products, media, options, stock, price, related/up-sell/cross-sell.',
            'Customer account | Register, login, logout, forgot password, account edit, addresses, orders, wishlist if enabled.',
            'CMS | CMS page, CMS block, redirects, 404.',
            'SEO/URLs | URL rewrites, canonical links, no route, redirects, sitemap where applicable.',
            'File and media tests prove traversal is blocked.',
        ],
        'specs/modernization/complex-feature-reverse-engineering.md' => [
            'SF-001 through SF-016',
            'Storefront routes, UI states, customer/session/cart/checkout/CMS/SEO/feed behavior.',
            'Playwright/Chrome tests, screenshots, URL compatibility tests, DB/session/email side-effect tests.',
        ],
        'docusaurus/docs/user/storefront-guide.md' => [
            'Home, CMS, category, product detail, search, cart, checkout, customer account, wishlist, compare, reviews, tags, newsletter, contact, sitemap, RSS, and multistore behavior.',
            'All product types: simple, configurable, grouped, bundle, virtual, downloadable, and products with custom options.',
        ],
        'docusaurus/docs/user/admin-guide.md' => [
            'Login, dashboard, catalog, categories, products, attributes, customers, sales, invoices, shipments, credit memos, promotions, CMS, reports, tax, currency, cache, indexes, users, roles, APIs, import/export, newsletter, and store configuration.',
            'Operational recovery paths for failed imports, failed emails, stale indexes, cache invalidation, scheduler failures, and integration outages.',
        ],
    ];

    foreach ($requiredFiles as $path => $requiredPhrases) {
        $content = readTextFile($path, $errors);
        if ($content === null) {
            continue;
        }

        foreach ($requiredPhrases as $phrase) {
            if (! str_contains($content, $phrase)) {
                $errors[] = "{$path}: missing domain planning phrase '{$phrase}'";
            }
        }
    }

    validateDomainCatalog($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateDomainCatalog(array &$errors): void
{
    $path = 'specs/modernization/magento-feature-catalog.md';
    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (domainFeatureIds() as $featureId) {
        if (! str_contains($content, "| {$featureId} |")) {
            $errors[] = "{$path}: missing domain feature row for {$featureId}";
        }
    }

    foreach (['CMS', 'category', 'product', 'search', 'wishlist', 'compare', 'reviews', 'tags', 'newsletter', 'contact', 'sitemap', 'RSS', 'import', 'export', 'dataflow', 'media'] as $phrase) {
        if (! containsCaseInsensitive($content, $phrase)) {
            $errors[] = "{$path}: missing domain catalog phrase '{$phrase}'";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateFinalDomainImplementation(array &$errors): void
{
    validateDomainArtifacts($errors);
    validateDomainTests($errors);
    validateDomainEvidence($errors);
}

/**
 * @param  list<string>  $errors
 */
function validateDomainArtifacts(array &$errors): void
{
    $appFiles = phpFilesUnder('laravel/app');

    if ($appFiles === []) {
        $errors[] = 'Final domain implementation requires Laravel app files';

        return;
    }

    $requiredContexts = [
        'Catalog',
        'Category',
        'Product',
        'ProductMedia',
        'Search',
        'Customer',
        'CustomerAddress',
        'Wishlist',
        'Compare',
        'Review',
        'Tag',
        'CmsPage',
        'CmsBlock',
        'Widget',
        'Newsletter',
        'Contact',
        'Sitemap',
        'UrlRewrite',
        'ImportExport',
        'Dataflow',
        'StoreScope',
        'MediaStorage',
        'Downloadable',
    ];

    foreach ($requiredContexts as $context) {
        if (! pathOrContentMatches($appFiles, "/{$context}|".preg_quote(splitWords($context), '/').'/i')) {
            $errors[] = "Final domain implementation requires {$context} service or domain coverage";
        }
    }

    $requirements = [
        'domain service contracts or interfaces' => '/\/Contracts\/|Interface\.php$|contract/i',
        'domain DTOs or value objects' => '/\/Data\/|\/DTO\/|Dto\.php$|Data\.php$|ValueObject/i',
        'repository or query service layer' => '/Repository|QueryService|Finder|ReadModel|Projector/i',
        'media filesystem storage controls' => '/Storage::|Filesystem|MediaStorage|downloadable|assertExists|path traversal/i',
        'mail notification or queued communication artifacts' => '/\/Mail\/|\/Notifications\/|ShouldQueue|Mail::|Notification::|newsletter|product alert|send to friend/i',
        'import export validation or batch artifacts' => '/Import|Export|Dataflow|CSV|Csv|batch|validator|validation/i',
        'controller Livewire or admin surface artifacts' => '/\/Http\/Controllers\/|\/Livewire\/|Component\.php$|Admin/i',
        'sitemap URL rewrite or SEO artifact' => '/Sitemap|UrlRewrite|canonical|RSS|Seo|Redirect/i',
        'store scope and config handling' => '/StoreScope|scope|website|store view|config/i',
        'permission or policy artifacts' => '/\/Policies\/|Policy\.php$|permission|Gate::|authorize\(/i',
    ];

    foreach ($requirements as $label => $pattern) {
        if (! pathOrContentMatches($appFiles, $pattern)) {
            $errors[] = "Final domain implementation requires {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateDomainTests(array &$errors): void
{
    $testFiles = phpFilesUnder('laravel/tests');
    $aggregateContent = aggregateFiles($testFiles);

    if ($aggregateContent === '') {
        $errors[] = 'Final domain implementation requires Laravel PHPUnit tests';

        return;
    }

    $coverage = [
        'all domain feature IDs' => allDomainFeatureIdsPresent($aggregateContent),
        'catalog category product media and search behavior' => preg_match('/catalog|category|product|media|search|filter|sort|swatch/i', $aggregateContent) === 1,
        'customer address wishlist compare review tag and newsletter behavior' => preg_match('/customer|address|wishlist|compare|review|tag|newsletter/i', $aggregateContent) === 1,
        'CMS page block widget no-route and redirect behavior' => preg_match('/CMS|cms page|cms block|widget|no-route|404|redirect/i', $aggregateContent) === 1,
        'contact product alert send-to-friend and email behavior' => preg_match('/contact|product alert|send to friend|email|Mail::|Notification::/i', $aggregateContent) === 1,
        'sitemap RSS URL rewrite and SEO behavior' => preg_match('/sitemap|RSS|url rewrite|canonical|SEO|redirect/i', $aggregateContent) === 1,
        'import export dataflow validation and failure behavior' => preg_match('/import|export|dataflow|CSV|validation|error file|failed import/i', $aggregateContent) === 1,
        'media filesystem traversal and missing-media behavior' => preg_match('/Storage::fake|assertExists|assertMissing|path traversal|missing media|downloadable/i', $aggregateContent) === 1,
        'dual-runtime legacy comparison' => preg_match('/dual-runtime|legacy comparison|Magento|baseline/i', $aggregateContent) === 1,
        'DB snapshots store scope and side effects' => preg_match('/DB snapshot|database snapshot|DB delta|database delta|assertDatabase|store scope|store view/i', $aggregateContent) === 1,
    ];

    foreach ($coverage as $label => $covered) {
        if (! $covered) {
            $errors[] = "Final domain implementation requires PHPUnit coverage for {$label}";
        }
    }
}

/**
 * @param  list<string>  $errors
 */
function validateDomainEvidence(array &$errors): void
{
    $candidatePaths = [
        'specs/modernization/domain-service-evidence.md',
        'docs/content/modernization/domain-service-evidence.md',
        'docusaurus/docs/developer/domain-services.md',
    ];

    $path = firstExistingPath($candidatePaths);
    if ($path === null) {
        $errors[] = 'Final domain implementation requires evidence at one of: '.implode(', ', $candidatePaths);

        return;
    }

    $content = readTextFile($path, $errors);
    if ($content === null) {
        return;
    }

    foreach (domainFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            $errors[] = "{$path}: missing domain evidence row for {$featureId}";
        }
    }

    foreach (['Domain', 'Service Contract', 'Fixture', 'Legacy Snapshot', 'Laravel Test', 'DB Delta', 'Media Artifact', 'Email Artifact', 'Import Export', 'Store Scope', 'Approved Difference', 'Status'] as $phrase) {
        if (! str_contains($content, $phrase)) {
            $errors[] = "{$path}: missing domain evidence phrase '{$phrase}'";
        }
    }

    if (preg_match('/\b(?:TBD|Pending|Required|Required where applicable|Operational evidence required|To inventory)\b/', $content) === 1) {
        $errors[] = "{$path}: final domain evidence still contains placeholders";
    }
}

/**
 * @return list<string>
 */
function domainFeatureIds(): array
{
    return [
        'SF-001',
        'SF-002',
        'SF-003',
        'SF-004',
        'SF-005',
        'SF-006',
        'SF-010',
        'SF-011',
        'SF-013',
        'SF-014',
        'SF-016',
        'AD-002',
        'AD-003',
        'AD-004',
        'AD-007',
        'AD-009',
        'AD-013',
        'AD-015',
        'AD-017',
        'CB-012',
        'CJ-019',
        'CJ-022',
        'CJ-025',
    ];
}

function allDomainFeatureIdsPresent(string $content): bool
{
    foreach (domainFeatureIds() as $featureId) {
        if (! str_contains($content, $featureId)) {
            return false;
        }
    }

    return true;
}

/**
 * @return list<string>
 */
function phpFilesUnder(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        if (str_ends_with($path, '.php')) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

/**
 * @param  list<string>  $files
 */
function aggregateFiles(array $files): string
{
    $content = '';

    foreach ($files as $file) {
        $fileContent = file_get_contents($file);
        if ($fileContent !== false) {
            $content .= "\n".$file."\n".$fileContent;
        }
    }

    return $content;
}

/**
 * @param  list<string>  $files
 */
function pathOrContentMatches(array $files, string $pattern): bool
{
    foreach ($files as $file) {
        $content = file_get_contents($file);
        if ($content !== false && preg_match($pattern, normalizePath($file)."\n".$content) === 1) {
            return true;
        }
    }

    return false;
}

function containsCaseInsensitive(string $content, string $needle): bool
{
    return stripos($content, $needle) !== false;
}

function splitWords(string $value): string
{
    return strtolower((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $value));
}

function normalizePath(string $path): string
{
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}

/**
 * @param  list<string>  $errors
 */
function readTextFile(string $path, array &$errors): ?string
{
    if (! is_file($path)) {
        $errors[] = "{$path}: missing file";

        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $errors[] = "{$path}: unable to read file";

        return null;
    }

    return $content;
}

/**
 * @param  list<string>  $paths
 */
function firstExistingPath(array $paths): ?string
{
    foreach ($paths as $path) {
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}
