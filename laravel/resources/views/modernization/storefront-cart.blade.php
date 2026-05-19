<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Storefront Cart</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.storefront-cart') }}">
        @livewireStyles
    </head>
    <body>
        <main class="storefront-cart-shell">
            <header class="storefront-cart-page-header">
                <p>Magento storefront parity</p>
                <h1>Cart diagnostics workbench</h1>
                <span>Read-only quote, cart item, totals, shipping, coupon, stock, and problem snapshots backed by domain facts.</span>
            </header>

            <livewire:storefront-cart-workbench />
        </main>

        @livewireScripts
    </body>
</html>
