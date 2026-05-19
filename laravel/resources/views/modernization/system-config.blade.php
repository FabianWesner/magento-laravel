<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin System Config</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.system-config') }}">
        @livewireStyles
    </head>
    <body>
        <main class="system-config-shell">
            <header class="system-config-page-header">
                <p>Magento admin parity</p>
                <h1>System config workbench</h1>
                <span>Read-only system configuration, multistore scope, source model, backend model, secret, environment override, and scoped cache diagnostics backed by deterministic domain facts.</span>
            </header>

            <livewire:system-config-workbench />
        </main>

        @livewireScripts
    </body>
</html>
