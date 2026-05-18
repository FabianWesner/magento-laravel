# Technologies And Dependencies

This document records the main technologies and the locked direct dependency versions from the current `composer.json` and `composer.lock`.

## Runtime Technologies

| Area | Technology | Version or source |
| --- | --- | --- |
| Language runtime | PHP | `>=8.1 <8.6` from `composer.json` |
| Composer platform | PHP | `8.1` pinned in `composer.json` config |
| Package manager | Composer | Runtime API `^2`; lock plugin API `2.9.0` |
| Application framework | OpenMage / Magento 1 | OpenMage `20.18.0`; Magento compatibility `1.9.4.5` from `app/Mage.php` |
| Database | MySQL/MariaDB | `pdo_mysql` resource adapter; docs require MySQL 5.6+ or MariaDB |
| Cache/session option | Redis | Optional packages are locked through Composer |
| Configuration | XML + DB + environment | `app/etc/*.xml`, module XML, `core_config_data`, `.env` |
| Documentation | MkDocs Material | Configured in `mkdocs.yml`; source in `docs/content` |
| Unit tests | PHPUnit | `9.6.34` |
| Static analysis | PHPStan | `2.1.54`, level 8 |
| Coding style | Easy Coding Standard / PHP-CS-Fixer / PHPCS | ECS `13.1.3`, PHP-CS-Fixer `3.95.1`, PHPCS `3.13.5` |
| Refactoring | Rector | `2.4.2` |
| E2E tests | Cypress via DDEV add-on | Configured by `.cypress.config.js`; base URL `https://magento-lts.ddev.site` |

## PHP Extensions

The platform requirements in `composer.json` and `composer.lock` require:

`ctype`, `curl`, `dom`, `ftp`, `gd`, `hash`, `iconv`, `intl`, `json`, `libxml`, `mbstring`, `mysqli`, `pdo`, `pdo_mysql`, `posix`, `simplexml`, `soap`, `spl`, `zlib`.

Development also requires `xmlreader`.

## Frontend Assets

| Asset | Version | Source |
| --- | --- | --- |
| Prototype | `1.7.3` | Header in `js/prototype/prototype.js` |
| script.aculo.us | `1.8.2` | Header in `js/scriptaculous/scriptaculous.js` |
| DHTML Calendar | `1.0` | Header in `js/calendar/calendar.js` |
| jQuery | `v3.7.1` | `components/jquery` in `composer.lock` |
| TinyMCE | `8.5.0` | `tinymce/tinymce` in `composer.lock` |
| TinyMCE i18n | `26.4.7` | `mklkj/tinymce-i18n` in `composer.lock` |
| Chart.js | `v4.5.1` | `nnnick/chartjs` in `composer.lock` |
| Flow.js | `dev-master` | `flowjs/flowjs` in `composer.lock` |

## Direct Runtime Dependencies

| Package | Constraint | Locked version |
| --- | --- | --- |
| `colinmollenhour/cache-backend-redis` | `^1.14` | `1.18.0` |
| `colinmollenhour/magento-redis-session` | `^3.2.0` | `3.3.0` |
| `components/jquery` | `^3.7.1` | `v3.7.1` |
| `cweagans/composer-patches` | `^2.0` | `2.0.0` |
| `empiricompany/openmage_ignition` | `^1.5` | `1.5.2` |
| `ezyang/htmlpurifier` | `^4.17` | `v4.19.0` |
| `flowjs/flowjs` | `dev-master` | `dev-master` |
| `laminas/laminas-captcha` | `^2.18` | `2.18.0` |
| `magento-hackathon/magento-composer-installer` | `^3.1 || ^2.1 || ^4.0` | `4.0.2` |
| `mklkj/tinymce-i18n` | `^26.1` | `26.4.7` |
| `monolog/monolog` | `^3.9` | `3.10.0` |
| `nesbot/carbon` | `^2.73` | `2.73.0` |
| `nnnick/chartjs` | `^4.4` | `v4.5.1` |
| `openmage/composer-plugin` | `^3.1` | `v3.2.0` |
| `pelago/emogrifier` | `^8.0` | `v8.2.0` |
| `php-units-of-measure/php-units-of-measure` | `^2.2` | `v2.2.0` |
| `phpseclib/mcrypt_compat` | `^2.0.3` | `2.0.8` |
| `phpseclib/phpseclib` | `^3.0.14` | `3.0.52` |
| `shardj/zf1-future` | `^1.24.1` | `1.25.0` |
| `shipstream/fedex-rest-sdk` | `^1.4` | `v1.4.0` |
| `symfony/mime` | `^6.4` | `v6.4.36` |
| `symfony/polyfill-php82` | `^1.33` | `v1.37.0` |
| `symfony/polyfill-php83` | `^1.33` | `v1.37.0` |
| `symfony/polyfill-php84` | `^1.33` | `v1.37.0` |
| `symfony/polyfill-php85` | `^1.33` | `v1.37.0` |
| `symfony/string` | `^6.4` | `v6.4.34` |
| `symfony/translation-contracts` | `^3.5` | `v3.7.0` |
| `symfony/validator` | `^6.4` | `v6.4.36` |
| `tinymce/tinymce` | `^8.0` | `8.5.0` |
| `vlucas/phpdotenv` | `^5.6` | `v5.6.3` |

