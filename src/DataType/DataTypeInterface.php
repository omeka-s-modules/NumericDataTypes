<?php
namespace NumericDataTypes\DataType;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AdapterInterface;

interface DataTypeInterface
{
    /**
     * Get the fully qualified name of the corresponding entity.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Get the number to be stored from the passed value.
     *
     * @param string $value
     * @return int
     */
    public function getNumberFromValue($value);

    /**
     * Build a numeric query.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param array $query
     */
    public function buildQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query);

    /**
     * Sort a numeric query.
     *
     * @param AdapterInterface $adapter
     * @param QueryBuilder $qb
     * @param array $query
     * @param string $type
     * @param int $propertyId
     */
    public function sortQuery(AdapterInterface $adapter, QueryBuilder $qb, array $query, $type, $propertyId);
}
