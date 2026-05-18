# Modernization Inventory Spec

Canonical inventory instructions live in `specs/modernization/inventory.md`.

Use `dev/modernization/inventory.php` to generate a machine-readable or Markdown report from the current checkout:

```bash
php dev/modernization/inventory.php --format=markdown
php dev/modernization/inventory.php --format=json
```

The current repository keeps Magento CE `1.9.4.5` source under `core/magento-1.9.4.5/` and reserves `project/` for the project overlay. The local Magento baseline is documented in `specs/modernization/install-verification.md`.
