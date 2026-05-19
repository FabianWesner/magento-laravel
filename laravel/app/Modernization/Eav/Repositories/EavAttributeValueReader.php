<?php

namespace App\Modernization\Eav\Repositories;

use App\Modernization\Eav\Contracts\EavAttributeValueReaderContract;
use App\Modernization\Eav\EavAttribute;
use App\Modernization\Eav\EavEntityType;
use Illuminate\Database\ConnectionInterface;

final readonly class EavAttributeValueReader implements EavAttributeValueReaderContract
{
    public function __construct(private ConnectionInterface $connection) {}

    public function attribute(EavEntityType $entityType, string $attributeCode): ?EavAttribute
    {
        $row = $this->connection
            ->table('eav_attribute')
            ->join('eav_entity_type', 'eav_entity_type.entity_type_id', '=', 'eav_attribute.entity_type_id')
            ->where('eav_entity_type.entity_type_code', $entityType->value)
            ->where('eav_attribute.attribute_code', $attributeCode)
            ->select([
                'eav_attribute.attribute_id',
                'eav_attribute.entity_type_id',
                'eav_attribute.attribute_code',
                'eav_attribute.backend_type',
            ])
            ->first();

        if ($row === null) {
            return null;
        }

        return new EavAttribute(
            attributeId: (int) $row->attribute_id,
            entityTypeId: (int) $row->entity_type_id,
            code: (string) $row->attribute_code,
            backendType: (string) $row->backend_type,
        );
    }

    public function value(EavEntityType $entityType, int $entityId, string $attributeCode, int $storeId = 0): mixed
    {
        $attribute = $this->attribute($entityType, $attributeCode);
        if ($attribute === null) {
            return null;
        }

        if ($attribute->backendType === 'static') {
            return $this->connection
                ->table($entityType->baseTable())
                ->where('entity_id', $entityId)
                ->value($attribute->code);
        }

        $query = $this->connection
            ->table($entityType->valueTable($attribute->backendType))
            ->where('entity_id', $entityId)
            ->where('attribute_id', $attribute->attributeId)
            ->select('value');

        if ($entityType->hasStoreScopeValues()) {
            $query
                ->whereIn('store_id', [0, $storeId])
                ->orderByRaw('case when store_id = ? then 0 else 1 end', [$storeId]);
        }

        return $query->value('value');
    }
}
