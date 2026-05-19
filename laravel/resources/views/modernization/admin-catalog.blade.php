<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Catalog</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-catalog') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-catalog-shell">
            <header class="admin-catalog-page-header">
                <p>Magento admin parity</p>
                <h1>Admin catalog management workbench</h1>
                <span>Read-only diagnostics for product grids, category tree rows, EAV attribute signals, media galleries, downloadable links, store-view values, and blocked problem states.</span>
            </header>

            <livewire:admin-catalog-workbench />
        </main>

        @livewireScripts
    </body>
</html>
