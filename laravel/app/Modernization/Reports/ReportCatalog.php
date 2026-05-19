<?php

namespace App\Modernization\Reports;

use InvalidArgumentException;

class ReportCatalog
{
    /**
     * @return array<string, ReportDefinition>
     */
    public function all(): array
    {
        $filters = ['date range', 'from date', 'to date', 'store scope', 'store id', 'website', 'currency'];

        return [
            'sales' => new ReportDefinition('sales', 'SalesReport', 'Sales report', ['AD-014', 'CJ-007'], 'report_facts', 'amount', 'bucket', $filters),
            'tax' => new ReportDefinition('tax', 'TaxReport', 'Tax report', ['AD-014', 'CJ-020'], 'report_facts', 'amount', 'bucket', $filters),
            'shipping' => new ReportDefinition('shipping', 'ShippingReport', 'Shipping report', ['AD-014', 'CJ-009'], 'report_facts', 'amount', 'bucket', $filters),
            'invoiced' => new ReportDefinition('invoiced', 'InvoicedReport', 'Invoiced report', ['AD-014', 'CJ-008'], 'report_facts', 'amount', 'bucket', $filters),
            'refunded' => new ReportDefinition('refunded', 'RefundedReport', 'Refunded report', ['AD-014', 'CJ-010'], 'report_facts', 'amount', 'bucket', $filters),
            'coupon' => new ReportDefinition('coupon', 'CouponReport', 'Coupon report', ['AD-008', 'CJ-015'], 'report_facts', 'amount', 'bucket', $filters),
            'product' => new ReportDefinition('product', 'ProductReport', 'Product report', ['AD-005', 'AD-014'], 'report_facts', 'amount', 'bucket', $filters),
            'customer' => new ReportDefinition('customer', 'CustomerReport', 'Customer report', ['AD-006', 'AD-014'], 'report_facts', 'amount', 'bucket', $filters),
            'search' => new ReportDefinition('search', 'SearchReport', 'Search terms report', ['AD-014', 'CJ-004'], 'report_facts', 'amount', 'bucket', $filters),
            'cart' => new ReportDefinition('cart', 'CartReport', 'Cart report', ['AD-014', 'CJ-007'], 'report_facts', 'amount', 'bucket', $filters),
            'review' => new ReportDefinition('review', 'ReviewReport', 'Review report', ['AD-014', 'CJ-007'], 'report_facts', 'amount', 'bucket', $filters),
            'tag' => new ReportDefinition('tag', 'TagReport', 'Tag report', ['AD-014', 'CJ-007'], 'report_facts', 'amount', 'bucket', $filters),
            'bestseller' => new ReportDefinition('bestseller', 'BestsellerReport', 'Bestseller report', ['AD-014', 'CJ-011'], 'report_facts', 'amount', 'bucket', $filters),
            'low_stock' => new ReportDefinition('low_stock', 'LowStockReport', 'Low stock report', ['AD-014'], 'report_facts', 'amount', 'bucket', $filters),
        ];
    }

    public function get(string $key): ReportDefinition
    {
        return $this->all()[$key] ?? throw new InvalidArgumentException("Report [{$key}] is not configured.");
    }
}
