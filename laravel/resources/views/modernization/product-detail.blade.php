<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Product Detail</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.product-detail') }}">
        @livewireStyles
    </head>
    <body>
        <main class="product-shell">
            <header class="product-page-header">
                <p>Magento storefront parity</p>
                <h1>Product detail workbench</h1>
                <span>Read-only product detail, media gallery, custom options, downloads, and commerce relationships backed by domain facts.</span>
            </header>

            <livewire:product-detail-workbench />
        </main>

        @livewireScripts
    </body>
</html>
