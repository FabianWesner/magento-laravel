<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin CMS Design</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-cms-design') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-cms-design-shell">
            <header class="admin-cms-design-page-header">
                <p>Magento admin parity</p>
                <h1>Admin CMS design workbench</h1>
                <span>Read-only diagnostics for CMS pages, static blocks, widget instances, URL rewrites, design scope settings, and cache dependencies.</span>
            </header>

            <livewire:admin-cms-design-workbench />
        </main>

        @livewireScripts
    </body>
</html>