`composer.lock` contains 89 runtime packages including transitive packages such as Guzzle, Saloon, Illuminate support components, Laminas components, Symfony components, PSR interfaces, Spatie Ignition packages, and Redis session/cache support packages.

## Direct Development Dependencies

| Package | Constraint | Locked version |
| --- | --- | --- |
| `composer/composer` | `>= 2.2.27 <2.3 || >= 2.9.6` | `2.2.27` |
| `dealerdirect/phpcodesniffer-composer-installer` | `^1.0.0` | `v1.2.1` |
| `friendsofphp/php-cs-fixer` | `^3.6` | `v3.95.1` |
| `macopedia/phpstan-magento1` | `^1.1` | `v1.2.0` |
| `magento-ecg/coding-standard` | `^4.5` | `4.5.4` |
| `openmage/dev-translations` | `^1.0.1` | `1.0.1` |
| `perftools/php-profiler` | `^1.1` | `1.4.0` |
| `phpcompatibility/php-compatibility` | `^9.3` | `9.3.5` |
| `phpmd/phpmd` | `^2.13` | `2.15.0` |
| `phpstan/extension-installer` | `^1.4` | `1.4.3` |
| `phpstan/phpstan` | `^2.1.18` | `2.1.54` |
| `phpstan/phpstan-deprecation-rules` | `^2.0` | `2.0.4` |
| `phpstan/phpstan-phpunit` | `^2.0` | `2.0.16` |
| `phpstan/phpstan-strict-rules` | `^2.0` | `2.0.11` |
| `phpunit/phpunit` | `^9.6` | `9.6.34` |
| `rector/rector` | `^2.1.12` | `2.4.2` |
| `shipmonk/phpstan-baseline-per-identifier` | `^2.3` | `2.3.0` |
| `squizlabs/php_codesniffer` | `^3.7` | `3.13.5` |
| `symplify/easy-coding-standard` | `^13.0` | `13.1.3` |
| `symplify/vendor-patches` | `^12.0, !=12.0.5, !=12.0.6` | `12.0.1` |

`composer.lock` contains 76 development packages including PHPUnit internals, Rector dependencies, Composer internals, ReactPHP packages used by tooling, and Symfony development components.

## Composer Plugins And Install Behavior

Composer plugins are explicitly allowed in `composer.json`:

- `cweagans/composer-patches`
- `dealerdirect/phpcodesniffer-composer-installer`
- `magento-hackathon/magento-composer-installer`
- `openmage/composer-plugin`
- `phpstan/extension-installer`

Magento deployment is configured with:

- `magento-root-dir`: `.`
- `magento-deploystrategy`: `copy`
- `magento-deploystrategy-dev`: `symlink`
- `magento-force`: `true`

## Vendor Patches

Vendor patches are configured in `patches.json` and locked in `patches.lock.json`.

| Package | Patch descriptions |
| --- | --- |
| `shardj/zf1-future` | `MAG-1.9.3.0`, `MAG-1.9.3.7 - SUPEE-10415`, `OM-918`, `OM-1081`, `OM-2047`, `OM-2050` |
| `magento-ecg/coding-standard` | `ECG-72 - Fix LoopSniff` |
| `react/promise` | `PR-264 - PHP 8.5 syntax` |

Lock metadata:

- Composer lock content hash: `50497fc4ed00a1af92b7d5239263c529`.
- Patch lock hash: `7cd3c6afba6520d9e06f454d66cfb5b51172b59852d63853d7ad70fae225aca9`.

