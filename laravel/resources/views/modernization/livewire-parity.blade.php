<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Livewire Parity</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.livewire-parity') }}">
        @livewireStyles
    </head>
    <body>
        <main class="parity-shell">
            <header class="parity-header">
                <p class="parity-kicker">Magento to Laravel migration</p>
                <h1>Livewire parity workbench</h1>
                <p>
                    Browser-reachable storefront and admin parity controls for validating migrated
                    Livewire behavior against retained Magento fixtures.
                </p>
            </header>

            <div class="parity-grid">
                <livewire:storefront-parity-panel />
                <livewire:admin-parity-grid />
            </div>
        </main>

        @livewireScripts
    </body>
</html>
