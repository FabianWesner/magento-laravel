<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Storefront Search</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.search') }}">
        @livewireStyles
    </head>
    <body>
        <main class="search-shell">
            <header class="search-page-header">
                <p>Magento storefront parity</p>
                <h1>Search workbench</h1>
                <span>Read-only quick search, advanced criteria, redirects, RSS links, and stale index states backed by domain facts.</span>
            </header>

            <livewire:search-workbench />
        </main>

        @livewireScripts
    </body>
</html>
