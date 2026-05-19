<?php

namespace App\Modernization\Domain;

use InvalidArgumentException;

class DomainCatalog
{
    /**
     * @return array<string, DomainFeature>
     */
    public function all(): array
    {
        return [
            'catalog' => new DomainFeature('catalog', 'Catalog', 'Catalog browsing', ['SF-002', 'AD-002'], ['category', 'product listing', 'filter', 'sort', 'swatch'], true),
            'category' => new DomainFeature('category', 'Category', 'Category browsing', ['SF-002', 'AD-003'], ['category images', 'store view'], true),
            'product' => new DomainFeature('product', 'Product', 'Product detail', ['SF-003', 'SF-004', 'SF-005', 'AD-004'], ['simple', 'configurable', 'grouped', 'bundle', 'virtual', 'downloadable', 'custom options'], true),
            'product_media' => new DomainFeature('product_media', 'ProductMedia', 'Product media', ['SF-005', 'AD-004'], ['media gallery', 'missing media', 'image cache'], true),
            'search' => new DomainFeature('search', 'Search', 'Search and RSS', ['SF-006', 'SF-014', 'CJ-019'], ['search terms', 'canonical', 'RSS'], true),
            'customer' => new DomainFeature('customer', 'Customer', 'Customer account', ['SF-010'], ['register', 'login', 'groups'], true),
            'customer_address' => new DomainFeature('customer_address', 'CustomerAddress', 'Customer address', ['SF-010'], ['billing', 'shipping', 'validation'], true),
            'wishlist' => new DomainFeature('wishlist', 'Wishlist', 'Wishlist', ['SF-011'], ['share', 'move to cart'], true),
            'compare' => new DomainFeature('compare', 'Compare', 'Compare products', ['SF-011'], ['compare list'], true),
            'review' => new DomainFeature('review', 'Review', 'Reviews', ['SF-011', 'AD-009'], ['review list', 'review form'], true),
            'tag' => new DomainFeature('tag', 'Tag', 'Tags', ['SF-011', 'AD-009'], ['product tags'], true),
            'cms_page' => new DomainFeature('cms_page', 'CmsPage', 'CMS page', ['SF-001', 'SF-013', 'AD-007'], ['CMS page', 'no-route', '404', 'redirect'], true),
            'cms_block' => new DomainFeature('cms_block', 'CmsBlock', 'CMS block', ['SF-001', 'SF-013', 'AD-007'], ['cms block', 'WYSIWYG media'], true),
            'widget' => new DomainFeature('widget', 'Widget', 'Widget output', ['SF-013', 'AD-007'], ['widget instance'], true),
            'newsletter' => new DomainFeature('newsletter', 'Newsletter', 'Newsletter', ['SF-016', 'AD-015', 'CJ-022'], ['subscriber', 'problem reports', 'email'], true),
            'contact' => new DomainFeature('contact', 'Contact', 'Contact and send to friend', ['SF-016', 'AD-017'], ['contact', 'product alert', 'send to friend', 'email'], true),
            'cache' => new DomainFeature('cache', 'Cache', 'Cache and compiler operations', ['AD-011', 'CB-013', 'CJ-016'], ['enabled', 'disabled', 'invalidated', 'stale cache', 'compiler'], true),
            'index' => new DomainFeature('index', 'Index', 'Index management', ['AD-011', 'CB-012', 'CJ-021'], ['ready', 'processing', 'reindex required', 'update required', 'lock'], true),
            'sitemap' => new DomainFeature('sitemap', 'Sitemap', 'Sitemap and RSS', ['SF-014', 'CJ-025'], ['sitemap', 'RSS', 'SEO'], true),
            'url_rewrite' => new DomainFeature('url_rewrite', 'UrlRewrite', 'URL rewrite', ['SF-014', 'CB-012'], ['url rewrite', 'canonical', 'redirect'], true),
            'import_export' => new DomainFeature('import_export', 'ImportExport', 'Import export', ['AD-013'], ['CSV', 'validation', 'generated file', 'error file'], false),
            'dataflow' => new DomainFeature('dataflow', 'Dataflow', 'Dataflow profiles', ['AD-013'], ['profile', 'batch', 'failed import', 'generated file'], false),
            'store_scope' => new DomainFeature('store_scope', 'StoreScope', 'Store scope', ['SF-001', 'SF-002'], ['website', 'store view', 'config'], true),
            'media_storage' => new DomainFeature('media_storage', 'MediaStorage', 'Media storage', ['SF-005', 'AD-013'], ['Filesystem', 'path traversal', 'missing media'], true),
            'downloadable' => new DomainFeature('downloadable', 'Downloadable', 'Downloadable products', ['SF-005'], ['downloadable file', 'permission'], true),
        ];
    }

    public function get(string $key): DomainFeature
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Domain feature [{$key}] is not configured.");
    }
}
