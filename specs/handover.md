# Handover

Last updated: 2026-05-19 18:39 CEST

## Repository State

- Path: `/Users/fabianwesner/Herd/magento-lts`
- Branch: `main`
- Previous commit before this handover: `415e90aedf feat: add storefront cart diagnostics workbench`
- Active goal: modernize Magento CE 1.9.4.5 to Laravel per `specs/GOAL.md`
- Latest user direction: commit everything, then stop

## Current Work In Progress

The current slice adds a read-only Storefront Checkout Diagnostics Workbench under Laravel:

- Route: `/_modernization/storefront/checkout`
- Route name: `modernization.storefront.checkout`
- CSS asset route: `/_modernization/assets/storefront-checkout.css`
- CSS asset route name: `modernization.assets.storefront-checkout`
- Livewire component: `App\Livewire\StorefrontCheckoutWorkbench`
- Page view: `laravel/resources/views/modernization/storefront-checkout.blade.php`
- Livewire view: `laravel/resources/views/livewire/storefront-checkout-workbench.blade.php`

This is not checkout cutover. It is a local diagnostic surface that keeps all checkout, payment, order, and multishipping write paths disabled.

## Files Changed

- `.gitignore`
- `laravel/app/Livewire/StorefrontCheckoutWorkbench.php`
- `laravel/app/Modernization/Domain/DomainCatalog.php`
- `laravel/config/modernization.php`
- `laravel/database/seeders/DomainFactSeeder.php`
- `laravel/public/modernization/storefront-checkout.css`
- `laravel/resources/views/livewire/storefront-checkout-workbench.blade.php`
- `laravel/resources/views/modernization/storefront-checkout.blade.php`
- `laravel/routes/web.php`
- `laravel/tests/Feature/DomainFoundationTest.php`
- `laravel/tests/Feature/ModernizationStorefrontCheckoutRouteTest.php`
- `specs/handover.md`

## Implementation Notes

- Added domain catalog keys:
  - `checkout_step`
  - `checkout_payment`
  - `checkout_review`
  - `multishipping`
- Added deterministic domain facts for checkout steps, payment methods, review/agreement states, and multishipping diagnostics.
- Added route and module manifest entries for the checkout workbench and CSS asset.
- Added `DomainPolicy::class` to the modernization module manifest because these workbenches use it for local diagnostics.
- Added focused PHPUnit coverage for route rendering, CSS availability, Livewire filters, store scope, empty state, denied role, invalid public state normalization, and domain fact snapshots.
- Added screenshot ignore rule for `/storefront-checkout-*.png`.

## Verification Completed

Before the handover request interrupted the working turn, these checks passed:

- Laravel docs lookup was attempted with:
  - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH php artisan docs -- "Livewire route testing public properties authorization policies"`
  - It failed because the sandbox could not open the Laravel docs URL with macOS `open`.
- PHP syntax checks passed for:
  - `app/Livewire/StorefrontCheckoutWorkbench.php`
  - `database/seeders/DomainFactSeeder.php`
  - `tests/Feature/ModernizationStorefrontCheckoutRouteTest.php`
  - `tests/Feature/DomainFoundationTest.php`
- Focused PHPUnit passed before and after Pint:
  - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH php artisan test --compact tests/Feature/ModernizationStorefrontCheckoutRouteTest.php tests/Feature/DomainFoundationTest.php tests/Feature/ModernizationModuleRegistryTest.php`
  - Result after Pint: 29 tests, 1085 assertions passed.
- Pint passed:
  - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH vendor/bin/pint --dirty --format agent`
- Local deterministic facts were seeded:
  - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH php artisan db:seed --class=DomainFactSeeder --no-interaction`
- Herd route and asset returned HTTP 200:
  - `curl -I http://magento-lts.test/_modernization/storefront/checkout`
  - `curl -I http://magento-lts.test/_modernization/assets/storefront-checkout.css`

## Browser Verification Status

Chrome verification was started but not completed because the user interrupted and requested handover.

Completed in Chrome with Playwright:

