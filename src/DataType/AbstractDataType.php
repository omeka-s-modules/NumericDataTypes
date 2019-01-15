<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use NumericDataTypes\Entity\NumericDataTypesNumber;
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

    public function setEntityValues(NumericDataTypesNumber $entity, Value $value)
    {
        // The default behavior is to assume the number entity has one value,
        // and that value is derived from self::getNumberFromValue().
        $entity->setValue($this->getNumberFromValue($value->getValue()));
    }

    /**
     * Get the number to be stored from the passed value.
     *
     * Should throw \InvalidArgumentException if the passed value is invalid.
     *
     * @throws \InvalidArgumentException
     * @param string $value
     * @return int
     */
    public function getNumberFromValue($value)
    {
        throw new \RuntimeException(sprintf(
            'The %s data type does not support getNumberFromValue().',
            get_class($this)
        ));
    }

    /**
     * Add a less-than query.
     *
     * Use in self::buildQuery() to perform simple less-than comparisons.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param int propertyId
     * @param string $value
     */
    public function addLessThanQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $value)
    {
        try {
            $number = $this->getNumberFromValue($value);
        } catch (\InvalidArgumentException $e) {
            return; // invalid value
        }
        $alias = $adapter->createAlias();
        $qb->leftJoin(
            $this->getEntityClass(), $alias, 'WITH',
            $qb->expr()->andX(
                $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
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
     * @param string $value
     */
    public function addGreaterThanQuery(AdapterInterface $adapter, QueryBuilder $qb, $propertyId, $value)
    {
        try {
            $number = $this->getNumberFromValue($value);
        } catch (\InvalidArgumentException $e) {
            return; // invalid value
        }
        $alias = $adapter->createAlias();
        $qb->leftJoin(
            $this->getEntityClass(), $alias, 'WITH',
            $qb->expr()->andX(
                $qb->expr()->eq("$alias.resource", $adapter->getEntityClass() . '.id'),
                $qb->expr()->eq("$alias.property", (int) $propertyId)
            )
        );
        $qb->andWhere($qb->expr()->gt(
            "$alias.value",
            $adapter->createNamedParameter($qb, $number)
        ));
    }
}

