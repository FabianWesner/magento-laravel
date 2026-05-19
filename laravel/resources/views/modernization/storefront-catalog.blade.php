<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Storefront Catalog</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.storefront-catalog') }}">
        @livewireStyles
    </head>
    <body>
        <main class="catalog-shell">
            <header class="catalog-page-header">
                <p>Magento storefront parity</p>
                <h1>Catalog browse workbench</h1>
                <span>Read-only catalog listing, filters, sorting, swatches, and store-view snapshots backed by domain facts.</span>
            </header>

            <livewire:storefront-catalog-workbench />
        </main>

        @livewireScripts
    </body>
</html>