- Opened `http://magento-lts.test/_modernization/storefront/checkout` at desktop size.
- Confirmed page title `Modernization Storefront Checkout`.
- Confirmed visible counts:
  - `5 steps`
  - `4 payments`
  - `3 review rows`
  - `3 multishipping rows`
  - `7 problems`
- Confirmed default rows including:
  - `Guest billing step ready`
  - `Shipping method required`
  - `Agreement required before review`
  - `DE billing and shipping ready`
  - `DE invalid shipping address`
- Clicked the Payments section and filtered status to `failed_payment`.
- Confirmed `Failed card authorization` appeared with disabled checkout actions.
- Started review/agreement filter verification, but the turn was interrupted before completing full desktop/mobile/network/console verification.

Still needed:

- Finish non-scripted Chrome desktop verification.
- Finish mobile viewport verification.
- Capture screenshots if continuing the previous verification convention.
- Check console output after the full pass.
- Check network requests and confirm no write endpoints fire, especially:
  - `checkout/onepage/saveMethod`
  - `checkout/onepage/saveBilling`
  - `checkout/onepage/saveShipping`
  - `checkout/onepage/saveShippingMethod`
  - `checkout/onepage/savePayment`
  - `checkout/onepage/saveOrder`
  - `checkout/multishipping/addressesPost`
  - `checkout/multishipping/shippingPost`
  - `checkout/multishipping/overviewPost`
  - payment redirect/callback/order write endpoints

## Subagent Findings

Two subagents completed read-only scans.

### Laravel Pattern Scan

The Laravel scan recommended:

- Reuse the cart route/component/view/CSS/test pattern.
- Add `/_modernization/storefront/checkout` after the cart route.
- Add `StorefrontCheckoutWorkbench`.
- Add `storefront-checkout.css`.
- Add `ModernizationStorefrontCheckoutRouteTest`.
- Add checkout-specific domain fact keys instead of overloading cart facts.
- Keep production `checkout/*` bridge-owned, not Laravel-owned.

### Magento Checkout Behavior Scan

The legacy scan found:

- Onepage step order is `login -> billing -> shipping -> shipping_method -> payment -> review`.
- `initCheckout()` can reset multishipping/customer balance/reward state, collect totals, save quote, and assign customer data.
- `saveMethod`, `saveBilling`, `saveShipping`, `saveShippingMethod`, `savePayment`, and `saveOrder` are write paths and must stay disabled in diagnostics.
- Required checkout agreements are enforced during `saveOrderAction()`.
- Multishipping address allocation, shipping method selection, billing/payment, overview, remove item, and final order placement all mutate quote/order state and must stay disabled.

## Important Constraints For The Next Mac

- Do not treat this workbench as production checkout replacement.
- Do not enable checkout, payment, order, or multishipping write paths without retained Magento characterization and DB delta evidence.
- Keep using `env PATH=/private/tmp/magento-lts-php85-bin:$PATH` for PHP commands on this project.
- PHP edits require:
  - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH vendor/bin/pint --dirty --format agent`
- Tests are PHPUnit, not Pest.
- Route tests should use `LazilyRefreshDatabase`.
- Documentation and tracking updates were intentionally deferred because implementation/browser verification was not completed.

## Suggested Next Steps

1. Pull or copy this committed state on the next Mac.
2. Run:
   - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH php artisan test --compact tests/Feature/ModernizationStorefrontCheckoutRouteTest.php tests/Feature/DomainFoundationTest.php tests/Feature/ModernizationModuleRegistryTest.php`
3. Seed local facts:
   - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH php artisan db:seed --class=DomainFactSeeder --no-interaction`
4. Finish Chrome verification at `http://magento-lts.test/_modernization/storefront/checkout`.
5. Run the normal gate:
   - `env PATH=/private/tmp/magento-lts-php85-bin:$PATH bash dev/modernization/gate.sh`
6. Only after verification, update:
   - `specs/tasklist.md`
   - `specs/open-issues.md`
   - `specs/modernization/backlog.md`
   - `specs/track.md`
   - `specs/progress.md`
