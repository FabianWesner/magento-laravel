<?php

namespace App\Modernization\Eav\Contracts;

use App\Modernization\Eav\EavAttribute;
use App\Modernization\Eav\EavEntityType;

interface EavAttributeValueReaderContract
{
    public function attribute(EavEntityType $entityType, string $attributeCode): ?EavAttribute;

    public function value(EavEntityType $entityType, int $entityId, string $attributeCode, int $storeId = 0): mixed;
}
