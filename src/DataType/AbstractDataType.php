<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\AbstractDataType as BaseAbstractDataType;
use Omeka\Entity\Property;
use Omeka\Entity\Value;

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

    /**
     * Add a less-than query.
     *
     * Use in self::buildQuery() to perform simple less-than comparisons.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param int propertyId
     * @param int $number
     */
    public function addLessThanQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $number)
    {
        $alias = $adapter->createAlias();
        $qb->leftJoin(
            $this->getEntityClass(), $alias, 'WITH',
            $qb->expr()->andX(
                $qb->expr()->eq("$alias.resource", 'omeka_root.id'),
                $qb->expr()->eq("$alias.property", (int) $propertyId)
            )
        );
        $qb->andWhere($qb->expr()->lt(
            "$alias.value",
            $adapter->createNamedParameter($qb, $number)
        ));
    }

    /**
     * Add a greater-than query.
     *
     * Use in self::buildQuery() to perform simple greater than comparisons.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param int propertyId
     * @param int $number
     */
    public function addGreaterThanQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $number)
    {
        $alias = $adapter->createAlias();
        $qb->leftJoin(
            $this->getEntityClass(), $alias, 'WITH',
            $qb->expr()->andX(
                $qb->expr()->eq("$alias.resource", 'omeka_root.id'),
                $qb->expr()->eq("$alias.property", (int) $propertyId)
            )
        );
        $qb->andWhere($qb->expr()->gt(
            "$alias.value",
            $adapter->createNamedParameter($qb, $number)
        ));
    }
}

