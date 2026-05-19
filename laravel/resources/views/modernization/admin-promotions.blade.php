<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Promotions</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-promotions') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-promotions-shell">
            <header class="admin-promotions-page-header">
                <p>Magento admin parity</p>
                <h1>Admin promotions workbench</h1>
                <span>Read-only diagnostics for cart price rules, catalog price rules, coupon generation, usage limits, report aggregation, and stale promotion problem states.</span>
            </header>

            <livewire:admin-promotions-workbench />
        </main>

        @livewireScripts
    </body>
</html>
