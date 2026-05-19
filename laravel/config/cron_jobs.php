<?php

return [
    'queue' => 'default',

    'jobs' => [
        ['feature_id' => 'CJ-001', 'name' => 'scheduled backup', 'schedule' => 'config-driven', 'legacy_model' => 'backup/observer::scheduledBackup', 'decision' => 'bridge'],
        ['feature_id' => 'CJ-002', 'name' => 'currency rate update', 'schedule' => 'config-driven', 'legacy_model' => 'directory/observer::scheduledUpdateCurrencyRates', 'decision' => 'bridge'],
        ['feature_id' => 'CJ-003', 'name' => 'delete customer flow password', 'schedule' => '0 0 1 * *', 'legacy_model' => 'customer/observer::deleteCustomerFlowPassword', 'decision' => 'replace'],
        ['feature_id' => 'CJ-004', 'name' => 'PayPal fetch reports', 'schedule' => 'config-driven', 'legacy_model' => 'paypal/observer::fetchReports', 'decision' => 'bridge'],
        ['feature_id' => 'CJ-005', 'name' => 'log cleanup', 'schedule' => 'config-driven', 'legacy_model' => 'log/cron::logClean', 'decision' => 'replace'],
        ['feature_id' => 'CJ-006', 'name' => 'clean expired quotes', 'schedule' => '0 0 * * *', 'legacy_model' => 'sales/observer::cleanExpiredQuotes', 'decision' => 'replace'],
        ['feature_id' => 'CJ-007', 'name' => 'aggregate sales orders', 'schedule' => '0 0 * * *', 'legacy_model' => 'sales/observer::aggregateSalesReportOrderData', 'decision' => 'replace'],
        ['feature_id' => 'CJ-008', 'name' => 'aggregate sales shipments', 'schedule' => '0 0 * * *', 'legacy_model' => 'sales/observer::aggregateSalesReportShipmentData', 'decision' => 'replace'],
        ['feature_id' => 'CJ-009', 'name' => 'aggregate sales invoiced', 'schedule' => '0 0 * * *', 'legacy_model' => 'sales/observer::aggregateSalesReportInvoicedData', 'decision' => 'replace'],
        ['feature_id' => 'CJ-010', 'name' => 'aggregate sales refunded', 'schedule' => '0 0 * * *', 'legacy_model' => 'sales/observer::aggregateSalesReportRefundedData', 'decision' => 'replace'],
        ['feature_id' => 'CJ-011', 'name' => 'aggregate bestsellers', 'schedule' => '0 0 * * *', 'legacy_model' => 'sales/observer::aggregateSalesReportBestsellersData', 'decision' => 'replace'],
        ['feature_id' => 'CJ-012', 'name' => 'clear expired persistent sessions', 'schedule' => '0 0 * * *', 'legacy_model' => 'persistent/observer::clearExpiredCronJob', 'decision' => 'replace'],
        ['feature_id' => 'CJ-013', 'name' => 'XmlConnect scheduled send', 'schedule' => '*/5 * * * *', 'legacy_model' => 'xmlconnect/observer::scheduledSend', 'decision' => 'retire_or_bridge'],
        ['feature_id' => 'CJ-014', 'name' => 'daily catalog rule update', 'schedule' => '0 1 * * *', 'legacy_model' => 'catalogrule/observer::dailyCatalogUpdate', 'decision' => 'replace'],
        ['feature_id' => 'CJ-015', 'name' => 'aggregate coupon reports', 'schedule' => '0 0 * * *', 'legacy_model' => 'salesrule/observer::aggregateSalesReportCouponsData', 'decision' => 'replace'],
        ['feature_id' => 'CJ-016', 'name' => 'clean cache', 'schedule' => '30 2 * * *', 'legacy_model' => 'core/observer::cleanCache', 'decision' => 'replace'],
        ['feature_id' => 'CJ-017', 'name' => 'send email queue', 'schedule' => '*/1 * * * *', 'legacy_model' => 'core/email_queue::send', 'decision' => 'replace'],
        ['feature_id' => 'CJ-018', 'name' => 'clean email queue', 'schedule' => '0 0 * * *', 'legacy_model' => 'core/email_queue::cleanQueue', 'decision' => 'replace'],
        ['feature_id' => 'CJ-019', 'name' => 'product alerts', 'schedule' => 'config-driven', 'legacy_model' => 'productalert/observer::process', 'decision' => 'replace'],
        ['feature_id' => 'CJ-020', 'name' => 'aggregate tax reports', 'schedule' => '0 0 * * *', 'legacy_model' => 'tax/observer::aggregateSalesReportTaxData', 'decision' => 'replace'],
        ['feature_id' => 'CJ-021', 'name' => 'reindex product prices', 'schedule' => '0 2 * * *', 'legacy_model' => 'catalog/observer::reindexProductPrices', 'decision' => 'replace'],
        ['feature_id' => 'CJ-022', 'name' => 'newsletter scheduled send', 'schedule' => '*/5 * * * *', 'legacy_model' => 'newsletter/observer::scheduledSend', 'decision' => 'replace'],
        ['feature_id' => 'CJ-023', 'name' => 'delete old captcha attempts', 'schedule' => '*/30 * * * *', 'legacy_model' => 'captcha/observer::deleteOldAttempts', 'decision' => 'replace'],
        ['feature_id' => 'CJ-024', 'name' => 'delete expired captcha images', 'schedule' => '*/10 * * * *', 'legacy_model' => 'captcha/observer::deleteExpiredImages', 'decision' => 'replace'],
        ['feature_id' => 'CJ-025', 'name' => 'generate sitemaps', 'schedule' => 'config-driven', 'legacy_model' => 'sitemap/observer::scheduledGenerateSitemaps', 'decision' => 'replace'],
    ],
];
