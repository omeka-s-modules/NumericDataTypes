<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\AbstractDataType as BaseAbstractDataType;
use Omeka\Entity\Property;

abstract class AbstractDataType extends BaseAbstractDataType implements DataTypeInterface
{
    public function getOptgroupLabel()
    {
        return 'Numeric'; // @translate
    }

    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query)
    {
    }

    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId)
    {
    }
}

