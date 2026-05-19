<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Tax Currency</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.tax-currency') }}">
        @livewireStyles
    </head>
    <body>
        <main class="tax-currency-shell">
            <header class="tax-currency-page-header">
                <p>Magento admin parity</p>
                <h1>Tax currency workbench</h1>
                <span>Read-only tax class, rate, rule, calculation, report aggregation, currency rate, symbol, and scheduled import diagnostics backed by deterministic domain facts.</span>
            </header>

            <livewire:tax-currency-workbench />
        </main>

        @livewireScripts
    </body>
</html>
