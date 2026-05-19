<!DOCTYPE html>
<html lang="{{ str(app()->getLocale())->replace('_', '-') }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Modernization Storefront CMS SEO</title>
        <link rel="icon" href="data:,">
        <link rel="stylesheet" href="{{ route('modernization.assets.cms-seo') }}">
        @livewireStyles
    </head>
    <body>
        <main class="cms-shell">
            <header class="cms-page-header">
                <p>Magento storefront parity</p>
                <h1>CMS SEO workbench</h1>
                <span>Read-only CMS pages, blocks, widgets, redirects, URL rewrites, sitemap, and RSS states backed by domain facts.</span>
            </header>

            <livewire:cms-seo-workbench />
        </main>

        @livewireScripts
    </body>
</html>
