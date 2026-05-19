<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Customer</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-customer') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-customer-shell">
            <header class="admin-customer-page-header">
                <p>Magento admin parity</p>
                <h1>Admin customer management workbench</h1>
                <span>Read-only diagnostics for customer grids, addresses, customer activity, review and tag moderation, denied states, store-view values, and blocked problem states.</span>
            </header>

            <livewire:admin-customer-workbench />
        </main>

        @livewireScripts
    </body>
</html>
