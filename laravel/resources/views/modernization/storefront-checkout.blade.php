<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Storefront Checkout</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.storefront-checkout') }}">
        @livewireStyles
    </head>
    <body>
        <main class="storefront-checkout-shell">
            <header class="storefront-checkout-page-header">
                <p>Magento storefront parity</p>
                <h1>Checkout diagnostics workbench</h1>
                <span>Read-only billing, shipping, payment, review, agreement, and multishipping snapshots backed by domain facts.</span>
            </header>

            <livewire:storefront-checkout-workbench />
        </main>

        @livewireScripts
    </body>
</html>
