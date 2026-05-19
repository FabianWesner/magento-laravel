<?php

namespace App\Modernization\Eav\Repositories;

use App\Modernization\Eav\Contracts\EavAttributeValueReaderContract;
use App\Modernization\Eav\EavEntityType;

final readonly class AddressEavRepository
{
    public function __construct(private EavAttributeValueReaderContract $reader) {}

    public function value(int $addressId, string $attributeCode): mixed
    {
        return $this->reader->value(EavEntityType::Address, $addressId, $attributeCode);
    }
}
