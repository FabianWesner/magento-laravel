<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Import Export</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.import-export') }}">
        @livewireStyles
    </head>
    <body>
        <main class="import-export-shell">
            <header class="import-export-page-header">
                <p>Magento admin parity</p>
                <h1>Import export workbench</h1>
                <span>Read-only import, export, dataflow profile, generated file, and validation diagnostics backed by deterministic domain facts.</span>
            </header>

            <livewire:import-export-workbench />
        </main>

        @livewireScripts
    </body>
</html>
