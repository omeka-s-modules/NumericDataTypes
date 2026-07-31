<?php
namespace NumericDataTypes\FacetedBrowse\FacetType;

/**
 * The "show all available values" query shared by the numeric facet types, which
 * differ only in the NUMERIC_DATA_TYPE each declares. Requires $this->entityManager
 * on the using class.
 */
trait ShowAllTrait
{
    /**
     * Repeats DataType\*::getEntityClass() rather than injecting the data type
     * manager into seven facet types and seven factories for one lookup.
     */
    protected const NUMERIC_ENTITY_CLASSES = [
        'numeric:timestamp' => 'NumericDataTypes\Entity\NumericDataTypesTimestamp',
        'numeric:duration' => 'NumericDataTypes\Entity\NumericDataTypesDuration',
        'numeric:integer' => 'NumericDataTypes\Entity\NumericDataTypesInteger',
        'numeric:interval' => 'NumericDataTypes\Entity\NumericDataTypesInterval',
    ];

    /**
     * Return rows for the "show all available values" table.
     *
     * Orders on the parallel numeric table, not the displayed string, which sorts
     * lexically: P10D before P2D, and -0300 before -0500.
     *
     * @see FacetedBrowse\Controller\SiteAdmin\CategoryController::showAllValuesAction()
     */
    public function getShowAllValues(array $options): array
    {
        // These facet types all require a property, so without one there is
        // nothing to list and no reason to aggregate the whole category.
        $propertyId = $options['data']['property_id'] ?? null;
        if (!$propertyId) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        // COUNT DISTINCT because the join fans out: the numeric tables key on
        // resource and property only, with no reference to the value row.
        $qb->select('v.value label', 'COUNT(DISTINCT v.id) has_count')
            ->addSelect('MIN(n.value) AS HIDDEN numeric_value')
            ->from('Omeka\Entity\Value', 'v')
            ->innerJoin(
                self::NUMERIC_ENTITY_CLASSES[static::NUMERIC_DATA_TYPE], 'n', 'WITH',
                'n.resource = v.resource AND n.property = v.property'
            )
            ->andWhere('v.type = :type')
            ->andWhere('v.property = :propertyId')
            ->andWhere('v.resource IN (:resourceIds)')
            ->groupBy('v.value')
            ->setParameter('type', static::NUMERIC_DATA_TYPE)
            ->setParameter('propertyId', $propertyId)
            ->setParameter('resourceIds', $options['resource_ids'])
            ->setMaxResults($options['limit']);

        if ('has_count' === $options['sort_by']) {
            $qb->orderBy('has_count', $options['sort_order'])
                ->addOrderBy('numeric_value', 'asc');
        } else {
            $qb->orderBy('numeric_value', $options['sort_order']);
        }
        return $qb->getQuery()->getResult();
    }

    /**
     * These read best in their own order rather than by frequency.
     */
    public function getShowAllDefaultSort(): ?array
    {
        return ['label', 'asc'];
    }
}
