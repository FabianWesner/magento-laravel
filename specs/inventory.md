# Modernization Inventory Spec

Canonical inventory instructions live in `specs/modernization/inventory.md`.

Use `dev/modernization/inventory.php` to generate a machine-readable or Markdown report from the current checkout:

```bash
php dev/modernization/inventory.php --format=markdown
php dev/modernization/inventory.php --format=json
```

The current root checkout is expected to be Magento source-only unless a project overlay is added. The local Magento CE `1.9.4.5` baseline is documented in `specs/modernization/install-verification.md`.
