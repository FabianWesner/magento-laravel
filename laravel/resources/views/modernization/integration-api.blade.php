<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Integrations API</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.integration-api') }}">
        @livewireStyles
    </head>
    <body>
        <main class="integration-api-shell">
            <header class="integration-api-page-header">
                <p>Magento admin parity</p>
                <h1>Integrations API workbench</h1>
                <span>Read-only diagnostics for legacy API contracts, sandbox adapters, callbacks, OAuth, retry controls, secrets, and rollback metadata.</span>
            </header>

            <livewire:integration-api-workbench />
        </main>

        @livewireScripts
    </body>
</html>
