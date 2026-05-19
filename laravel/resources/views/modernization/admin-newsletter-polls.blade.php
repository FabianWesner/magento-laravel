<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Newsletter Polls</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-newsletter-polls') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-newsletter-polls-shell">
            <header class="admin-newsletter-polls-page-header">
                <p>Magento admin parity</p>
                <h1>Admin newsletter and polls workbench</h1>
                <span>Read-only diagnostics for newsletter subscribers, email queue rows, problem reports, poll questions, poll answers, vote guards, and store scope.</span>
            </header>

            <livewire:admin-newsletter-polls-workbench />
        </main>

        @livewireScripts
    </body>
</html>
