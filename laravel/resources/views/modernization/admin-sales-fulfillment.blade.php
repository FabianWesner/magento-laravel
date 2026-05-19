<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Sales Fulfillment</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-sales-fulfillment') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-sales-fulfillment-shell">
            <header class="admin-sales-fulfillment-page-header">
                <p>Magento admin parity</p>
                <h1>Admin sales fulfillment workbench</h1>
                <span>Read-only diagnostics for order state guards, invoice capture, shipment tracking, credit memo refunds, payment transactions, comments, PDFs, and fulfillment problem states.</span>
            </header>

            <livewire:admin-sales-fulfillment-workbench />
        </main>

        @livewireScripts
    </body>
</html>
