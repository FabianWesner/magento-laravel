<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Cron Jobs</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.cron-jobs') }}">
        @livewireStyles
    </head>
    <body>
        <main class="cron-jobs-shell">
            <header class="cron-jobs-page-header">
                <p>Magento admin parity</p>
                <h1>Cron jobs workbench</h1>
                <span>Read-only scheduler diagnostics for all Magento cron mappings, replacement decisions, config-driven schedules, and blocked high-risk jobs.</span>
            </header>

            <livewire:cron-jobs-workbench />
        </main>

        @livewireScripts
    </body>
</html>
