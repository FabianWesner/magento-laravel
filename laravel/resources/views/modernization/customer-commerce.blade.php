<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Customer Commerce</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.customer-commerce') }}">
        @livewireStyles
    </head>
    <body>
        <main class="commerce-shell">
            <header class="commerce-page-header">
                <p>Magento storefront parity</p>
                <h1>Customer commerce workbench</h1>
                <span>Read-only wishlist, compare, review, and product tag states backed by deterministic domain facts.</span>
            </header>

            <livewire:customer-commerce-workbench />
        </main>

        @livewireScripts
    </body>
</html>
