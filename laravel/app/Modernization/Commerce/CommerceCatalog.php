<?php

namespace App\Modernization\Commerce;

use InvalidArgumentException;

class CommerceCatalog
{
    /**
     * @return array<string, CommerceFeature>
     */
    public function all(): array
    {
        return [
            'quote' => new CommerceFeature('quote', 'Quote', 'Quote lifecycle', ['CB-001'], ['guest quote', 'customer quote', 'merge on login', 'expired quote']),
            'cart' => new CommerceFeature('cart', 'Cart', 'Cart rows', ['CB-001', 'CB-002'], ['add', 'update', 'remove', 'invalid quantity']),
            'totals' => new CommerceFeature('totals', 'Totals', 'Totals collector sequence', ['CB-003', 'CB-006'], ['subtotal', 'discount', 'tax', 'shipping', 'grand total']),
            'product_type' => new CommerceFeature('product_type', 'ProductType', 'Product type behavior', ['CB-002'], ['simple', 'configurable', 'grouped', 'bundle', 'downloadable', 'virtual']),
            'pricing' => new CommerceFeature('pricing', 'Pricing', 'Price resolution', ['CB-003'], ['base price', 'special price', 'tier price', 'catalog rule']),
            'promotion' => new CommerceFeature('promotion', 'Promotion', 'Catalog rule and cart rule coupons', ['CB-004', 'CB-005'], ['coupon', 'cart rule', 'catalog rule', 'free shipping']),
            'tax' => new CommerceFeature('tax', 'Tax', 'Tax calculation', ['CB-006'], ['shipping tax', 'discount before tax', 'discount after tax']),
            'shipping' => new CommerceFeature('shipping', 'Shipping', 'Shipping rates', ['CB-007'], ['free shipping', 'table rate', 'UPS', 'USPS', 'FedEx', 'DHL', 'unavailable shipping']),
            'payment' => new CommerceFeature('payment', 'Payment', 'Payment lifecycle', ['CB-008'], ['authorize', 'capture', 'void', 'failed payment', 'sandbox retry timeout mock']),
            'inventory' => new CommerceFeature('inventory', 'Inventory', 'Inventory and stock', ['CB-009'], ['in stock', 'out of stock', 'low stock']),
            'order' => new CommerceFeature('order', 'Order', 'Order state machine', ['CB-010'], ['pending', 'processing', 'complete', 'canceled', 'payment review']),
            'invoice' => new CommerceFeature('invoice', 'Invoice', 'Invoice lifecycle', ['CB-010'], ['partial invoice', 'PDF', 'email']),
            'shipment' => new CommerceFeature('shipment', 'Shipment', 'Shipment lifecycle', ['CB-010'], ['partial shipment', 'tracking']),
            'credit_memo' => new CommerceFeature('credit_memo', 'CreditMemo', 'Credit memo lifecycle', ['CB-010'], ['credit memo', 'creditmemo', 'PDF']),
            'refund' => new CommerceFeature('refund', 'Refund', 'Refund lifecycle', ['CB-010'], ['online refund', 'offline refund']),
            'eav_scope' => new CommerceFeature('eav_scope', 'EavScope', 'EAV scope semantics', ['CB-011'], ['attribute fallback', 'store labels', 'option labels', 'entity defaults', 'backend models', 'source models', 'validation']),
            'index' => new CommerceFeature('index', 'Index', 'Indexing', ['CB-012'], ['stale index', 'recovery']),
            'cache' => new CommerceFeature('cache', 'Cache', 'Cache and session behavior', ['CB-013'], ['stale cache', 'session']),
            'email_queue' => new CommerceFeature('email_queue', 'EmailQueue', 'Email queue', ['CB-014'], ['email queue', 'failed email']),
        ];
    }

    public function get(string $key): CommerceFeature
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Commerce feature [{$key}] is not configured.");
    }
}
