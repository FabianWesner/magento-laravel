<?php

namespace App\Modernization\Eav\Repositories;

use App\Modernization\Eav\Contracts\EavAttributeValueReaderContract;
use App\Modernization\Eav\EavEntityType;

final readonly class ProductEavRepository
{
    public function __construct(private EavAttributeValueReaderContract $reader) {}

    public function value(int $productId, string $attributeCode, int $storeId = 0): mixed
    {
        return $this->reader->value(EavEntityType::Product, $productId, $attributeCode, $storeId);
    }
}
