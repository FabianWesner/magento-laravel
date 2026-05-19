<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Storefront Communications</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.communications') }}">
        @livewireStyles
    </head>
    <body>
        <main class="communications-shell">
            <header class="communications-page-header">
                <p>Magento storefront parity</p>
                <h1>Communications workbench</h1>
                <span>Read-only newsletter, contact, send-to-friend, product-alert, and queued email states backed by deterministic domain facts.</span>
            </header>

            <livewire:communications-workbench />
        </main>

        @livewireScripts
    </body>
</html>
