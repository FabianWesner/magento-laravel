<?php

namespace App\Modernization\Eav;

use InvalidArgumentException;

enum EavEntityType: string
{
    case Product = 'catalog_product';
    case Category = 'catalog_category';
    case Customer = 'customer';
    case Address = 'customer_address';

    public function baseTable(): string
    {
        return match ($this) {
            self::Product => 'catalog_product_entity',
            self::Category => 'catalog_category_entity',
            self::Customer => 'customer_entity',
            self::Address => 'customer_address_entity',
        };
    }

    public function valueTable(string $backendType): string
    {
        if (! in_array($backendType, ['datetime', 'decimal', 'int', 'text', 'varchar'], true)) {
            throw new InvalidArgumentException("Unsupported EAV backend type [{$backendType}].");
        }

        return $this->baseTable().'_'.$backendType;
    }

    public function hasStoreScopeValues(): bool
    {
        return in_array($this, [self::Product, self::Category], true);
    }
}
