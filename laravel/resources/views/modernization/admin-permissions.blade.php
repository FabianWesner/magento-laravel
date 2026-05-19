<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Admin Permissions</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.admin-permissions') }}">
        @livewireStyles
    </head>
    <body>
        <main class="admin-permissions-shell">
            <header class="admin-permissions-page-header">
                <p>Magento admin parity</p>
                <h1>Admin permissions workbench</h1>
                <span>Read-only diagnostics for admin roles, ACL resources, API users, REST roles, OAuth scopes, denied states, and rollback metadata.</span>
            </header>

            <livewire:admin-permissions-workbench />
        </main>

        @livewireScripts
    </body>
</html>
