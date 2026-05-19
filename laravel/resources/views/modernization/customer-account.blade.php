<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Customer Account</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.customer-account') }}">
        @livewireStyles
    </head>
    <body>
        <main class="customer-shell">
            <header class="customer-page-header">
                <p>Magento storefront parity</p>
                <h1>Customer account workbench</h1>
                <span>Read-only account dashboard, address book, recent orders, and security/session snapshots backed by domain facts.</span>
            </header>

            <livewire:customer-account-workbench />
        </main>

        @livewireScripts
    </body>
</html>
