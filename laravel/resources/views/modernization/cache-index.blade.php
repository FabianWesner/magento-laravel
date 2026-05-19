<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Cache Index</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.cache-index') }}">
        @livewireStyles
    </head>
    <body>
        <main class="cache-index-shell">
            <header class="cache-index-page-header">
                <p>Magento admin parity</p>
                <h1>Cache index workbench</h1>
                <span>Read-only cache, index process, cron cleanup, stale state, and lock diagnostics backed by deterministic domain facts.</span>
            </header>

            <livewire:cache-index-workbench />
        </main>

        @livewireScripts
    </body>
</html>
