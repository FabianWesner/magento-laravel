<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Store Operations</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-store-operations') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-store-operations-shell">
            <header class="admin-store-operations-page-header">
                <p>Magento admin parity</p>
                <h1>Admin store operations workbench</h1>
                <span>Read-only diagnostics for store hierarchy, backups, system information, transactional email templates, URL rewrites, sitemaps, RSS feeds, and store scope.</span>
            </header>

            <livewire:admin-store-operations-workbench />
        </main>

        @livewireScripts
    </body>
</html>
