<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Reports</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-reports') }}">
        @livewireStyles
    </head>
    <body>
        <main class="reports-shell">
            <header class="reports-page-header">
                <p>Magento admin parity</p>
                <h1>Reports workbench</h1>
                <span>Sales, tax, shipping, coupon, product, customer, search, bestseller, and low-stock report snapshots.</span>
            </header>

            <livewire:admin-reports-workbench />
        </main>

        @livewireScripts
    </body>
</html>
