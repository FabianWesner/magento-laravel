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
            'cms_page' => new DomainFeature('cms_page', 'CmsPage', 'CMS page', ['SF-001', 'SF-013', 'AD-007', 'AD-009'], ['CMS page', 'no-route', '404', 'redirect'], true),
            'cms_block' => new DomainFeature('cms_block', 'CmsBlock', 'CMS block', ['SF-001', 'SF-013', 'AD-007', 'AD-009'], ['cms block', 'WYSIWYG media'], true),
            'widget' => new DomainFeature('widget', 'Widget', 'Widget output', ['SF-013', 'AD-007', 'AD-009'], ['widget instance'], true),
            'newsletter' => new DomainFeature('newsletter', 'Newsletter', 'Newsletter', ['SF-016', 'AD-015', 'CJ-022'], ['subscriber', 'problem reports', 'email'], true),
            'poll' => new DomainFeature('poll', 'Poll', 'Polls', ['AD-015'], ['poll', 'answer', 'vote', 'closed', 'validation'], true),
            'contact' => new DomainFeature('contact', 'Contact', 'Contact and send to friend', ['SF-016', 'AD-017'], ['contact', 'product alert', 'send to friend', 'email'], true),
            'quote' => new DomainFeature('quote', 'Quote', 'Quote lifecycle', ['SF-007', 'SF-008', 'SF-009', 'CB-001', 'CJ-006'], ['guest quote', 'customer quote', 'merge on login', 'persistent cart', 'expired quote'], true),
            'cart_item' => new DomainFeature('cart_item', 'CartItem', 'Cart items', ['SF-007', 'CB-001', 'CB-002', 'CB-009'], ['simple', 'configurable', 'invalid quantity', 'low stock', 'backorder'], true),
            'cart_total' => new DomainFeature('cart_total', 'CartTotal', 'Cart totals', ['SF-007', 'SF-008', 'CB-003', 'CB-004', 'CB-006', 'CB-007'], ['subtotal', 'discount', 'tax', 'shipping', 'grand total', 'stale totals'], true),
            'shipping_rate' => new DomainFeature('shipping_rate', 'ShippingRate', 'Shipping rates', ['SF-007', 'SF-008', 'SF-009', 'CB-007'], ['flat rate', 'free shipping', 'table rate', 'virtual-only cart', 'unavailable shipping'], true),
            'backup' => new DomainFeature('backup', 'Backup', 'Backups', ['AD-017', 'CJ-001'], ['database backup', 'filesystem backup', 'rollback', 'maintenance'], true),
            'system_info' => new DomainFeature('system_info', 'SystemInfo', 'System information', ['AD-017'], ['runtime', 'extensions', 'database', 'filesystem'], true),
            'email_template' => new DomainFeature('email_template', 'EmailTemplate', 'Email templates', ['SF-013', 'AD-017', 'CB-014'], ['transactional email', 'template', 'locale', 'variables'], true),
            'sales_order' => new DomainFeature('sales_order', 'SalesOrder', 'Sales orders', ['AD-005', 'CB-010'], ['grid', 'state guard', 'comment history', 'payment review'], true),
            'sales_invoice' => new DomainFeature('sales_invoice', 'SalesInvoice', 'Sales invoices', ['AD-006', 'CB-010', 'CB-014'], ['capture', 'PDF', 'email', 'partial invoice'], true),
            'sales_shipment' => new DomainFeature('sales_shipment', 'SalesShipment', 'Sales shipments', ['AD-006', 'CB-010'], ['tracking', 'label', 'email', 'partial shipment'], true),
            'sales_credit_memo' => new DomainFeature('sales_credit_memo', 'SalesCreditMemo', 'Sales credit memos', ['AD-006', 'CB-008', 'CB-010'], ['online refund', 'offline refund', 'adjustment', 'PDF'], true),
            'sales_transaction' => new DomainFeature('sales_transaction', 'SalesTransaction', 'Sales payment transactions', ['AD-005', 'AD-006', 'CB-008', 'CB-010'], ['authorization', 'capture', 'refund', 'payment review'], true),
            'promotion_rule' => new DomainFeature('promotion_rule', 'PromotionRule', 'Cart price rules', ['AD-008', 'CB-004', 'CB-005'], ['active', 'inactive', 'expired', 'invalid condition', 'percent discount'], true),
            'catalog_price_rule' => new DomainFeature('catalog_price_rule', 'CatalogPriceRule', 'Catalog price rules', ['AD-008', 'CB-003', 'CB-004', 'CJ-014'], ['scheduled', 'active', 'expired', 'stale application', 'price resolution'], true),
            'promotion_coupon' => new DomainFeature('promotion_coupon', 'PromotionCoupon', 'Coupon codes and usage', ['AD-008', 'CB-004', 'CB-005'], ['autogenerated', 'manual', 'used', 'exhausted', 'problem'], true),
            'promotion_report' => new DomainFeature('promotion_report', 'PromotionReport', 'Promotion report aggregation', ['AD-008', 'CJ-015'], ['aggregation row', 'coupon report', 'cron signal'], true),
            'cache' => new DomainFeature('cache', 'Cache', 'Cache and compiler operations', ['AD-011', 'CB-013', 'CJ-016'], ['enabled', 'disabled', 'invalidated', 'stale cache', 'compiler'], true),
            'index' => new DomainFeature('index', 'Index', 'Index management', ['AD-011', 'CB-012', 'CJ-021'], ['ready', 'processing', 'reindex required', 'update required', 'lock'], true),
            'tax' => new DomainFeature('tax', 'Tax', 'Tax rates and rules', ['AD-016', 'CB-006', 'CJ-020'], ['tax class', 'rate', 'rule', 'calculation', 'report aggregation'], true),
            'currency' => new DomainFeature('currency', 'Currency', 'Currency rates and symbols', ['AD-016', 'CJ-002'], ['base currency', 'display currency', 'rate import', 'symbol override', 'scheduled update'], true),
            'sitemap' => new DomainFeature('sitemap', 'Sitemap', 'Sitemap and RSS', ['SF-014', 'AD-017', 'CJ-025'], ['sitemap', 'RSS', 'SEO'], true),
            'url_rewrite' => new DomainFeature('url_rewrite', 'UrlRewrite', 'URL rewrite', ['SF-014', 'AD-017', 'CB-012'], ['url rewrite', 'canonical', 'redirect'], true),
            'import_export' => new DomainFeature('import_export', 'ImportExport', 'Import export', ['AD-013'], ['CSV', 'validation', 'generated file', 'error file'], false),
            'dataflow' => new DomainFeature('dataflow', 'Dataflow', 'Dataflow profiles', ['AD-013'], ['profile', 'batch', 'failed import', 'generated file'], false),
            'system_config' => new DomainFeature('system_config', 'SystemConfig', 'System configuration', ['SF-012', 'AD-010', 'CB-011', 'CB-013'], ['scope fallback', 'inherited value', 'source model', 'backend model', 'secret', 'env override'], true),
            'store_scope' => new DomainFeature('store_scope', 'StoreScope', 'Store scope', ['SF-001', 'SF-002', 'SF-012', 'AD-009', 'AD-017'], ['website', 'store view', 'config'], true),
            'media_storage' => new DomainFeature('media_storage', 'MediaStorage', 'Media storage', ['SF-005', 'AD-013'], ['Filesystem', 'path traversal', 'missing media'], true),
            'downloadable' => new DomainFeature('downloadable', 'Downloadable', 'Downloadable products', ['SF-005'], ['downloadable file', 'permission'], true),
        ];
    }

    public function get(string $key): DomainFeature
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Domain feature [{$key}] is not configured.");
    }
}
